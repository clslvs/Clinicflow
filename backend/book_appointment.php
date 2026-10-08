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
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, service, appointment_date, appointment_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$patient_id, $doctor_id, $service, $date, $time]);
    
    $appointment_id = $pdo->lastInsertId();
    
    $globalQueueStmt = $pdo->prepare("
        SELECT a.id
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        WHERE a.appointment_date = ? AND a.status != 'cancelled'
        ORDER BY u.is_priority DESC, a.created_at ASC
    ");
    $globalQueueStmt->execute([$date]);
    $globalAppointments = $globalQueueStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $global_q = 0;
    foreach ($globalAppointments as $i => $app_id) {
        if ($app_id == $appointment_id) {
            $global_q = $i + 1;
            break;
        }
    }

    $queue_number = str_pad($global_q, 2, '0', STR_PAD_LEFT);
    echo json_encode([
        "success" => true, 
        "message" => "Appointment booked successfully!",
        "queue_number" => $queue_number
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
