# SISTEM PENGURUSAN INVOIS & KEWANGAN DIGITAL
## AI Smart Scan Edition · PHP + MySQL

Sistem lengkap untuk pengurusan invois, pembayaran, kewangan, dan pengimbasan resit secara automatik menggunakan OCR.

---

## SETUP (XAMPP)

### 1. Letakkan folder
Copy seluruh folder `invois` ke:
```
C:\xampp\htdocs\invois\
```

### 2. Mulakan Apache + MySQL
Buka **XAMPP Control Panel**, klik **Start** untuk Apache dan MySQL.

### 3. Import database
1. Buka http://localhost/phpmyadmin/
2. Klik tab **Import**
3. Pilih fail: `database/invois.sql`
4. Klik **Go**

Database `invois` dan semua jadual akan dicipta automatik dengan data demo.

### 4. Konfigurasi (jika perlu)
Edit `includes/config.php` jika tetapan MySQL anda berbeza:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'invois');
```

### 5. Permission folder upload
Pastikan folder `uploads/` boleh ditulis (writable).

### 6. Akses sistem
Buka: **http://localhost/invois/**

---

## LOGIN DEFAULT

| Role  | Email              | Password  |
|-------|--------------------|-----------|
| Admin | admin@invois.com   | admin123  |
| Staff | staff@invois.com   | admin123  |

> **Tukar password segera selepas login pertama.**

---

## MODUL TERSEDIA

1. **Dashboard** — KPI Cards, Revenue/Expenses chart, Recent Invoices, Reminders
2. **Invoices** — CRUD, search & filter, document upload, PDF print
3. **Payments** — Record, auto-update status invois, payment history
4. **Clients & Vendors** — Pengurusan rakan kongsi + transaction summary
5. **Expenses** — Categorized + budget tracker + receipt upload
6. **AI Smart Scan (OCR)** — Auto-extract amount/date/vendor/invoice # dari resit
7. **Reports** — Invoice, Payment, Expenses, Outstanding, Revenue + CSV export
8. **Users** — Admin sahaja, role-based access
9. **Notifications & Audit Trail** — Log aktiviti penuh

---

## AI SMART SCAN (OCR)

Modul OCR menggunakan **Tesseract** (jika dipasang) untuk imbasan imej.

**Install Tesseract (Windows):**
1. Download: https://github.com/UB-Mannheim/tesseract/wiki
2. Install ke `C:\Program Files\Tesseract-OCR\`
3. Tambah ke PATH environment variable
4. Restart Apache

Jika Tesseract tiada, sistem akan tunjukkan mesej dan benarkan input manual.

---

## SECURITY

- PDO Prepared Statements (SQL Injection protection)
- CSRF token pada semua form
- Password hashing (bcrypt)
- Session security (httponly, regenerate ID)
- File upload validation (type + size)
- XSS protection via `e()` helper
- Audit logging untuk semua aktiviti penting
- Role-based access control

---

## STRUKTUR FOLDER

```
invois/
├── index.php              # Auto-redirect
├── login.php / logout.php
├── dashboard.php
├── invoices/              # CRUD invois + PDF
├── payments/              # Pengurusan pembayaran
├── clients/               # Client & Vendor
├── expenses/              # Expenses + budget
├── ai-ocr/                # AI Smart Scan
├── reports/               # Laporan + export
├── users/                 # User management
├── uploads/               # Stored files
├── includes/              # Core: config, db, auth, helpers
├── assets/css|js|img/
└── database/invois.sql
```

---

## NOTA TEKNIKAL

- **PHP**: Murni, tanpa Composer/framework
- **PDO MySQL**: Database layer
- **Bootstrap 5.3 + Bootstrap Icons + Chart.js**: Via CDN
- **Inter font**: Via Google Fonts CDN
- **PHP version**: 7.4+ (8.x disyorkan)

Sistem siap untuk production-ready dengan modifikasi minimal.


<div align="center">

# 🧾 Invois

### Invoice & Financial Management System with AI Smart Scan

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-22c55e?style=flat-square)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-22c55e?style=flat-square)]()

**Manage invoices, record payments, track expenses, and scan receipts automatically** — all in one lightweight system with zero framework dependencies.

[📦 Installation](#️-installation) · [📖 Modules](#-available-modules) · [🐛 Report a Bug](issues) · [✨ Request a Feature](issues)

---

</div>

## ✨ Why Invois?

Most invoice systems are either too complex or too expensive. **Invois** is built for small and medium businesses that need a system that is:

- 🚀 **Lightweight** — Pure PHP, no Composer, no framework
- 🤖 **Smart** — AI Smart Scan reads receipts automatically
- 🔒 **Secure** — CSRF, XSS, and SQL Injection protection built-in
- 📊 **Complete** — From invoices to financial reports, everything is covered

---

## 🤖 AI Smart Scan (Highlight Feature)

Upload a receipt image or PDF → the system will **auto-extract** key information:

| Field | Example Output |
|---|---|
| 🏪 Vendor Name | `Kedai Maju Jaya Sdn Bhd` |
| 📅 Date | `2024-01-15` |
| 💰 Amount | `RM 234.50` |
| 🔢 Invoice No. | `INV-2024-0089` |

> Powered by **Tesseract OCR** with automatic duplicate detection.

---

## 📦 Available Modules

| Module | Description |
|---|---|
| 📊 **Dashboard** | KPI cards, revenue/expense charts, reminders |
| 🧾 **Invoices** | Create, edit, view, print to PDF, document upload |
| 💳 **Payments** | Record payments, auto-update invoice status |
| 👥 **Clients & Vendors** | Partner management + transaction summary |
| 💸 **Expenses** | Categorized expenses + budget tracker |
| 🤖 **AI Smart Scan** | OCR auto-extract from receipts & invoices |
| 📈 **Reports** | Invoice, payment, expense reports + CSV export |
| 👤 **Users** | Role-based access control (Admin / Staff) |
| 📋 **Audit Trail** | Full activity log for all system actions |

---

## ⚙️ Installation

### Requirements
- PHP 7.4+ (8.x recommended)
- MySQL 5.7+ / MariaDB 10+
- XAMPP / Laragon / any local web server

### Setup Steps

**1. Clone or download the project**
```bash
git clone https://github.com/username/invois.git
```
Or download the ZIP and extract it to:
```
C:\xampp\htdocs\invois\
```

**2. Import the database**
```
1. Open http://localhost/phpmyadmin
2. Go to the "Import" tab → Select file: database/invois.sql
3. Click "Go"
```

**3. Configure the connection (if needed)**

Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'invois');
```

**4. Install Tesseract OCR** *(required for AI Smart Scan)*
```
Download: https://github.com/UB-Mannheim/tesseract/wiki
Install to: C:\Program Files\Tesseract-OCR\
Add to PATH environment variable → Restart Apache
```

**5. Access the system**
```
http://localhost/invois/
```

---

## 🔐 Default Login Credentials

| Role | Email | Password |
|---|---|---|
| Admin | admin@invois.com | admin123 |
| Staff | staff@invois.com | admin123 |

> ⚠️ **Change your password immediately after the first login!**

---

## 🛡️ Security

This system includes multiple layers of security out of the box:

- ✅ **PDO Prepared Statements** — SQL Injection protection
- ✅ **CSRF Tokens** — Applied to all forms
- ✅ **bcrypt Password Hashing** — Industry standard
- ✅ **Session Security** — httponly cookies, session ID regeneration
- ✅ **File Upload Validation** — File type & size enforcement
- ✅ **XSS Protection** — Via the `e()` helper function
- ✅ **Role-Based Access Control** — Admin vs Staff permissions
- ✅ **Audit Logging** — Every important action is recorded

---

## 🏗️ Project Structure

```
invois/
├── 📄 index.php              # Auto-redirect
├── 📄 login.php / logout.php
├── 📄 dashboard.php
├── 📁 invoices/              # Invoice CRUD + PDF print
├── 📁 payments/              # Payment management
├── 📁 clients/               # Clients & vendors
├── 📁 expenses/              # Expenses + budget
├── 📁 ai-ocr/                # AI Smart Scan (OCR)
├── 📁 reports/               # Reports + CSV export
├── 📁 users/                 # User management
├── 📁 audit/                 # Activity logs
├── 📁 uploads/               # Stored files
├── 📁 includes/              # Config, DB, auth, helpers
├── 📁 assets/                # CSS, JS, images
└── 📁 database/
    └── 📄 invois.sql         # Schema + demo data
```

---

## 🧰 Tech Stack

| Technology | Role |
|---|---|
| PHP (Pure) | Backend, no framework |
| MySQL / PDO | Database layer |
| Bootstrap 5.3 | UI & responsive layout |
| Bootstrap Icons | Icon set |
| Chart.js | Charts & data visualization |
| Tesseract OCR | Receipt scanning |
| Inter Font | Typography |

---

## 🤝 Contributing

Contributions are welcome! Here's how:

1. Fork this repository
2. Create a new branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -m 'Add some feature'`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Open a Pull Request

---

## 📄 License

Distributed under the MIT License. See [`LICENSE`](LICENSE) for more information.

---

<div align="center">

Built with ❤️ for small businesses

⭐ **Star this repo if it helped you!** ⭐

</div>

