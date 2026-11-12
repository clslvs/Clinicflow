<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Please log in."]);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data provided."]);
    exit;
}

$patient_id = $_SESSION['user_id'];
$appointment_id = $data['appointment_id'] ?? '';
$date = $data['date'] ?? '';
$time = !empty($data['time']) ? $data['time'] : null;

if (empty($appointment_id) || empty($date)) {
    echo json_encode(["success" => false, "message" => "Appointment ID and new date are required."]);
    exit;
}

// Disallow rescheduling to a past date
if ($date < date('Y-m-d')) {
    echo json_encode(["success" => false, "message" => "Cannot reschedule to a past date."]);
    exit;
}

// Disallow rescheduling to a past time slot on today
if ($date === date('Y-m-d') && !empty($time) && strtotime("$date $time") <= time()) {
    echo json_encode(["success" => false, "message" => "Cannot reschedule to a past time slot."]);
    exit;
}

try {
    $dateObj = new DateTime($date);
    $dayOfWeek = $dateObj->format('l');

    $settingsStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'closed_days' LIMIT 1");
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    if ($settings && !empty($settings['setting_value'])) {
        $closedDays = json_decode($settings['setting_value'], true);
        if (is_array($closedDays) && in_array($dayOfWeek, $closedDays)) {
            echo json_encode(["success" => false, "message" => "The clinic is closed on " . $dayOfWeek . "s."]);
            exit;
        }
    }

    $pdo->beginTransaction();

    $stmtCheck = $pdo->prepare("SELECT id, doctor_id, status FROM appointments WHERE id = ? AND patient_id = ? FOR UPDATE");
    $stmtCheck->execute([$appointment_id, $patient_id]);
    $apptRow = $stmtCheck->fetch();
    if (!$apptRow) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Appointment not found or unauthorized."]);
        exit;
    }

    if (!in_array($apptRow['status'], ['pending', 'confirmed'])) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Only pending or confirmed appointments can be rescheduled."]);
        exit;
    }

    if (!empty($time)) {
        $checkSlot = $pdo->prepare("
            SELECT id FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled' AND id != ?
            LIMIT 1 FOR UPDATE
        ");
        $checkSlot->execute([$apptRow['doctor_id'], $date, $time, $appointment_id]);
        if ($checkSlot->fetch()) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "message" => "This time slot is already booked for this doctor. Please choose another time."]);
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ? WHERE id = ?");
    $stmt->execute([$date, $time, $appointment_id]);

    $pdo->commit();

    echo json_encode([
        "success" => true, 
        "message" => "Appointment rescheduled successfully!"
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage()); 
    echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>