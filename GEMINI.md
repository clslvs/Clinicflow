# Project: ClinicFlow (Dental Clinic Management System)

## 📌 Tech Stack
- **Frontend**: HTML5, Vanilla CSS3 (Custom design system with glassmorphism, dark/light themes), Vanilla JavaScript (ES6+, Fetch API, DOM manipulation). No build step or heavy node frameworks.
- **Backend**: PHP 8+ procedural/modular RESTful endpoints (`backend/*.php`) returning JSON.
- **Database**: MySQL / MariaDB via PDO (`backend/db.php`).
- **Local Dev Server**: Laragon (Apache + MySQL on Windows).
- **Deployment**: InfinityFree hosting via PowerShell FTP script (`deploy.ps1`).

---

## 🏗️ Architecture & Roles
- **Patient Portal (`patient/`)**: Appointment booking, live queue tracking, profile, and personal visit history.
- **Staff Portal (`staff/`)**: Daily queue progression, weekly schedule, booking adjustments, and patient records.
- **Doctor Portal (`doctor/`)**: Consultation notes, patient medical history, and availability scheduling.
- **Admin Portal (`admin/`)**: Clinic-wide appointment overview, user management, and dynamic settings (clinic hours, services, doctors).

---

## 📋 Code Conventions

### Backend (PHP / PDO)
1. **JSON API Contract**:
   - Always declare: `header('Content-Type: application/json');`.
   - Standard response payload format (return domain keys directly at top level to match frontend):
     ```json
     {
       "success": true,
       "message": "Optional human-readable feedback",
       "doctors": [],
       "patients": []
     }
     ```
   - On error: `echo json_encode(["success" => false, "message" => "Error description"]); exit;`.
2. **Database & Prepared Statements**:
   - Always include `require 'db.php';`.
   - **Never concatenate unsanitized user input** into queries. Always use PDO prepared statements with `?` or named parameters.
3. **Session & Auth Checks**:
   - Session initialization: `session_start();`.
   - Access verification via `$_SESSION['user_id']` and `$_SESSION['user_role']`.
   - Password handling: Verify using `password_verify($password, $hash)` and store with `password_hash($password, PASSWORD_DEFAULT)`.
4. **Data Integrity & Soft Deletes**:
   - For users/records tied to medical or appointment history, apply ethical soft-deletion (`is_active = 0`) instead of hard `DELETE`.

### Frontend (HTML / CSS / JS)
1. **Styling**:
   - Follow tokens and conventions defined in `assets/css/variables.css`, `assets/css/layout.css`, and `assets/css/global.css`.
   - Maintain the premium aesthetic (glassmorphism, subtle gradients, accessible color contrasts, smooth micro-interactions).
2. **JavaScript**:
   - Use clean modern Vanilla JavaScript (Fetch API with `async/await` or promise chains).
   - Parse JSON request bodies on backend via `file_get_contents('php://input')` and `json_decode(..., true)`.
   - Provide clear visual feedback (toasts, alerts, disabled states during requests) on user actions.

---

## 🚫 Boundaries & Security
- **Credentials & Secrets**: Never expose or commit database passwords, API secrets, or FTP credentials.
- **Database Migrations**: Do not run destructive queries (`DROP TABLE`, unbacked `TRUNCATE`, destructive column drops) without explicit confirmation.
- **Environment Compatibility**: Preserve dual-environment logic in `backend/db.php` supporting both local Laragon (`127.0.0.1`) and production InfinityFree hosting.
- **Dependencies**: Keep the app lightweight and native — avoid adding unnecessary external libraries or node dependencies unless requested.
