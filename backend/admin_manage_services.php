<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM services ORDER BY name ASC");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "services" => $services]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO services (name, description) VALUES (?, ?)");
            $stmt->execute([$data['name'], $data['description']]);
            echo json_encode(["success" => true]);
        } else if ($action === 'update_status') {
            $stmt = $pdo->prepare("UPDATE services SET is_active = ? WHERE id = ?");
            $stmt->execute([$data['is_active'], $data['id']]);
            echo json_encode(["success" => true]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>
