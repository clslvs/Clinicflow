<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'admin'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$today = date('Y-m-d');

try {

    $metrics = [
        "total_appointments" => 0,
        "in_queue" => 0,
        "doctors_on_duty" => 0,
        "completed_today" => 0
    ];

    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status != 'cancelled'");
    $stmt1->execute([$today]);
    $metrics['total_appointments'] = $stmt1->fetchColumn();

    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status IN ('pending', 'in_progress')");
    $stmt2->execute([$today]);
    $metrics['in_queue'] = $stmt2->fetchColumn();

    $stmt3 = $pdo->prepare("SELECT COUNT(DISTINCT doctor_id) FROM appointments WHERE appointment_date = ? AND status != 'cancelled'");
    $stmt3->execute([$today]);
    $metrics['doctors_on_duty'] = $stmt3->fetchColumn();

    $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status = 'completed'");
    $stmt4->execute([$today]);
    $metrics['completed_today'] = $stmt4->fetchColumn();

    ensureAppointmentVisitType($pdo);

    $stmtSchedule = $pdo->prepare("
        SELECT a.id, a.patient_id, a.service, a.appointment_time, a.visit_type, a.status,
               d.name as doctor_name,
               CONCAT(u.first_name, ' ', u.last_name) as patient_name,
               u.is_priority
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON a.patient_id = u.id
        WHERE a.appointment_date = ? 
        ORDER BY u.is_priority DESC, COALESCE(NULLIF(a.appointment_time, ''), '23:59:59') ASC, a.created_at ASC
    ");
    $stmtSchedule->execute([$today]);
    $schedule = $stmtSchedule->fetchAll(PDO::FETCH_ASSOC);
    foreach ($schedule as &$row) {
        $row['is_priority'] = (bool)($row['is_priority'] ?? false);
    }
    unset($row);

    echo json_encode([
        "success" => true,
        "metrics" => $metrics,
        "schedule" => $schedule
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
