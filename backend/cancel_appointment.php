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

$appointment_id = $data['appointment_id'] ?? '';

if (empty($appointment_id)) {
    echo json_encode(["success" => false, "message" => "Appointment ID is required."]);
    exit;
}

try {

    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND patient_id = ?");
    $stmt->execute([$appointment_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Appointment cancelled successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Appointment not found, unauthorized, or already cancelled."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
