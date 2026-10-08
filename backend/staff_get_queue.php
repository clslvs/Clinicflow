<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'staff') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT a.id, a.service, a.status, a.appointment_time, 
               u.first_name, u.last_name, u.is_priority,
               d.name as doctor_name, d.id as doctor_id
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.appointment_date = CURDATE()
          AND a.status != 'cancelled'
        ORDER BY u.is_priority DESC, a.created_at ASC
    ");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $queue = [];
    $in_progress_count = 0;
    $waiting_count = 0;
    $completed_count = 0;

    $queue_number = 1;

    foreach ($appointments as $appt) {

        $logical_status = $appt['status'];
        if ($logical_status === 'pending' || $logical_status === 'confirmed') {
            $logical_status = 'waiting';
            $waiting_count++;
        } else if ($logical_status === 'in_progress') {
            $in_progress_count++;
        } else if ($logical_status === 'completed') {
            $completed_count++;
        }

        $queue[] = [
            "id" => $appt['id'],
            "queue_no" => str_pad($queue_number, 2, '0', STR_PAD_LEFT),
            "patient_name" => $appt['first_name'] . ' ' . $appt['last_name'],
            "service" => $appt['service'],
            "doctor_name" => $appt['doctor_name'],
            "room_no" => "Room " . $appt['doctor_id'], // Simulated Room No based on doctor ID
            "status" => $logical_status, // 'waiting', 'in_progress', 'completed'
            "raw_status" => $appt['status'], // Original status if needed
            "is_priority" => (bool)$appt['is_priority']
        ];
        
        $queue_number++;
    }

    echo json_encode([
        "success" => true,
        "metrics" => [
            "in_progress" => str_pad($in_progress_count, 2, '0', STR_PAD_LEFT),
            "waiting" => str_pad($waiting_count, 2, '0', STR_PAD_LEFT),
            "completed" => str_pad($completed_count, 2, '0', STR_PAD_LEFT)
        ],
        "queue" => $queue
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
