<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT a.id, a.service, a.status, a.appointment_date, a.appointment_time, 
               CONCAT(u.first_name, ' ', u.last_name) as patient_name,
               d.name as doctor_name
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($appointments as $app) {
        $dateObj = new DateTime($app['appointment_date']);
        $timeObj = new DateTime($app['appointment_time']);
        
        $app['formatted_date'] = $dateObj->format('M. d, Y');
        $app['formatted_time'] = $timeObj->format('h:iA');
        
        $formatted[] = $app;
    }

    echo json_encode(["success" => true, "appointments" => $formatted]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
