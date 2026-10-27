<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        echo json_encode(["success" => true, "settings" => $settings]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        foreach ($data as $key => $value) {
            $stmt->execute([$key, $value, $value]);
        }
        
        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
    }
}
?>
