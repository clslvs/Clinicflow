<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'staff') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['patient_id']) || !isset($data['is_priority'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET is_priority = ? WHERE id = ?");
    $stmt->execute([$data['is_priority'] ? 1 : 0, $data['patient_id']]);

    echo json_encode(["success" => true, "message" => "Status updated successfully!"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
