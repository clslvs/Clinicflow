<?php
// backend/book_appointment.php
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
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, service, appointment_date, appointment_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$patient_id, $doctor_id, $service, $date, $time]);
    
    $appointment_id = $pdo->lastInsertId();
    $queue_number = str_pad($appointment_id, 4, '0', STR_PAD_LEFT);

    echo json_encode([
        "success" => true, 
        "message" => "Appointment booked successfully!",
        "queue_number" => $queue_number
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
