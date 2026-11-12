<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    ensureAppointmentVisitType($pdo);
    $stmt = $pdo->query("
        SELECT a.id, a.patient_id, a.service, a.status, a.appointment_date, a.appointment_time, a.visit_type,
               COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''), 'Unknown Patient') as patient_name,
               COALESCE(u.is_priority, 0) as is_priority,
               COALESCE(d.name, 'Unassigned / Any Doctor') as doctor_name
        FROM appointments a
        LEFT JOIN users u ON a.patient_id = u.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.appointment_date DESC, COALESCE(a.appointment_time, '23:59:59') DESC, a.id DESC
    ");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($appointments as $app) {
        try {
            $dateObj = new DateTime($app['appointment_date']);
            $app['formatted_date'] = $dateObj->format('M. d, Y');
        } catch (\Throwable $de) {
            $app['formatted_date'] = $app['appointment_date'] ?: '—';
        }

        if (!empty($app['appointment_time'])) {
            try {
                $timeObj = new DateTime($app['appointment_time']);
                $app['formatted_time'] = $timeObj->format('h:iA');
            } catch (\Throwable $te) {
                $app['formatted_time'] = $app['appointment_time'];
            }
        } else {
            $app['formatted_time'] = ($app['visit_type'] ?? '') === 'walk_in' ? 'Walk-in' : 'Not set';
        }
        $app['is_priority'] = (bool)($app['is_priority'] ?? false);

        $formatted[] = $app;
    }

    echo json_encode(["success" => true, "appointments" => $formatted]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
