# ScholarMatch — Scholarship Discovery and Alerts Platform

ScholarMatch is a production-ready SaaS platform built using clean, vanilla Core PHP and MySQL for personalized scholarship matching and automated notifications via WhatsApp and email.

---

### **STEP 1 STATUS: VERIFIED COMPLETE**
- **Front Controller Bootstrap**: Root `index.php` boots configurations, sets secure session cookies, registers headers, and routes clean URLs.
- **Service Layer**: Centralized PDO database connections, redacted error logging, and clean URL dynamic routing.
- **Responsive Layout**: Validated brand landing grid across viewport widths from desktop down to `280px` mobile sizes.

### **STEP 2 STATUS: VERIFIED COMPLETE**
- **SaaS Database Schema**: 33 fully normalized InnoDB tables supporting profiles, academic lists, matching parameters, transactions, and audit logs.
- **Integrity Constraints**: Complete foreign key mappings (with cascading deletion checks and RESTRICT blockages), unique keys, and index keys.
- **CLI Migration & Seeder Engine**: Zero-dependency schema updater and idempotent seeding runners.
- **Local Database Self-Healing**: Dynamic MySQL auto-creation of missing databases at first launch.

### **STEP 3 STATUS: VERIFIED COMPLETE**
- **User Authentication Flow**: Secure `/register`, `/login`, and `/logout` POST controllers with password confirmation and country options loaded dynamically from the database.
- **Authorization Gating**: Clean `Auth::requireRole` and `Auth::requirePermission` guards.
- **Password Hardening**: Implemented BCrypt storage (`PASSWORD_BCRYPT`) and session fixation rotation.
- **Brute Force Rate Limiter**: Database-backed login attempt throttling (blocks users after 5 failed tries in 15 minutes).
- **Persistent Logins**: Selector/validator cookie pattern with automated token rotation and replay theft prevention.
- **Forgot Password Flow**: Secure recovery pipeline at `/forgot-password` with hashed tokens. Shows local-only reset bypass.
- **Secure Views**: Fully responsive login, registration, recovery, and dashboard views (Visitor, Employee, Admin panels) wrapping XSS output variables using `e()`.

### **STEP 4 STATUS: VERIFIED COMPLETE**
- **Applicant Profile Details**: Dynamic profile display and tabbed updates for personal details, short bios, nationalities, and residence locations.
- **Dynamic Age Calculator**: Reusable helper calculates age on renders based on saved birthdates.
- **Normalized Preference Mappings**: Mapped destination countries, study fields, and degree target configurations via many-to-many lookup tables.
- **Education history manager**: Mapped degree level trackers (with GPA scales and validation limits) enforcing single-current designations inside database transactions.
- **AJAX Cascading Locations**: Cascade selectors (Country -> State -> City) using fetch queries.
- **Profile Completeness Engine**: `ProfileCompletionService` calculates progress metrics based on weight factors (Personal, Location, Academic, Preferences).
- **Hardened Guards**: Strict CSRF tokens, whitelist mass-assignment blocks, and IDOR protection on education updates.

### **STEP 5 STATUS: VERIFIED COMPLETE**
- **Normalized DB Pivots**: Study countries, fields, degree targets, eligible nationalities, and languages are fully normalized using pivot mapping tables.
- **Scholarship Lifecycle States**: Configured draft, pending review, published, and archived states.
- **Admin CRUD Management**: Renders paginated listings with search, filter, and sorting controls for admin/employees.
- **Unique SEO Slugs**: Auto-generates unique hyphenated slugs, incrementing counts to prevent duplicates.
- **Duplicate Prevention**: Evaluates titles, providers, and URLs before saving to block redundancy.
- **Dynamic Closing Checks**: Computes deadline indicators (Open, Closing Soon, Deadline Passed) on view renders.
- **Public Opportunities Portal**: Mapped `/scholarships` filter list and `/scholarships/{slug}` details page. Gated private drafts.
- **XSS & URL Sanitizers**: Strips malicious event handlers from rich descriptions and restricts link schemes.
- **Audit Trails Trail**: Populates module audit logs for all administrative transitions.

### **STEP 6 STATUS: VERIFIED COMPLETE**
- **Personalized Matching Service**: Implemented deterministic score weighting and rule checking in `ScholarshipMatchingService`.
- **GPA & Age Normalization**: Evaluates diverse academic GPA scales dynamically and calculates student age from DOB values.
- **Dashboard Recommendations**: Dynamic matching cards populated with matched, failed, and missing details. Includes status filters and sorting allowlists.
- **"Your Match" Detail Section**: Inline evaluation box rendered for authenticated users, with registration CTAs for guests.
- **Admin Diagnostics Tool**: Dynamic test panel at `/admin/matches/test` allowing managers to trace criteria matching step-by-step.
- **Match Invalidation**: Profile, education, or preference updates trigger lazy-cache invalidations of stale results automatically.
- **Performance Scale**: Benchmark verifying N+1 safety and execution limits (matches 500 records in ~5.6 seconds).

### **STEP 7 STATUS: VERIFIED COMPLETE**
- **Centralized Outbox Architecture**: Implemented `NotificationQueueService` mapping email/WhatsApp alerts with unique database constraint idempotency.
- **Provider-Independent Transports**: Integrated `EmailNotificationService` (with SMTP socket-level EHLO/AUTH operations and local file log backup) and `WhatsAppNotificationService` supporting provider selection.
- **CLI Cron Task Runners**: Created `cron/daily_matches.php` and `cron/deadline_reminders.php` batching executions to avoid loop memory overhead.
- **Staff Control Dashboards**: Created `NotificationController` and paginated log center panel supporting channel/status filtering and manual CSRF-guarded retry actions.
- **Scale Benchmarks**: Added tests enqueuing and processing 500 records on 1,000 mock scholarships, processing batch transfers in ~6.5 seconds.

---

## 1. Technology Stack & Requirements

- **PHP Version**: `^8.0` (Local verified: `8.3.30`)
- **Database**: MySQL `^8.0` (with UTF-8/utf8mb4 collation)
- **Dependency Management**: Composer `^2.0`
- **Web Server**: Apache (with `mod_rewrite` enabled for Clean URL routing) or Nginx

---

## 2. Project Architecture

The codebase follows a structured Model-View-Controller (MVC) and service-oriented directory format:

```text
/
├── app/
│   ├── Controllers/         # Application entry handlers (HomeController, etc.)
│   ├── Models/              # Database models (BaseModel, etc.)
│   ├── Services/            # Business services (Database wrapper, Logger, Router)
│   ├── Helpers/             # Helper classes (Security, View template compiler)
│   ├── Middleware/          # Routing & access control middleware
│   └── Validation/          # Form and parameter validator engines
│
├── assets/                  # Public resources (css, js, images, icons)
│   ├── css/style.css        # Minified style layout
│   └── js/main.js           # Core JS behaviors (Lucide icons, menu locks, FAQ toggle)
│
├── config/                  # Configuration layers loading parameters from .env
│   ├── app.php
│   ├── database.php
│   ├── mail.php
│   ├── whatsapp.php
│   └── payment.php
│
├── database/                # Database migrations & seeds
│   ├── migrations/
│   └── seeders/
│
├── routes/                  # Centralized HTTP request routing
│   └── web.php
│
├── storage/                 # Local uploads, logs, and caching files (ignored by Git)
│   ├── logs/
│   ├── cache/
│   └── uploads/
│
├── tests/                   # Regression and unit smoke testing suite
│   ├── run.php
│   └── *Test.php
│
├── .env                     # Local configuration variables (ignored by Git)
├── .env.example             # Configuration templates for other environments
├── .htaccess                # Apache routing rewriting rules & public access security
├── composer.json            # Application package requirements
└── README.md                # Platform documentation
```

---

## 3. Local Installation & Setup

Follow these steps to set up the platform locally:

### Step A: Clone & Prepare Workspace
If clone path is inside Laragon (e.g. `C:\laragon\www\scholarship`), the site will automatically map to `http://scholarship.test/`.

### Step B: Dependencies Installation
Run the following command at the project root to install core packages (like `phpdotenv`):
```bash
composer install
```

### Step C: Environment Setup
Copy the configuration template to establish local properties:
```bash
copy .env.example .env
```
Open `.env` and fill in your local MySQL credentials:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scholarship
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

### Step D: Database Initialization
1. Ensure your local MySQL server (like Laragon's MariaDB/MySQL) is running.
2. Create a database named `scholarship` with charset `utf8mb4`:
```sql
CREATE DATABASE scholarship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 4. Development vs Production Modes

We configure environment behaviors based on the `APP_DEBUG` and `APP_ENV` keys inside the `.env` file.

### Development Mode (`.env`)
```env
APP_ENV=local
APP_DEBUG=true
```
- Custom details are displayed in 500 error pages.
- Log traces write descriptive database query details to `storage/logs/app.log`.

### Production Mode (`.env`)
```env
APP_ENV=production
APP_DEBUG=false
```
- Detailed SQL exceptions and errors are suppressed. Users receive a generic error message.
- Session configurations enforce secure cookies (`cookie_secure` will automatically trigger when accessed via HTTPS).

---

## 5. Testing the Application

We use a custom, zero-dependency PHP smoke-testing suite. To run the automated checks, execute the following command at the project root:

```bash
php tests/run.php
```

The runner executes:
1. **ConfigTest**: Checks that central config values and fallback limits parse successfully.
2. **DatabaseTest**: Checks central database connection, error-trapping routines, and collation.
3. **HomepageTest**: Validates directory presence, asset directories, compiles the Homepage view, and verifies essential layout landmarks.

---

## 6. Security Notes

- **Credential Hiding**: Never hardcode API passwords, WhatsApp access tokens, or Easypaisa keys inside code files. Read them dynamically through the central `config()` helper, which reads variables initialized by `vlucas/phpdotenv`.
- **Public Folder Lock**: The `.htaccess` file blocks direct browser access to `.env`, `.git`, `composer.json`, and `storage/` directories in Apache. It also disables index directory listings globally (`Options -Indexes`).
- **Data Protection**:
  - Central `Logger` automatically redacts sensitive parameter values matching credential keys (like `password`, `key`, `token`) from log arrays before writing to `storage/logs/app.log`.
  - Database inputs are executed strictly via PDO prepared statements to mitigate SQL injection vectors.
  - Session cookies are set to `HttpOnly` and `SameSite=Lax` to protect against XSS session hijacking.
