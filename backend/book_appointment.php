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

try {
    // Prevent double booking for this doctor at the selected date and time
    if (!empty($time)) {
        $checkSlot = $pdo->prepare("
            SELECT id FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'
            LIMIT 1
        ");
        $checkSlot->execute([$doctor_id, $date, $time]);
        if ($checkSlot->fetch()) {
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

    echo json_encode([
        "success" => true, 
        "message" => "Appointment booked successfully!",
        "queue_number" => $queue_number
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
