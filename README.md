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
