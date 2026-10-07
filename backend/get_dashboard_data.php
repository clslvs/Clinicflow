<?php
// backend/get_dashboard_data.php
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
    // Get member since month/year
    $stmtUser = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $userData = $stmtUser->fetch();
    if ($userData && $userData['created_at']) {
        $date = new DateTime($userData['created_at']);
        $response['member_since'] = $date->format('F Y');
    }

    // Get total visits (count all appointments for this user)
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE patient_id = ?");
    $stmtTotal->execute([$user_id]);
    $totalData = $stmtTotal->fetch();
    $response['total_visits'] = $totalData['total'] ?? 0;

    // Get upcoming appointments (appointments in the future)
    // We'll join with doctors table to get doctor name
    $stmtUpcoming = $pdo->prepare("
        SELECT a.*, d.name as doctor_name 
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ? 
        AND a.appointment_date >= CURDATE()
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ");
    $stmtUpcoming->execute([$user_id]);
    $appointments = $stmtUpcoming->fetchAll();

    // If there's at least one upcoming, the first one is the "next appointment"
    if (count($appointments) > 0) {
        $next = $appointments[0];
        
        $dateObj = new DateTime($next['appointment_date']);
        $time_display = 'Time not set';
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

    // Format all upcoming appointments for the list
    foreach ($appointments as $apt) {
        $dateObj = new DateTime($apt['appointment_date']);
        
        $time_text = 'Time not set';
        if (!empty($apt['appointment_time'])) {
            $timeObj = new DateTime($apt['appointment_time']);
            $time_text = $timeObj->format('h:i A');
        }
        
        // Format status cleanly (e.g. "in_progress" -> "In progress")
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
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
