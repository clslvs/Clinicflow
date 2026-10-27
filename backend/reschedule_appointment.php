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

try {

    $stmtCheck = $pdo->prepare("SELECT id, status FROM appointments WHERE id = ? AND patient_id = ?");
    $stmtCheck->execute([$appointment_id, $patient_id]);
    $apptRow = $stmtCheck->fetch();
    if (!$apptRow) {
        echo json_encode(["success" => false, "message" => "Appointment not found or unauthorized."]);
        exit;
    }

    if (!in_array($apptRow['status'], ['pending', 'confirmed'])) {
        echo json_encode(["success" => false, "message" => "Only pending or confirmed appointments can be rescheduled."]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ? WHERE id = ?");
    $stmt->execute([$date, $time, $appointment_id]);

    echo json_encode([
        "success" => true, 
        "message" => "Appointment rescheduled successfully!"
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
