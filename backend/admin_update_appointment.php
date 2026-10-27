<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['appointment_id'], $data['status'])) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

$allowedStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'rescheduling'];
if (!in_array($data['status'], $allowedStatuses)) {
    echo json_encode(["success" => false, "message" => "Invalid status"]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$data['status'], $data['appointment_id']]);

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
