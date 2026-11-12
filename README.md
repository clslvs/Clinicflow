# ClinicFlow

ClinicFlow is a dental clinic management web application for NgipinSolution Biñan. It provides separate portals for patients, staff, doctors, and administrators using PHP 8+, MySQL or MariaDB via PDO, and vanilla HTML, CSS, and JavaScript with no build step.

## Portals and features

### Patient portal (`patient/`)

Patients can book appointments by service, dentist, and available time slot (`book-appointment.html`), check their live queue position (`queue-tracker.html`), view, reschedule, or cancel bookings (`my-appointments.html`), and update personal details and medical disclosures such as allergies (`settings.html`).

### Staff portal (`staff/`)

Staff manage the daily walk-in and scheduled queue (`queue.html`), review weekly schedules (`schedules.html`), search patient records and visit history (`records.html`), and toggle priority status for senior citizens or persons with disabilities.

### Doctor portal (`doctor/`)

Doctors can review their assigned patients and prior visit records (`my-patients.html`), add consultation notes, view their schedule (`my-schedule.html`), and manage account settings (`settings.html`).

### Admin portal (`admin/`)

Administrators can search and update appointments across the clinic (`appointments.html`), create or deactivate accounts with a two-step permanent deletion flow that anonymizes user data while keeping medical history (`users.html`), and configure clinic hours, closed days, services, doctor profiles with avatar uploads, and Monday to Saturday doctor availability (`settings.html`).

Public pages at the repository root include the landing page with live queue status (`index.html`), role-aware sign-in and password reset (`login.html`), patient registration (`register.html`), and terms, privacy, and cookie notices (`legal.html`).

## Tech stack

- Frontend: HTML5, CSS3 (`tokens.css`, `base.css`, and per-portal stylesheets), vanilla ES6+ JavaScript (`Fetch API`), and bundled Lucide icons (`assets/js/lucide.min.js`).
- Backend: Procedural PHP 8+ JSON endpoints with session authentication and PDO prepared statements.
- Database: MySQL or MariaDB (`backend/database.sql`).
- Email: Brevo REST API over HTTPS (`backend/forgot_password.php`) for password reset codes on hosts that block SMTP ports.

## Local setup

### Requirements

- Laragon (Apache and MySQL) or an equivalent PHP 8+ and MySQL/MariaDB stack
- A web browser

### Steps

1. Clone or extract this repository into your Laragon web root (for example `E:\laragon\www\clinicflow`).
2. Start Apache and MySQL from Laragon.
3. Import `backend/database.sql` in phpMyAdmin or the MySQL CLI to create the `clinicflow` database, schema, default settings, and sample accounts. If you are updating an older database, run `setup_db.sql` as well.
4. Copy `backend/config.local.sample.php` to `backend/config.local.php` and fill in `BREVO_API_KEY`, `SENDER_EMAIL`, and `DB_PASS` for production. Local Laragon defaults (`127.0.0.1`, database `clinicflow`, user `root`, blank password) work without editing `backend/db.php`.
5. Open `http://clinicflow.test/` or `http://localhost/clinicflow/` in your browser.

## Default accounts

All accounts seeded by `backend/database.sql` use the password `admin123`. Change or remove them before deploying to production.

| Role | Email | Password |
|---|---|---|
| Admin | `admin@clinicflow.com` | `admin123` |
| Staff | `staff@clinicflow.com` | `admin123` |
| Doctor | `doctor@clinicflow.com` | `admin123` |
| Patient | `patient@clinicflow.com` | `admin123` |

## Project structure

```text
clinicflow/
├── admin/               # Admin appointments, user management, and settings
├── assets/              # Stylesheets, client scripts, Lucide bundle, and images
│   ├── css/
│   ├── images/
│   └── js/
├── backend/             # PHP endpoints, database schema, and local config sample
│   ├── config.local.sample.php
│   ├── database.sql
│   ├── db.php
│   └── *.php
├── doctor/              # Doctor dashboard, patients, schedule, and settings
├── patient/             # Patient dashboard, booking, appointments, queue, settings
├── staff/               # Staff dashboard, queue, records, schedules, settings
├── base.css             # Shared base styles
├── tokens.css           # Design tokens
├── index.html           # Public landing page
├── legal.html           # Terms, privacy policy, and cookie policy
├── login.html           # Sign-in and password reset
├── register.html        # Patient registration
└── setup_db.sql         # Incremental schema and seed script
```

## License

For educational and dental clinic management use.
