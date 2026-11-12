<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Please log in."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = [
    "success" => true,
    "user" => [
        "name" => $_SESSION['user_name'],
        "initial" => strtoupper(substr($_SESSION['user_name'], 0, 1))
    ],
    "next_appointment" => null,
    "total_visits" => 0,
    "member_since" => "",
    "upcoming_appointments" => []
];

try {

    $stmtUser = $pdo->prepare("SELECT created_at, is_priority FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $userData = $stmtUser->fetch();
    $response['user']['is_priority'] = (bool)($userData['is_priority'] ?? false);
    if ($userData && $userData['created_at']) {
        $date = new DateTime($userData['created_at']);
        $response['member_since'] = $date->format('F Y');
    }

    $stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE patient_id = ?");
    $stmtTotal->execute([$user_id]);
    $totalData = $stmtTotal->fetch();
    $response['total_visits'] = $totalData['total'] ?? 0;


    ensureAppointmentVisitType($pdo);

    $stmtUpcoming = $pdo->prepare("
        SELECT a.*, d.name as doctor_name 
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ? 
        AND a.appointment_date >= CURDATE()
        AND a.status != 'cancelled'
        ORDER BY a.appointment_date ASC, COALESCE(NULLIF(a.appointment_time, ''), '23:59:59') ASC, a.created_at ASC
    ");
    $stmtUpcoming->execute([$user_id]);
    $appointments = $stmtUpcoming->fetchAll();

    if (count($appointments) > 0) {
        $next = $appointments[0];
        
        $dateObj = new DateTime($next['appointment_date']);
        $time_display = (($next['visit_type'] ?? '') === 'walk_in') ? 'Walk-in' : 'Time not set';
        if (!empty($next['appointment_time'])) {
            $timeObj = new DateTime($next['appointment_time']);
            $time_display = $timeObj->format('h:i A');
        }

        $response['next_appointment'] = [
            "date_display" => $dateObj->format('F d'),
            "time_display" => $time_display,
            "doctor_name" => $next['doctor_name']
        ];
    }

    foreach ($appointments as $apt) {
        $dateObj = new DateTime($apt['appointment_date']);
        
        $time_text = (($apt['visit_type'] ?? '') === 'walk_in') ? 'Walk-in' : 'Time not set';
        if (!empty($apt['appointment_time'])) {
            $timeObj = new DateTime($apt['appointment_time']);
            $time_text = $timeObj->format('h:i A');
        }

        $status_display = str_replace('_', ' ', $apt['status']);
        $status_display = ucfirst($status_display);

        $response['upcoming_appointments'][] = [
            "id" => $apt['id'],
            "service" => $apt['service'],
            "doctor_name" => $apt['doctor_name'],
            "date_text" => $dateObj->format('F d, Y'),
            "time_text" => $time_text,
            "status" => $apt['status'],
            "status_display" => $status_display
        ];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
