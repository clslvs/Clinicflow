<?php
// backend/staff_get_dashboard.php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'staff') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$today = date('Y-m-d');

try {
    // 1. Get Metrics
    $metrics = [
        "total_appointments" => 0,
        "in_queue" => 0,
        "doctors_on_duty" => 0,
        "completed_today" => 0
    ];

    // Total Appointments Today (excluding cancelled)
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status != 'cancelled'");
    $stmt1->execute([$today]);
    $metrics['total_appointments'] = $stmt1->fetchColumn();

    // In Queue (pending or in_progress)
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status IN ('pending', 'in_progress')");
    $stmt2->execute([$today]);
    $metrics['in_queue'] = $stmt2->fetchColumn();

    // Doctors on Duty (distinct doctors with appointments today)
    $stmt3 = $pdo->prepare("SELECT COUNT(DISTINCT doctor_id) FROM appointments WHERE appointment_date = ? AND status != 'cancelled'");
    $stmt3->execute([$today]);
    $metrics['doctors_on_duty'] = $stmt3->fetchColumn();

    // Completed Today
    $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status = 'completed'");
    $stmt4->execute([$today]);
    $metrics['completed_today'] = $stmt4->fetchColumn();

    // 2. Get Schedule List
    $stmtSchedule = $pdo->prepare("
        SELECT a.id, a.service, a.appointment_time, a.status, d.name as doctor_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.appointment_date = ? 
        ORDER BY a.appointment_time ASC
    ");
    $stmtSchedule->execute([$today]);
    $schedule = $stmtSchedule->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "metrics" => $metrics,
        "schedule" => $schedule
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
