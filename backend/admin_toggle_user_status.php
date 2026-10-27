<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'], $data['is_active'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

if ($data['id'] == $_SESSION['user_id']) {
    echo json_encode(["success" => false, "message" => "You cannot deactivate your own account."]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$data['is_active'], $data['id']]);

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
