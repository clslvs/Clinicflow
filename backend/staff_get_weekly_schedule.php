<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'admin'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if (!isset($_GET['doctor_id']) || !isset($_GET['start_date']) || !isset($_GET['end_date'])) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

$doctor_id = $_GET['doctor_id'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.service, a.appointment_date, a.appointment_time, 
               u.first_name, u.last_name
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        WHERE a.doctor_id = ? 
          AND a.appointment_date >= ? 
          AND a.appointment_date <= ?
          AND a.status != 'cancelled'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ");
    $stmt->execute([$doctor_id, $start_date, $end_date]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $schedule = [];
    foreach ($appointments as $appt) {
        $schedule[] = [
            "id" => $appt['id'],
            "date" => $appt['appointment_date'],
            "time" => substr($appt['appointment_time'], 0, 5), // 'HH:MM'
            "patient_name" => $appt['first_name'] . ' ' . $appt['last_name'],
            "service" => $appt['service']
        ];
    }

    echo json_encode(["success" => true, "schedule" => $schedule]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
