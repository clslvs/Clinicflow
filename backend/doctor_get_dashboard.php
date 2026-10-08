<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {

    $userStmt = $pdo->prepare("SELECT linked_doctor_id FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $userRow = $userStmt->fetch();

    if (!$userRow || empty($userRow['linked_doctor_id'])) {
        echo json_encode(["success" => false, "message" => "Doctor profile not linked."]);
        exit;
    }

    $doctor_id = $userRow['linked_doctor_id'];

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status IN ('pending', 'confirmed') THEN 1 ELSE 0 END) as in_queue,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_today
        FROM appointments 
        WHERE appointment_date = CURDATE() AND status != 'cancelled' AND doctor_id = ?
    ");
    $stmt->execute([$doctor_id]);
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

    $patientStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT patient_id) as total_patients 
        FROM appointments 
        WHERE doctor_id = ?
    ");
    $patientStmt->execute([$doctor_id]);
    $patientData = $patientStmt->fetch(PDO::FETCH_ASSOC);

    $stmtSched = $pdo->prepare("
        SELECT a.id, a.service, a.status, a.appointment_time, a.notes, 
               CONCAT(u.first_name, ' ', u.last_name) as patient_name
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        WHERE a.appointment_date = CURDATE() AND a.status != 'cancelled' AND a.doctor_id = ?
        ORDER BY a.appointment_time ASC
    ");
    $stmtSched->execute([$doctor_id]);
    $schedule = $stmtSched->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "metrics" => [
            "total_appointments" => $metrics['total_appointments'] ?? 0,
            "in_queue" => $metrics['in_queue'] ?? 0,
            "total_patients" => $patientData['total_patients'] ?? 0,
            "completed_today" => $metrics['completed_today'] ?? 0
        ],
        "schedule" => $schedule
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
