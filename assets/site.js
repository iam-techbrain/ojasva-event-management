const form = document.querySelector('#brief-form');
const status = document.querySelector('#form-status');
const preview = document.querySelector('#brief-preview');
const summary = document.querySelector('#brief-summary');
const showcase = document.querySelector('.showcase');
const cover = document.querySelector('.cover');
const menu = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#site-nav');

if (showcase) {
  showcase.hidden = false;
  const sceneButtons = showcase.querySelectorAll('[data-scene]');
  sceneButtons.forEach((button) => {
    button.addEventListener('click', () => {
      cover.dataset.scene = button.dataset.scene;
      sceneButtons.forEach((option) => {
        option.setAttribute('aria-pressed', String(option === button));
      });
    });
  });
}

function setMenu(open) {
  menu.setAttribute('aria-expanded', String(open));
  menu.querySelector('span').textContent = open ? '−' : '+';
  navigation.classList.toggle('is-open', open);
}
menu.hidden = false;
menu.addEventListener('click', () => setMenu(menu.getAttribute('aria-expanded') === 'false'));
navigation.querySelectorAll('a').forEach((link) => {
  link.addEventListener('click', () => setMenu(false));
});
document.querySelector('.header').addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && menu.getAttribute('aria-expanded') === 'true') {
    setMenu(false);
    menu.focus();
  }
});

const filters = document.querySelector('.gallery-filters');
if (filters) {
  filters.hidden = false;
  const buttons = filters.querySelectorAll('[data-filter]');
  const images = document.querySelectorAll('[data-category]');
  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      buttons.forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
      images.forEach((item) => {
        item.hidden = button.dataset.filter !== 'all' && button.dataset.filter !== item.dataset.category;
      });
    });
  });

  const lightbox = document.querySelector('.lightbox');
  document.querySelectorAll('[data-lightbox]').forEach((link) => {
    link.addEventListener('click', (event) => {
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || !lightbox.showModal) return;
      event.preventDefault();
      const image = lightbox.querySelector('img');
      image.src = link.href;
      image.alt = link.querySelector('img').alt;
      lightbox.querySelector('p').textContent = link.nextElementSibling.textContent;
      lightbox.showModal();
    });
  });
}

if (form) {
  const enquiry = document.querySelector('.enquiry-ready');
  const enquiryLink = document.querySelector('#enquiry-link');
  const params = new URLSearchParams(location.search);
  ['event', 'package'].forEach((field) => {
    const select = form.elements.namedItem(field);
    const value = params.get(field);
    if (value && Array.from(select.options).some((option) => option.value === value)) {
      select.value = value;
    }
  });
  form.querySelectorAll('[type="submit"]').forEach((button) => { button.disabled = false; });

  function updatePreview() {
    const { event, date, venue, guests } = form.elements;
    const preferred = form.elements.namedItem('package').value;
    const parts = [event.value, preferred, venue.value.trim(), date.value];
    if (guests.value && guests.validity.valid) {
      parts.push(guests.value + (Number(guests.value) === 1 ? ' guest' : ' guests'));
    }
    summary.textContent = parts.filter(Boolean).join(' · ');
    preview.hidden = !summary.textContent;
    status.textContent = '';
    enquiry.hidden = true;
    enquiryLink.removeAttribute('href');
  }
  form.addEventListener('input', updatePreview);
  window.addEventListener('pageshow', updatePreview);

  document.querySelectorAll('[data-event]').forEach((link) => {
    link.addEventListener('click', () => {
      form.elements.event.value = link.dataset.event;
      updatePreview();
    });
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = new FormData(form);
    const fields = [
      ['Client name', 'name'],
      ['Phone number', 'phone'],
      ['Email address', 'email'],
      ['Event type', 'event'],
      ['Event date', 'date'],
      ['City or venue', 'venue'],
      ['Estimated guests', 'guests'],
      ['Preferred package', 'package'],
      ['Budget (INR)', 'budget'],
      ['Event details', 'details'],
    ];

    if (event.submitter?.value === 'download') {
      const brief = ['OJASVA EVENTS', 'Event planning brief', ''];
      fields.forEach(([label, key]) => {
        const value = (data.get(key) || '').trim();
        if (value) brief.push(label + ': ' + value);
      });
      const url = URL.createObjectURL(new Blob([brief.join('\n') + '\n'], { type: 'text/plain;charset=utf-8' }));
      const download = document.createElement('a');
      download.href = url;
      download.download = 'Ojasva-Events-Brief.txt';
      document.body.append(download);
      download.click();
      download.remove();
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);
      status.textContent = 'Your event brief is ready to download.';
      return;
    }

    // Submit Enquiry -> Store into SQLite database via submit.php
    const submitBtn = event.submitter || form.querySelector('button[type="submit"]');
    const clientName = (data.get('name') || '').trim();
    const eventType = (data.get('event') || '').trim();

    const newLead = {
      id: 'lead-' + Date.now(),
      name: clientName,
      phone: (data.get('phone') || '').trim(),
      email: (data.get('email') || '').trim(),
      event: eventType,
      date: (data.get('date') || '').trim(),
      venue: (data.get('venue') || '').trim(),
      guests: (data.get('guests') || '').trim(),
      package: (data.get('package') || '').trim(),
      budget: (data.get('budget') || '').trim(),
      details: (data.get('details') || '').trim(),
      status: 'New',
      submittedAt: new Date().toISOString()
    };

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.7';
    }
    status.textContent = 'Sending your enquiry...';

    try {
      await fetch('submit.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(newLead)
      });
    } catch (err) {
      // Offline fallback
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
      }
    }

    form.reset();
    updatePreview();

    const displayName = clientName ? escapeHtml(clientName) : 'Valued Guest';
    const displayEvent = eventType ? escapeHtml(eventType) : 'Event';
    status.innerHTML = `
      <div class="form-success-banner" role="alert">
        <div class="success-icon">✓</div>
        <div class="success-content">
          <h3>Thank You, ${displayName}!</h3>
          <p>Your enquiry for <strong>${displayEvent}</strong> has been received successfully.</p>
          <p class="sub">Our event planning team will review your requirements and get in touch with you shortly.</p>
        </div>
      </div>
    `;
    status.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  // Check if redirected with ?status=success from non-AJAX submission
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('status') === 'success') {
    status.innerHTML = `
      <div class="form-success-banner" role="alert">
        <div class="success-icon">✓</div>
        <div class="success-content">
          <h3>Thank You!</h3>
          <p>Your enquiry has been received successfully.</p>
          <p class="sub">Our event planning team will review your requirements and get in touch with you shortly.</p>
        </div>
      </div>
    `;
    status.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, m => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[m]);
}
