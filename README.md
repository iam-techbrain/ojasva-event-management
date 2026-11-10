# Ojasva Events — Event Planning & Design

A modern, fast, and accessible multi-page website for **Ojasva Events**, crafted to showcase weddings, corporate gatherings, and private celebrations with elegant aesthetics and seamless inquiry generation.

---

## 📌 Project Overview

- **Brand:** Ojasva Events
- **Tagline:** *Make the moment unforgettable.*
- **Tech Stack:** Semantic HTML5, Modular CSS3, Vanilla ES6 JavaScript (Zero external libraries or runtime dependencies).
- **Design Philosophy:** Human-first, editorial typography, rich plum & warm gold palette, responsive layout, and accessible interactions.

---

## 🗂️ File Structure

```text
ojasva-event-management/
├── assets/
│   ├── corporate.webp            # Corporate stage & lighting showcase image
│   ├── hero.webp                 # Main hero celebration & wedding mandap banner
│   ├── ojasva-monogram.png       # Official brand monogram icon & favicon
│   ├── private.webp              # Private celebration & dinner styling image
│   ├── site.css                  # Complete stylesheet (responsive, dark/light tones)
│   ├── site.js                   # Interactive client-side logic (forms, gallery, menu)
│   └── wedding.webp              # Ceremonial wedding mandap visual
├── contact.html                  # Contact information, booking policies & full inquiry form
├── dashboard.html                # Admin Leads & Enquiries Dashboard (/dashboard)
├── gallery.html                  # Categorized event gallery with interactive modal lightbox
├── index.html                    # Homepage with scene switcher, services & planning brief
├── leads.json                    # Central JSON file storing all submitted customer leads
├── login.html                    # Secure admin login portal (/login)
├── server.js                     # Built-in lightweight Node.js server & REST API
├── services.html                 # Detailed event categories & Silver/Gold/Platinum packages
├── start-server.bat              # 1-click Windows launcher for local server
└── README.md                     # Documentation and setup guide
```

---

## 🔐 Admin Portal & Leads Dashboard

- **Dashboard URL:** `/dashboard` or `dashboard.html`
- **Login Portal:** `/login` or `login.html`
- **Login Credentials:**
  - **Login ID:** `admin@123`
  - **Password:** `admin`
- **How It Works:**
  1. Whenever a customer submits an enquiry on `index.html` or `contact.html`, it is automatically saved directly into **`leads.json`**.
  2. The admin can log in to **`/dashboard`** to see total inquiries, breakdown by category (Weddings, Corporate, etc.), customer contacts, and requested dates.
  3. Admins can click **"Download leads.json"** to export the entire inquiries database at any time.
  4. Features include searching/filtering, toggling status (*New* vs *Contacted*), and manual inquiry recording.
```

---

## 🌟 Key Pages & Features

### 1. Home (`index.html`)
- **Interactive Scene Switcher:** Dynamic hero section toggle between *Weddings*, *Corporate events*, and *Private celebrations*.
- **Core Offerings:** Visual service cards with direct deep-links into pre-filled planning briefs.
- **Three-Step Approach:** *Discover*, *Design*, and *Deliver* workflow highlights.
- **Quick Planning Brief Form:** Real-time live summary preview as fields are filled.

### 2. Services & Packages (`services.html`)
- **Event Breakdown:** Dedicated sections for *Weddings & Family Functions*, *Corporate Events*, *Social Celebrations*, *School & College Events*, *Festivals & Public Gatherings*, and *Custom Add-ons*.
- **Tiered Packages:** Indicative pricing and scope comparison for **Silver** (₹25,000+), **Gold** (₹1,00,000+), and **Platinum** (₹3,00,000+).

### 3. Event Gallery (`gallery.html`)
- **Filterable Portfolio:** Filter event concepts by *All*, *Weddings*, *Corporate*, and *Celebrations*.
- **Native `<dialog>` Lightbox:** Accessible, high-resolution modal image viewer with caption support, keyboard navigation (`Escape`), and backdrop dismissal.

### 4. Contact & Policies (`contact.html`)
- **Direct Communication:** Instant click-to-call, WhatsApp messaging link, and official email.
- **Accordion Booking Policies:** Expandable `<details>` sections covering quotes, payments, cancellations, and privacy terms.

### 5. Interactive Event Brief Builder (`assets/site.js`)
- **Live Summary Preview:** Form inputs dynamically generate an *"Your event at a glance"* summary line.
- **Dual Output Actions:**
  1. **Prepare WhatsApp Enquiry:** Automatically formats and encodes the entire brief into a ready-to-send WhatsApp message for `+91 96615 53705`.
  2. **Download Brief:** Generates and triggers an immediate download of `Ojasva-Events-Brief.txt` directly in the browser without any backend required.

---

## 🚀 How to Run the Website

Because this is a pure, static website without build tools or dependencies, you can run it in multiple ways:

### Option A: Direct File Open
Double-click `index.html` (or right-click → *Open With* → your favorite browser).

### Option B: Local Web Server (Recommended)

#### Using Python:
```bash
python -m http.server 8000
```
Open [http://localhost:8000](http://localhost:8000) in your browser.

#### Using Node.js (npx):
```bash
npx serve .
```

#### Using VS Code:
Right-click `index.html` and choose **"Open with Live Server"**.

---

## 📞 Official Contact & Business Details

- **Brand:** Ojasva Events
- **Phone:** `+91 96615 53705`
- **WhatsApp:** `+91 96615 53705`
- **Email:** `services@ojasvaevents.co.in`
- **HQ / Primary Hub:** Patna, Bihar (Serving Pan-India)

