<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, email, phone, role, is_active, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($users as $user) {
        $dateObj = new DateTime($user['created_at']);
        $user['formatted_date'] = $dateObj->format('M. d, Y');
        $formatted[] = $user;
    }

    echo json_encode(["success" => true, "users" => $formatted]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
