<?php

session_start();

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    require 'db.php';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode([
            "success" => true,
            "user" => [
                "id" => $_SESSION['user_id'],
                "role" => $_SESSION['user_role'],
                "name" => $_SESSION['user_name']
            ]
        ]);
        exit;
    } else {

        session_destroy();
    }
}

echo json_encode(["success" => false, "message" => "Not logged in."]);
?>
