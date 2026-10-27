<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // SELECT * ensures middle_name, sex, etc. are loaded if available without breaking if unmigrated
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $middle = !empty($user['middle_name']) ? ' ' . $user['middle_name'] : '';
        echo json_encode([
            "success" => true,
            "user" => [
                "id" => $user['id'],
                "name" => trim(($user['first_name'] ?? '') . $middle . ' ' . ($user['last_name'] ?? '')),
                "first_name" => $user['first_name'] ?? '',
                "middle_name" => $user['middle_name'] ?? '',
                "last_name" => $user['last_name'] ?? '',
                "sex" => $user['sex'] ?? '',
                "email" => $user['email'] ?? '',
                "phone" => $user['phone'] ?? '',
                "role" => $user['role'] ?? '',
                "created_at" => $user['created_at'] ?? ''
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage()); 
    echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
