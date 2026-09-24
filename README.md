# 🦷 ClinicFlow - Dental Clinic Management System

ClinicFlow is a modern, responsive web application for dental clinics featuring multi-role access control for Patients, Staff members, and Administrators.

---

## 🌟 Key Features

### 👤 Patient Portal
- **Online Booking**: Dynamic service selection, doctor selection, and automated day-blocking based on clinic operating hours.
- **Appointment Tracking**: Real-time status tracking (Pending, Confirmed, In Progress, Completed, Cancelled).
- **Personal Records**: Manage patient medical history and personal profile.

### 👩‍⚕️ Staff Portal
- **Daily Queue Management**: Live status progression for patients currently in clinic.
- **Weekly Schedule & Appointments**: View, confirm, reschedule, or cancel patient bookings.
- **Patient Records & History**: Searchable medical records and past visit history.

### 🛡️ Admin Portal
- **Master Appointments Hub**: Live search and status filtering across all appointments clinic-wide.
- **User Management**: Add, update, and manage Patients, Staff, and Administrators with an ethical soft-delete mechanism (`is_active` flag) preserving historical medical records.
- **Settings & Dynamic Config**: Manage clinic operating hours, closed days (e.g. Sundays), active dental services, and doctor profiles dynamically stored in MySQL.

---

## 🛠️ Technology Stack
- **Frontend**: HTML5, Vanilla CSS3 (Custom design system, dark mode & glassmorphism), Vanilla JavaScript (Fetch API).
- **Backend**: PHP 8+ (RESTful JSON endpoints, PDO prepared statements).
- **Database**: MySQL / MariaDB (via XAMPP).

---

## 🚀 Setup & Local Installation

### 1. Requirements
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL)
- Web browser (Chrome, Edge, Firefox)

### 2. Installation Steps
1. Clone or extract this repository into your XAMPP web root:
   ```bash
   C:\xampp\htdocs\clinicflow
   # or
   E:\xampp\htdocs\clinicflow
   ```
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
4. Create a database named `clinicflow` (or import directly from `backend/database.sql`).
5. Open your browser and navigate to:
   ```
   http://localhost/clinicflow/
   ```

---

## 🔑 Default Credentials

| Role | Email | Password |
|---|---|---|
| **Admin** | `admin@clinicflow.com` | `admin123` |
| **Patient** | Register via `register.html` | Custom |

---

## 📁 Project Structure
```
clinicflow/
├── admin/               # Admin dashboard & management interfaces
│   ├── appointments.html
│   ├── users.html
│   └── settings.html
├── assets/              # Stylesheets, JS scripts, icons, images
│   ├── css/
│   ├── js/
│   └── images/
├── backend/             # PHP backend APIs & MySQL schema
│   ├── database.sql     # Master database schema & seed data
│   ├── db.php           # PDO database connection
│   └── *.php            # Endpoint scripts
├── patient/             # Patient dashboard & booking pages
├── staff/               # Staff queue, scheduling & records pages
├── index.html           # Landing page
├── login.html           # Authentication portal
└── register.html        # Patient registration
```

---

## 📄 License
This project is for educational and dental clinic management purposes.
