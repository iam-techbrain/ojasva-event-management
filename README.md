# Ojasva Events — Event Planning & Design

A modern, fast, and accessible website for **Ojasva Events**, powered by a lightweight **PHP & SQLite** backend for inquiries and admin lead management.

---

## 📌 Project Overview

- **Brand:** Ojasva Events
- **Tagline:** *Make the moment unforgettable.*
- **Backend Stack:** PHP 8.x + SQLite 3 (`PDO_SQLITE`)
- **Frontend Stack:** Semantic HTML5, Modular CSS3, Vanilla ES6 JavaScript
- **Database:** `database.sqlite` (Tables: `login`, `leads`)

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
├── contact.html                  # Contact information, booking policies & inquiry form
├── gallery.html                  # Categorized event gallery with modal lightbox
├── index.html                    # Homepage with scene switcher, services & planning brief
├── services.html                 # Detailed event categories & Silver/Gold/Platinum packages
├── db.php                        # SQLite PDO connection & auto-table creator
├── database.sqlite               # SQLite Database file (login & leads tables)
├── submit.php                    # Form submission handler (stores directly in SQLite)
├── login.php                     # Secure PHP admin authentication portal
├── dashboard.php                 # PHP Admin Leads & Enquiries Dashboard
├── logout.php                    # Admin session logout handler
├── start-server.bat              # 1-click Windows launcher for PHP SQLite server
└── README.md                     # Documentation and setup guide
```

---

## 🗄️ SQLite Database Schema

The database file is `database.sqlite` and contains two main tables:

### 1. `login` Table (Admin Authentication)
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER PRIMARY KEY | Auto-incremented user ID |
| `username` | TEXT UNIQUE | Admin Login ID (`admin@123`) |
| `password` | TEXT | Password hash (default: `admin`) |
| `role` | TEXT | Role (`admin`) |
| `created_at` | DATETIME | Account creation timestamp |

### 2. `leads` Table (Customer Inquiries)
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INTEGER PRIMARY KEY | Auto-incremented inquiry ID |
| `name` | TEXT | Client full name |
| `phone` | TEXT | Client phone number |
| `email` | TEXT | Client email address |
| `event_type` | TEXT | Event category (Wedding, Corporate, etc.) |
| `event_date` | TEXT | Requested date |
| `venue` | TEXT | City or venue |
| `guests` | TEXT | Estimated guest count |
| `package` | TEXT | Selected package (Silver, Gold, Platinum, Custom) |
| `budget` | TEXT | Budget in INR |
| `details` | TEXT | Customer notes and special vision |
| `status` | TEXT | Status (`New` / `Contacted`) |
| `created_at` | DATETIME | Submission timestamp |

---

## 🔐 Admin Portal & Leads Dashboard (PHP)

- **Login Page:** [`login.php`](http://localhost:8000/login.php)
- **Dashboard URL:** [`dashboard.php`](http://localhost:8000/dashboard.php)
- **Credentials:**
  - **Login ID:** `admin@123`
  - **Password:** `admin`
- **Dashboard Capabilities:**
  1. **Live Lead Counter:** Real-time metrics from SQLite for Total Inquiries, Weddings, Corporate, and Celebrations.
  2. **Inquiry Table Feed:** Search by client name, email, phone, venue, or filter by event type.
  3. **Status Toggle:** Click any status pill to toggle between *● New* and *✓ Contacted*.
  4. **View Details Dialog:** Modal popup to view full client messages and notes.
  5. **Delete Action:** Remove old or test leads from SQLite with confirmation.
  6. **1-Click CSV Export:** Export and download leads for Excel (`ojasva-leads.csv`).
  7. **Manual Entry Drawer:** Form to record walk-in or telephone leads directly into the database.

---

## 🚀 How to Run the Project (PHP Server)

### Option 1: One-Click Windows Launcher
Double-click **`start-server.bat`** in the project folder.

### Option 2: Command Line
Run in PowerShell or Command Prompt:
```bash
php -S localhost:8000
```

Then visit:
- **Website:** [http://localhost:8000](http://localhost:8000)
- **Admin Login:** [http://localhost:8000/login.php](http://localhost:8000/login.php)
- **Admin Dashboard:** [http://localhost:8000/dashboard.php](http://localhost:8000/dashboard.php)
