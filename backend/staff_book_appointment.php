<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'admin'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$visitType = (($data['visit_type'] ?? 'scheduled') === 'walk_in') ? 'walk_in' : 'scheduled';
$date = !empty($data['date']) ? $data['date'] : ($visitType === 'walk_in' ? date('Y-m-d') : '');
$time = isset($data['time']) && trim((string)$data['time']) !== '' ? trim((string)$data['time']) : null;

if (empty($data['patient_id']) || empty($data['doctor_id']) || empty($date) || empty($data['service']) || ($visitType === 'scheduled' && $time === null)) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$status = $visitType === 'walk_in' ? 'confirmed' : 'pending';
$notes = isset($data['notes']) && trim((string)$data['notes']) !== '' ? trim((string)$data['notes']) : null;

try {
    ensureAppointmentVisitType($pdo);

    $stmt = $pdo->prepare("
        INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, visit_type, service, status, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['patient_id'],
        $data['doctor_id'],
        $date,
        $visitType === 'walk_in' ? null : $time,
        $visitType,
        $data['service'],
        $status,
        $notes
    ]);

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
