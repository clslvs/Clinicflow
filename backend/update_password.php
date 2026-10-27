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

$old_password = $data['old_password'] ?? $data['current_password'] ?? '';
$new_password = $data['new_password'] ?? '';

if (empty($old_password) || empty($new_password)) {
    echo json_encode(["success" => false, "message" => "Both current and new passwords are required."]);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(["success" => false, "message" => "New password must be at least 6 characters long."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($old_password, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Incorrect old/current password."]);
        exit;
    }

    $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->execute([$hashed_new, $_SESSION['user_id']]);

    echo json_encode(["success" => true, "message" => "Password updated successfully."]);

} catch (PDOException $e) {
    error_log($e->getMessage()); 
    echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
