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

    $stmt = $pdo->prepare("SELECT day_of_week, start_time, end_time, is_available FROM doctor_availability WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')");
    $stmt->execute([$doctor_id]);
    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $docStmt = $pdo->prepare("SELECT name, specialty FROM doctors WHERE id = ?");
    $docStmt->execute([$doctor_id]);
    $doctorInfo = $docStmt->fetch();

    $weekStmt = $pdo->prepare("
        SELECT COUNT(*) as week_count FROM appointments 
        WHERE doctor_id = ? AND status != 'cancelled' 
        AND appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 5 DAY)
    ");
    $weekStmt->execute([$doctor_id]);
    $weekData = $weekStmt->fetch();

    echo json_encode([
        "success" => true,
        "doctor" => $doctorInfo,
        "availability" => $availability,
        "week_appointments" => $weekData['week_count'] ?? 0
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
