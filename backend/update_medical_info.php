<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$allergies = $data['allergies'] ?? '';
$conditions = $data['medical_conditions'] ?? '';
$is_priority = !empty($data['is_priority']) ? 1 : 0;

try {
    $stmt = $pdo->prepare("UPDATE users SET allergies = ?, medical_conditions = ?, is_priority = ? WHERE id = ?");
    $stmt->execute([$allergies, $conditions, $is_priority, $_SESSION['user_id']]);

    echo json_encode(["success" => true, "message" => "Medical information updated successfully."]);
} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
