<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$appointment_id = $input['appointment_id'] ?? null;
$notes = $input['doctor_notes'] ?? '';

if (!$appointment_id) {
    echo json_encode(["success" => false, "message" => "Appointment ID required."]);
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

    $checkStmt = $pdo->prepare("SELECT id FROM appointments WHERE id = ? AND doctor_id = ?");
    $checkStmt->execute([$appointment_id, $doctor_id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Appointment not found or not assigned to your profile."]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE appointments SET doctor_notes = ? WHERE id = ? AND doctor_id = ?");
    $stmt->execute([$notes, $appointment_id, $doctor_id]);

    echo json_encode(["success" => true, "message" => "Treatment notes saved."]);

} catch (PDOException $e) {
    error_log($e->getMessage()); 
    echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>