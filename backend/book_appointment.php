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
$service = $data['service'] ?? '';
$doctor_id = $data['doctorId'] ?? '';
$date = $data['date'] ?? '';
$time = !empty($data['time']) ? $data['time'] : null;

if (empty($service) || empty($doctor_id) || empty($date)) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

// Disallow booking on past dates
if ($date < date('Y-m-d')) {
    echo json_encode(["success" => false, "message" => "Cannot book appointments on past dates."]);
    exit;
}

// Disallow booking past time slots on today
if ($date === date('Y-m-d') && !empty($time) && strtotime("$date $time") <= time()) {
    echo json_encode(["success" => false, "message" => "Cannot book a past time slot."]);
    exit;
}

try {
    // Validate that the doctor exists and is active
    $docCheck = $pdo->prepare("SELECT id, name FROM doctors WHERE id = ? AND is_active = 1");
    $docCheck->execute([$doctor_id]);
    $docData = $docCheck->fetch();
    if (!$docData) {
        echo json_encode(["success" => false, "message" => "The selected doctor is not currently available for bookings."]);
        exit;
    }

    // Validate doctor working day and hours
    $dateObj = new DateTime($date);
    $dayOfWeek = $dateObj->format('l');

    $availStmt = $pdo->prepare("SELECT is_available, start_time, end_time FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ?");
    $availStmt->execute([$doctor_id, $dayOfWeek]);
    $docAvail = $availStmt->fetch();

    if ($docAvail && $docAvail['is_available'] == 0) {
        echo json_encode(["success" => false, "message" => "{$docData['name']} is not available on {$dayOfWeek}s. Please select another date or doctor."]);
        exit;
    }

    if ($docAvail && !empty($time)) {
        $slotTime = strtotime("$date $time");
        $startTime = strtotime("$date " . $docAvail['start_time']);
        $endTime = strtotime("$date " . $docAvail['end_time']);

        if ($slotTime < $startTime || $slotTime >= $endTime) {
            echo json_encode(["success" => false, "message" => "The selected time is outside {$docData['name']}'s consultation hours."]);
            exit;
        }
    }

    $pdo->beginTransaction();

    // Prevent double booking for this doctor at the selected date and time
    if (!empty($time)) {
        $checkSlot = $pdo->prepare("
            SELECT id FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'
            LIMIT 1 FOR UPDATE
        ");
        $checkSlot->execute([$doctor_id, $date, $time]);
        if ($checkSlot->fetch()) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "message" => "This time slot is already booked for this doctor. Please choose another time."]);
            exit;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, service, appointment_date, appointment_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$patient_id, $doctor_id, $service, $date, $time]);
    
    $appointment_id = $pdo->lastInsertId();
    
    // Calculate the patient's daily queue sequence for this doctor and date
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'");
    $countStmt->execute([$doctor_id, $date]);
    $dailyNumber = (int)$countStmt->fetchColumn();
    $queue_number = str_pad($dailyNumber, 3, '0', STR_PAD_LEFT);

    $pdo->commit();

    echo json_encode([
        "success" => true, 
        "message" => "Appointment booked successfully!",
        "queue_number" => $queue_number
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage()); 
    echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>