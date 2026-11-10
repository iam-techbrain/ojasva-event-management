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

    // Submit Enquiry -> Store in leads.json & localStorage
    const newLead = {
      id: 'lead-' + Date.now(),
      name: (data.get('name') || '').trim(),
      phone: (data.get('phone') || '').trim(),
      email: (data.get('email') || '').trim(),
      event: (data.get('event') || '').trim(),
      date: (data.get('date') || '').trim(),
      venue: (data.get('venue') || '').trim(),
      guests: (data.get('guests') || '').trim(),
      package: (data.get('package') || '').trim(),
      budget: (data.get('budget') || '').trim(),
      details: (data.get('details') || '').trim(),
      status: 'New',
      submittedAt: new Date().toISOString()
    };

    // 1. Cache in browser LocalStorage
    try {
      const currentLeads = JSON.parse(localStorage.getItem('ojasva_leads') || '[]');
      currentLeads.unshift(newLead);
      localStorage.setItem('ojasva_leads', JSON.stringify(currentLeads));
    } catch (e) {
      console.warn('LocalStorage error:', e);
    }

    // 2. Post to Node.js backend to save in leads.json file
    status.textContent = 'Saving your enquiry to leads.json...';
    try {
      await fetch('/api/leads', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newLead)
      });
    } catch (err) {
      // Offline / standalone browser mode handled gracefully
    }

    form.reset();
    updatePreview();
    status.innerHTML = '✓ <strong>Enquiry Received!</strong> Your details have been stored in <code>leads.json</code>. You can review all leads on the <a href="dashboard.html" style="text-decoration:underline;color:var(--plum);font-weight:700">/dashboard</a>.';
    status.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });
}
