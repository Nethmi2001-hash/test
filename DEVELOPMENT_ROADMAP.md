# 🚀 Monastery Healthcare System - Development Roadmap

## 📊 Project Status Overview

### ✅ COMPLETED MODULES
- [x] Authentication (login, logout, session management)
- [x] User Management
- [x] Role-Based Navigation (navbar)
- [x] Title Management
- [x] Category Management
- [x] Monk Registration
- [x] Doctor Availability
- [x] Patient Appointments
- [x] Room Management
- [x] Room Slot Management
- [x] Dashboard (basic)

### 🎯 REMAINING MODULES (Priority Order)

## PHASE 1: DATABASE & CORE INFRASTRUCTURE (Week 1)

### Task 1.1: Import Database Schema ✅ READY
**File Created:** `database_schema.sql`

**Action Steps:**
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Click "Import" tab
3. Choose `database_schema.sql` file
4. Click "Go"
5. Verify all 16 tables created successfully

**Tables Created:**
- roles, users, titles, categories
- monks, doctors, rooms, room_slots
- doctor_availability, appointments, medical_records
- donations, bills, audit_logs
- email_notifications, system_settings

### Task 1.2: Update Database Connection
**Update:** `mysql.php` → Rename to `db_config.php`

```php
<?php
// db_config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'monastery_healthcare');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("DB Connection failed: " . $conn->connect_error);
        die("Database connection failed. Please contact administrator.");
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}
?>
```

### Task 1.3: Create Common Functions Library
**Create:** `includes/functions.php`

---

## PHASE 2: DONATION MANAGEMENT (Week 2-3)

### Module 2.1: Bank/Cash Donations
**Create:** `donation_management.php`

**Features:**
- Record bank deposit/transfer with reference number
- Upload bank slip image (JPEG/PNG, max 5MB)
- Record cash donation with receipt number
- Admin verification workflow
- Status tracking (pending → verified → receipt sent)

**Database Fields:**
- method: 'bank' or 'cash'
- bank_reference, slip_path (for bank)
- receipt_number (for cash)
- status: pending/verified/paid

**UI Components:**
1. Donation entry form (donor info, amount, category, method)
2. Upload slip (for bank transfers)
3. Admin verification panel
4. Donation listing with filters

### Module 2.2: Sandbox Card Payment Integration
**Create:** `payment_gateway.php`, `payment_webhook.php`

**Gateway Options:**
- **PayHere** (Sri Lankan, best for LKR)
- **Stripe Test Mode** (international)

**Implementation Steps:**

**Step 1: Choose PayHere Sandbox**
1. Register at https://www.payhere.lk/
2. Get Sandbox credentials (Merchant ID, Secret)
3. Note: No real money in sandbox mode

**Step 2: Create Payment Flow**

```
User → donation_management.php → Choose "Card Payment"
  ↓
Generate order_id → Redirect to PayHere hosted checkout
  ↓
User enters test card → PayHere processes
  ↓
PayHere sends webhook → payment_webhook.php
  ↓
Verify signature → Update donation status → Send email receipt
```

**Files Needed:**
- `payment_gateway.php` - Generate checkout URL
- `payment_webhook.php` - Receive payment confirmation
- `payment_config.php` - Store credentials (use environment variables)

**Security:**
- NEVER store card numbers
- Only store: order_id, txn_ref, amount, status
- Verify HMAC signature on webhook
- Use HTTPS in production

---

## PHASE 3: BILLS & EXPENSES TRACKING (Week 4)

### Module 3.1: Bill Entry & Management
**Create:** `bill_management.php`

**Features:**
- Record utility bills (water, electricity)
- Record food/medicine expenses
- Upload invoice/bill PDF/images
- Category assignment
- Payment tracking (pending/paid)
- Monthly expense summaries

**Database Fields:**
- category_id (links to categories table)
- amount, bill_date, due_date
- vendor_name, invoice_number
- attachment_path (for bill scans)
- status (pending/paid/overdue)

**UI Components:**
1. Bill entry form
2. File upload (PDF/JPEG/PNG)
3. Bill listing with status filters
4. Payment recording

---

## PHASE 4: REPORTING SYSTEM (Week 5-6)

### Module 4.1: PDF Report Generation
**Create:** `reports/donation_report.php`

**Required Library:** TCPDF or Dompdf

**Installation:**
```bash
composer require tecnickcom/tcpdf
# OR
composer require dompdf/dompdf
```

**Reports to Create:**

**1. Monthly Donation vs. Expenditure Report**
- Date range selector (month/year)
- Donations by category (table + chart)
- Bills by category (table + chart)
- Net balance calculation
- Export as PDF

**2. Appointment Summary Report**
- Date range
- Doctor-wise statistics
- Status breakdown (completed/cancelled/no-show)
- Export as PDF/CSV

**3. Donor Transparency Report**
- Individual donor view
- All donations with dates
- How funds were used (mapped to bills)
- PDF download

### Module 4.2: CSV Export
**Create:** `reports/export_csv.php`

**Use:** PHPSpreadsheet or native fputcsv()

```php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="donations_' . date('Y-m-d') . '.csv"');
// Write CSV data
```

### Module 4.3: Dashboard Charts
**Update:** `dashboard.php`

**Add Charts (using Chart.js):**
1. Monthly donations bar chart
2. Expense breakdown pie chart
3. Appointment trends line chart
4. Recent donations table
5. Pending verifications alert tiles

**Include Chart.js:**
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

---

## PHASE 5: EMAIL NOTIFICATIONS (Week 7)

### Module 5.1: Email Configuration
**Create:** `includes/email_config.php`

**Library:** PHPMailer

**Installation:**
```bash
composer require phpmailer/phpmailer
```

**Setup SMTP:**
- Use Gmail SMTP (for testing)
- Later: Monastery email server

**Configuration:**
```php
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'app-password'; // Use App Password, not regular password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
```

### Module 5.2: Email Templates
**Create:** `email_templates/`

**Templates Needed:**
1. `donation_receipt.php` - Sent after donation verification
2. `appointment_reminder.php` - Sent 24hrs before appointment
3. `bill_paid_notification.php` - Sent when bill is paid

**Sample Receipt Email:**
```html
Dear [Donor Name],

Thank you for your generous donation to Giribawa Seela Suva Herath Bhikkhu Hospital.

Donation Details:
- Amount: LKR [amount]
- Date: [date]
- Category: [category]
- Method: [bank/cash/card]
- Receipt No: [number]

Your contribution helps provide healthcare services to our monks.

May you be blessed!
Giribawa Monastery
```

### Module 5.3: Automated Email Triggers
**Update:** Donation and appointment files to send emails

**Trigger Points:**
1. Donation verified → Send receipt
2. Appointment created → Send confirmation
3. 24hrs before appointment → Send reminder
4. Bill paid → Notify relevant parties

---

## PHASE 6: AUDIT LOGGING (Week 8)

### Module 6.1: Audit Log Implementation
**Create:** `includes/audit_logger.php`

**Log These Actions:**
- User login/logout
- Donation verification
- Medical record creation/update
- Bill payment recording
- User role changes

**Function Example:**
```php
function logAudit($user_id, $table, $record_id, $action, $changes) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO audit_logs 
        (user_id, table_name, record_id, action, changed_fields, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $agent = $_SERVER['HTTP_USER_AGENT'];
    $changes_json = json_encode($changes);
    
    $stmt->bind_param("isisss", $user_id, $table, $record_id, $action, 
                      $changes_json, $ip, $agent);
    $stmt->execute();
}
```

### Module 6.2: Audit Log Viewer
**Create:** `audit_logs.php` (Admin only)

**Features:**
- Date range filter
- Table filter
- User filter
- Action filter (create/update/delete)
- Export to CSV
- View detailed changes (JSON formatted)

---

## PHASE 7: MULTILINGUAL SUPPORT (i18n) (Week 9)

### Module 7.1: Language Structure Setup
**Create:** `languages/`

**Files:**
- `en.php` - English translations
- `si.php` - Sinhala translations

**Structure:**
```php
// languages/en.php
<?php
return [
    'dashboard' => 'Dashboard',
    'donations' => 'Donations',
    'appointments' => 'Appointments',
    'monks' => 'Monks',
    'doctors' => 'Doctors',
    'reports' => 'Reports',
    // ... more translations
];

// languages/si.php
<?php
return [
    'dashboard' => 'උපකරණ පුවරුව',
    'donations' => 'පරිත්‍යාග',
    'appointments' => 'හමුවීම්',
    'monks' => 'ස්වාමීන් වහන්සේලා',
    'doctors' => 'වෛද්‍යවරු',
    'reports' => 'වාර්තා',
    // ... more translations
];
```

### Module 7.2: Language Switcher
**Update:** `navbar.php`

**Add Language Dropdown:**
```php
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" role="button" 
       data-bs-toggle="dropdown">
        <?= $_SESSION['lang'] == 'si' ? 'සිංහල' : 'English' ?>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="?lang=en">English</a></li>
        <li><a class="dropdown-item" href="?lang=si">සිංහල</a></li>
    </ul>
</li>
```

### Module 7.3: Translation Function
**Create:** `includes/lang.php`

```php
<?php
session_start();

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Handle language change
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'si'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Load language file
$translations = include __DIR__ . '/../languages/' . $_SESSION['lang'] . '.php';

// Translation function
function t($key) {
    global $translations;
    return $translations[$key] ?? $key;
}
?>
```

**Usage in Pages:**
```php
<?php include 'includes/lang.php'; ?>
<h1><?= t('dashboard') ?></h1>
<a href="donations.php"><?= t('donations') ?></a>
```

---

## PHASE 8: TESTING & DOCUMENTATION (Week 10-12)

### Task 8.1: Security Testing
**Test Checklist:**
- [ ] SQL Injection (try ' OR '1'='1 in forms)
- [ ] XSS (try <script>alert('xss')</script> in inputs)
- [ ] CSRF (check all forms have tokens)
- [ ] Session fixation (logout properly clears session)
- [ ] File upload validation (only allow images/PDFs)
- [ ] Authorization (can users access admin pages?)
- [ ] Password strength (enforce minimum 8 chars)
- [ ] HTTPS redirect (in production)

### Task 8.2: Functional Testing
**Create Test Cases:**

**Donation Flow Test:**
1. Login as Donor
2. Record bank donation
3. Upload slip image
4. Check status = pending
5. Login as Admin
6. Verify donation
7. Check status = verified
8. Check email receipt sent

**Appointment Flow Test:**
1. Login as Helper
2. Select monk
3. Select doctor (check availability)
4. Select room slot
5. Book appointment
6. Check for conflicts
7. Verify email sent
8. Login as Doctor
9. View appointment
10. Mark as completed
11. Add medical record

### Task 8.3: Performance Testing
**Metrics:**
- Page load time < 2 seconds
- Report generation < 5 seconds (12 months data)
- Database query optimization (add indexes)
- Image compression (limit upload size)

### Task 8.4: User Documentation
**Create Manuals:**

**1. Admin User Manual**
- System setup
- User management
- Donation verification
- Report generation
- System settings

**2. Doctor User Manual**
- Login process
- View appointments
- Add medical records
- View monk history

**3. Helper User Manual**
- Register monks
- Book appointments
- Record donations (bank/cash)
- Manage room slots

**4. Donor Guide**
- How to donate
- Payment methods
- View donation history
- Download receipts

### Task 8.5: Technical Documentation
**Create:**
- Database schema diagram (ERD)
- API documentation (if any)
- Deployment guide
- Backup and recovery procedures
- Security best practices

---

## 📁 RECOMMENDED FILE STRUCTURE

```
test/
│
├── index.php (redirect to login or dashboard)
├── login.php ✅
├── logout.php (create this)
├── dashboard.php ✅ (update with charts)
├── navbar.php ✅
│
├── database_schema.sql ✅ NEW
├── db_config.php (rename from mysql.php)
│
├── modules/
│   ├── users/
│   │   ├── user_management.php ✅
│   │   ├── user_edit.php
│   │
│   ├── monks/
│   │   ├── monk_registration.php ✅
│   │   ├── monk_profile.php
│   │   ├── medical_history.php (new)
│   │
│   ├── doctors/
│   │   ├── doctor_management.php
│   │   ├── doctor_availability.php ✅
│   │
│   ├── appointments/
│   │   ├── patient_appointments.php ✅
│   │   ├── appointment_calendar.php (new)
│   │
│   ├── rooms/
│   │   ├── room_management.php ✅
│   │   ├── room_slot_management.php ✅
│   │
│   ├── donations/
│   │   ├── donation_management.php (NEW - PHASE 2)
│   │   ├── donation_verification.php (NEW)
│   │   ├── payment_gateway.php (NEW - sandbox)
│   │   ├── payment_webhook.php (NEW)
│   │
│   ├── bills/
│   │   ├── bill_management.php (NEW - PHASE 3)
│   │   ├── bill_payment.php (NEW)
│   │
│   ├── reports/
│   │   ├── donation_report.php (NEW - PHASE 4)
│   │   ├── expense_report.php (NEW)
│   │   ├── appointment_report.php (NEW)
│   │   ├── export_csv.php (NEW)
│   │   ├── export_pdf.php (NEW)
│   │
│   └── settings/
│       ├── category_management.php ✅
│       ├── title_management.php ✅
│       ├── system_settings.php (NEW)
│       ├── audit_logs.php (NEW - PHASE 6)
│
├── includes/
│   ├── db_config.php (NEW)
│   ├── functions.php (NEW)
│   ├── auth_check.php (NEW - session validation)
│   ├── csrf.php (NEW - CSRF token generation)
│   ├── audit_logger.php (NEW - PHASE 6)
│   ├── email_config.php (NEW - PHASE 5)
│   └── lang.php (NEW - PHASE 7)
│
├── languages/
│   ├── en.php (NEW - PHASE 7)
│   └── si.php (NEW - PHASE 7)
│
├── email_templates/
│   ├── donation_receipt.php (NEW - PHASE 5)
│   ├── appointment_reminder.php (NEW)
│   └── bill_notification.php (NEW)
│
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── custom.css
│   ├── js/
│   │   ├── main.js
│   │   └── charts.js (NEW - Chart.js integration)
│   └── images/
│
├── uploads/
│   ├── bank_slips/ (NEW - PHASE 2)
│   ├── bills/ (NEW - PHASE 3)
│   └── invoices/
│
├── vendor/ (Composer packages)
│   ├── phpmailer/
│   ├── tcpdf/ or dompdf/
│   └── autoload.php
│
├── composer.json (NEW - for dependencies)
└── README.md

```

---

## ⚙️ SETUP INSTRUCTIONS

### Step 1: Install Composer (for PHP dependencies)
1. Download from https://getcomposer.org/download/
2. Install globally
3. Verify: `composer --version`

### Step 2: Create composer.json
```json
{
    "require": {
        "phpmailer/phpmailer": "^6.8",
        "tecnickcom/tcpdf": "^6.6",
        "phpoffice/phpspreadsheet": "^1.29"
    }
}
```

### Step 3: Install Dependencies
```bash
cd c:\xamp\htdocs\test
composer install
```

### Step 4: Import Database
1. Open phpMyAdmin
2. Import `database_schema.sql`
3. Default login: admin@monastery.lk / admin123

### Step 5: Configure Email (Optional for testing)
- Use Mailtrap.io (fake SMTP for testing)
- OR Gmail with App Password
- Update `includes/email_config.php`

### Step 6: Test Sandbox Payment
- Register PayHere sandbox account
- Get test credentials
- Use test card: 4111 1111 1111 1111

---

## 🎯 WEEKLY SPRINT PLAN

### Week 1: Database & Infrastructure
- [ ] Import database schema
- [ ] Update db_config.php
- [ ] Create includes/functions.php
- [ ] Create logout.php
- [ ] Test all existing modules with new DB

### Week 2: Donation Management (Bank/Cash)
- [ ] Create donation_management.php
- [ ] Implement bank deposit entry
- [ ] Implement cash donation entry
- [ ] File upload for bank slips
- [ ] Admin verification workflow

### Week 3: Sandbox Payment Integration
- [ ] Choose payment gateway (PayHere)
- [ ] Setup sandbox account
- [ ] Implement hosted checkout
- [ ] Implement webhook receiver
- [ ] Test with test cards

### Week 4: Bills & Expenses
- [ ] Create bill_management.php
- [ ] Bill entry form
- [ ] File upload for invoices
- [ ] Payment tracking
- [ ] Monthly summaries

### Week 5: Reporting - Part 1
- [ ] Install TCPDF/Dompdf
- [ ] Create donation vs expense report
- [ ] Implement PDF generation
- [ ] Add date range filters

### Week 6: Reporting - Part 2
- [ ] Appointment summary report
- [ ] CSV export functionality
- [ ] Update dashboard with Chart.js
- [ ] Add statistical tiles

### Week 7: Email Notifications
- [ ] Install PHPMailer
- [ ] Configure SMTP
- [ ] Create email templates
- [ ] Implement receipt emails
- [ ] Implement appointment reminders

### Week 8: Audit Logging
- [ ] Create audit_logger.php
- [ ] Add logging to critical actions
- [ ] Create audit_logs.php viewer
- [ ] Add filters and export

### Week 9: Multilingual Support
- [ ] Create language files (en, si)
- [ ] Implement translation function
- [ ] Add language switcher
- [ ] Translate all pages

### Week 10-12: Testing & Documentation
- [ ] Security testing
- [ ] Functional testing
- [ ] Performance optimization
- [ ] Write user manuals
- [ ] Write technical documentation
- [ ] Final demo preparation

---

## 🔒 SECURITY CHECKLIST

- [ ] All passwords hashed with bcrypt/argon2
- [ ] Prepared statements for ALL queries (no string concatenation)
- [ ] CSRF tokens on all forms
- [ ] XSS prevention (htmlspecialchars on all output)
- [ ] File upload validation (type, size, extension)
- [ ] Session timeout (30 minutes inactivity)
- [ ] Role-based access control on every page
- [ ] Audit logging for sensitive actions
- [ ] HTTPS in production
- [ ] No sensitive data in error messages
- [ ] Input validation (server-side)
- [ ] No card data stored (only references)
- [ ] HMAC signature verification on webhooks
- [ ] Environment variables for secrets (.env file)

---

## 📊 SUCCESS CRITERIA (from PID)

### Performance
- [ ] Page load < 2 seconds
- [ ] Report generation < 5 seconds
- [ ] Support 100+ monks, 600+ appointments

### Functionality
- [ ] Monk history in < 3 clicks
- [ ] Appointment booking in < 5 clicks
- [ ] Donation verification workflow complete
- [ ] PDF/CSV reports accurate (zero variance)
- [ ] Email receipts < 1 minute
- [ ] Sandbox payment flow end-to-end

### Security
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] Authorization enforced
- [ ] Audit logs complete

### Usability
- [ ] Responsive design (mobile + desktop)
- [ ] SUS score > 70 (from 6-10 pilot users)
- [ ] Clear error messages
- [ ] Bilingual support ready

---

## 🎓 LEARNING RESOURCES

### Payment Gateway Integration
- PayHere Docs: https://support.payhere.lk/api-&-mobile-sdk/
- Stripe Test Mode: https://stripe.com/docs/testing

### PDF Generation
- TCPDF: https://tcpdf.org/examples/
- Dompdf: https://github.com/dompdf/dompdf

### Email
- PHPMailer: https://github.com/PHPMailer/PHPMailer
- Gmail SMTP: https://support.google.com/mail/answer/7126229

### Charts
- Chart.js: https://www.chartjs.org/docs/latest/

### Security
- OWASP Top 10: https://owasp.org/www-project-top-ten/
- PHP Security Guide: https://www.php.net/manual/en/security.php

---

## 📞 SUPPORT & QUESTIONS

### If you get stuck:
1. Check PHP error log: `c:\xampp\php\logs\php_error_log`
2. Check Apache error log: `c:\xampp\apache\logs\error.log`
3. Enable error display (development only):
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
4. Use var_dump() for debugging
5. Check browser console for JavaScript errors

---

## 🎉 FINAL DELIVERABLES

1. ✅ Fully functional web application
2. ✅ Database with sample data
3. ✅ User manuals (Admin, Doctor, Helper, Donor)
4. ✅ Technical documentation
5. ✅ Test report (security, functional, performance)
6. ✅ Source code with comments
7. ✅ Deployment guide
8. ✅ Final project report (PID format)
9. ✅ Demo video (10-15 minutes)
10. ✅ Supervisor approval

---

**Good luck with your final year project! 🙏**
**May your code be bug-free and your compilation successful! 🚀**
