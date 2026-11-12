<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'admin'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON payload"]);
    exit;
}

$doctorId = (int)($data['doctor_id'] ?? 0);
$service  = trim($data['service'] ?? '');
$notes    = trim($data['notes'] ?? '');
$patientId = !empty($data['patient_id']) ? (int)$data['patient_id'] : 0;
$today    = date('Y-m-d');
$dayName  = date('l');

if ($doctorId <= 0 || $service === '') {
    echo json_encode(["success" => false, "message" => "Doctor and dental service are required."]);
    exit;
}

if ($patientId <= 0) {
    $firstName = trim($data['first_name'] ?? '');
    $lastName  = trim($data['last_name'] ?? '');
    $phone     = trim($data['phone'] ?? '');
    $email     = trim($data['email'] ?? '');
    $sex       = trim($data['sex'] ?? '');
    $allergies = trim($data['allergies'] ?? '');
    $conditions = trim($data['medical_conditions'] ?? '');
    $isPriority = !empty($data['is_priority']) ? 1 : 0;

    if ($firstName === '' || $lastName === '') {
        echo json_encode(["success" => false, "message" => "First and last name are required for a new walk-in."]);
        exit;
    }
    if (!preg_match('/^[0-9]{11}$/', $phone)) {
        echo json_encode(["success" => false, "message" => "Phone number must be exactly 11 digits (e.g., 09123456789)."]);
        exit;
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Please provide a valid email address or leave it blank."]);
        exit;
    }
    if ($sex !== '' && !in_array($sex, ['Male', 'Female', 'Other'], true)) {
        $sex = null;
    } elseif ($sex === '') {
        $sex = null;
    }
}

try {
    ensureAppointmentVisitType($pdo);

    // Check closed days
    $settingsStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'closed_days' LIMIT 1");
    $closedRow = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    if ($closedRow && !empty($closedRow['setting_value'])) {
        $closedDays = json_decode($closedRow['setting_value'], true);
        if (is_array($closedDays) && in_array($dayName, $closedDays, true)) {
            echo json_encode(["success" => false, "message" => "The clinic is closed on {$dayName}s."]);
            exit;
        }
    }

    // Verify active doctor and day availability
    $docStmt = $pdo->prepare("SELECT id, name FROM doctors WHERE id = ? AND is_active = 1");
    $docStmt->execute([$doctorId]);
    if (!$docStmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Selected doctor is not currently active."]);
        exit;
    }

    $availStmt = $pdo->prepare("SELECT is_available FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ?");
    $availStmt->execute([$doctorId, $dayName]);
    $avail = $availStmt->fetch(PDO::FETCH_ASSOC);
    if ($avail && (int)$avail['is_available'] === 0) {
        echo json_encode(["success" => false, "message" => "Selected doctor is not available on {$dayName}s."]);
        exit;
    }

    $pdo->beginTransaction();

    if ($patientId > 0) {
        $pStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'patient' AND is_active = 1 AND (is_deleted = 0 OR is_deleted IS NULL)");
        $pStmt->execute([$patientId]);
        if (!$pStmt->fetch()) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "message" => "Selected patient record was not found or is inactive."]);
            exit;
        }

        if (!empty($data['is_priority'])) {
            $updPrio = $pdo->prepare("UPDATE users SET is_priority = 1 WHERE id = ?");
            $updPrio->execute([$patientId]);
        }
    } else {
        if ($email !== '') {
            $chkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $chkEmail->execute([$email]);
            if ($chkEmail->fetch()) {
                $pdo->rollBack();
                echo json_encode(["success" => false, "message" => "That email is already registered. Select the returning patient instead."]);
                exit;
            }
        } else {
            $email = 'walkin.' . bin2hex(random_bytes(6)) . '@clinic.local';
        }

        $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $insUser = $pdo->prepare("
            INSERT INTO users (first_name, last_name, sex, email, phone, password, role, is_priority, allergies, medical_conditions)
            VALUES (?, ?, ?, ?, ?, ?, 'patient', ?, ?, ?)
        ");
        $insUser->execute([
            $firstName,
            $lastName,
            $sex,
            $email,
            $phone,
            $randomPass,
            $isPriority,
            $allergies !== '' ? $allergies : null,
            $conditions !== '' ? $conditions : null
        ]);
        $patientId = (int)$pdo->lastInsertId();
    }

    // Prevent duplicate active queue entry for the same patient, doctor, and day
    $dupStmt = $pdo->prepare("
        SELECT id FROM appointments
        WHERE patient_id = ? AND doctor_id = ? AND appointment_date = ? AND status IN ('pending', 'confirmed', 'in_progress')
        LIMIT 1
    ");
    $dupStmt->execute([$patientId, $doctorId, $today]);
    if ($dupStmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "This patient already has an active queue entry with that doctor today."]);
        exit;
    }

    $insAppt = $pdo->prepare("
        INSERT INTO appointments (patient_id, doctor_id, service, appointment_date, appointment_time, visit_type, status, notes)
        VALUES (?, ?, ?, ?, NULL, 'walk_in', 'confirmed', ?)
    ");
    $insAppt->execute([$patientId, $doctorId, $service, $today, $notes !== '' ? $notes : null]);
    $appointmentId = (int)$pdo->lastInsertId();

    $qStmt = $pdo->prepare("
        SELECT a.id
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        WHERE a.appointment_date = ? AND a.status != 'cancelled'
        ORDER BY u.is_priority DESC, COALESCE(NULLIF(a.appointment_time, ''), '23:59:59') ASC, a.created_at ASC
    ");
    $qStmt->execute([$today]);
    $queueIds = $qStmt->fetchAll(PDO::FETCH_COLUMN);
    $pos = array_search((string)$appointmentId, array_map('strval', $queueIds), true);
    $queueNo = str_pad(($pos === false ? count($queueIds) : $pos + 1), 3, '0', STR_PAD_LEFT);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Walk-in checked in as Token #$queueNo.",
        "appointment_id" => $appointmentId,
        "patient_id" => $patientId,
        "queue_no" => $queueNo
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>

