<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

try {

    $stmt = $pdo->prepare("
        SELECT id, doctor_id, status, appointment_time, appointment_date 
        FROM appointments 
        WHERE patient_id = ? AND appointment_date = ? AND status IN ('pending', 'in_progress', 'confirmed')
        ORDER BY appointment_date ASC, created_at ASC
        LIMIT 1
    ");
    $stmt->execute([$user_id, $today]);
    $userAppt = $stmt->fetch();

    if (!$userAppt) {

        echo json_encode(["success" => true, "has_queue" => false]);
        exit;
    }

    $doctor_id = $userAppt['doctor_id'];
    $user_appointment_id = $userAppt['id'];
    $queue_date = $userAppt['appointment_date'];

    $docStmt = $pdo->prepare("SELECT name FROM doctors WHERE id = ?");
    $docStmt->execute([$doctor_id]);
    $doctor = $docStmt->fetch();
    $doctor_name = $doctor ? $doctor['name'] : "Unknown Doctor";

    $queueStmt = $pdo->prepare("
        SELECT a.id, a.status, a.patient_id, a.service, a.appointment_time, u.is_priority, CONCAT(u.first_name, ' ', u.last_name) as patient_name
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status IN ('pending', 'in_progress', 'confirmed')
        ORDER BY u.is_priority DESC, a.created_at ASC
    ");
    $queueStmt->execute([$doctor_id, $queue_date]);
    $allAppointments = $queueStmt->fetchAll();

    $queueList = [];
    $userPosition = 0;
    $currentPatientIndex = 0; // Find the first in_progress or pending

    $activePatientFound = false;

    foreach ($allAppointments as $index => $app) {
        $pos = $index + 1;
        $isUser = ($app['id'] == $user_appointment_id);
        
        if ($isUser) {
            $userPosition = $pos;
        }

        $state = 'waiting';
        $stateLabel = 'Waiting';
        
        if ($app['status'] == 'in_progress') {
            $state = 'in-progress';
            $stateLabel = 'In Progress';
            $activePatientFound = true;
            $currentPatientIndex = $index;
        } else if (!$activePatientFound) {

            $state = 'in-progress';
            $stateLabel = 'In Progress';
            $activePatientFound = true;
            $currentPatientIndex = $index;
        }

        if ($isUser) {
            $state = 'your-turn';
            if ($index === $currentPatientIndex) {
                $stateLabel = 'Your Turn';
            } else {
                $stateLabel = 'You';
            }
        }

        $qnum = str_pad($app['id'], 3, '0', STR_PAD_LEFT);

        $queueList[] = [
            "position" => str_pad($pos, 2, '0', STR_PAD_LEFT), // 01, 02
            "queue_number" => $qnum,
            "service" => $app['service'],
            "state" => $state,
            "stateLabel" => $stateLabel,
            "is_user" => $isUser
        ];
    }

    $peopleAhead = max(0, $userPosition - $currentPatientIndex - 1);
    $estimatedWaitMins = $peopleAhead * 30; // 30 mins per person

    if ($estimatedWaitMins >= 60) {
        $hrs = floor($estimatedWaitMins / 60);
        $mins = $estimatedWaitMins % 60;
        $estWaitText = $mins > 0 ? "{$hrs} hr {$mins} m" : "{$hrs} hrs";
    } else if ($estimatedWaitMins > 0) {
        $estWaitText = "{$estimatedWaitMins} mins";
    } else {
        $estWaitText = "Next";
    }

    $currentPatientStr = "#" . str_pad($currentPatientIndex + 1, 2, '0', STR_PAD_LEFT);

    echo json_encode([
        "success" => true,
        "has_queue" => true,
        "doctor_name" => $doctor_name,
        "user_position" => str_pad($userPosition, 2, '0', STR_PAD_LEFT),
        "user_queue_number" => str_pad($userAppt['id'], 3, '0', STR_PAD_LEFT),
        "current_patient" => $currentPatientStr,
        "people_ahead" => $peopleAhead,
        "estimated_wait" => $estWaitText,
        "queue_list" => $queueList
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
