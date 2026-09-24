<?php
// backend/staff_update_queue_status.php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'staff') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['appointment_id']) || !isset($data['status'])) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

$appointment_id = $data['appointment_id'];
$status = $data['status']; // Should be 'in_progress', 'completed', or 'cancelled'

$allowed_statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(["success" => false, "message" => "Invalid status"]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $appointment_id]);

    echo json_encode(["success" => true, "message" => "Status updated"]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
