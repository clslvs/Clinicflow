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
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, allergies, medical_conditions, is_priority FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        $patient_id_formatted = "#PT-" . str_pad($user['id'], 4, '0', STR_PAD_LEFT);

        echo json_encode([
            "success" => true,
            "data" => [
                "first_name" => $user['first_name'],
                "last_name" => $user['last_name'],
                "email" => $user['email'],
                "phone" => $user['phone'],
                "patient_id_formatted" => $patient_id_formatted,
                "allergies" => $user['allergies'] ?? "",
                "medical_conditions" => $user['medical_conditions'] ?? "",
                "is_priority" => (bool)$user['is_priority']
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
