<?php
// backend/update_medical_info.php
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

try {
    $stmt = $pdo->prepare("UPDATE users SET allergies = ?, medical_conditions = ? WHERE id = ?");
    $stmt->execute([$allergies, $conditions, $_SESSION['user_id']]);

    echo json_encode(["success" => true, "message" => "Medical information updated successfully."]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
