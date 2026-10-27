<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['availability']) || !is_array($input['availability'])) {
    echo json_encode(["success" => false, "message" => "Invalid data."]);
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

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_available) 
        VALUES (?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time), is_available = VALUES(is_available)
    ");

    foreach ($input['availability'] as $slot) {
        $day = $slot['day_of_week'];
        $start = $slot['start_time'];
        $end = $slot['end_time'];
        $available = $slot['is_available'] ? 1 : 0;

        $stmt->execute([$doctor_id, $day, $start, $end, $available]);
    }

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "Availability saved successfully."]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
