<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'admin'])) {
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
$notes = $data['notes'] ?? null;
$follow_up_date = $data['follow_up_date'] ?? null;
$follow_up_service = $data['follow_up_service'] ?? 'Follow-up';

$allowed_statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(["success" => false, "message" => "Invalid status"]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE appointments SET status = ?, notes = ? WHERE id = ?");
    $stmt->execute([$status, $notes, $appointment_id]);

    if ($status === 'completed' && !empty($follow_up_date)) {

        $currStmt = $pdo->prepare("SELECT patient_id, doctor_id FROM appointments WHERE id = ?");
        $currStmt->execute([$appointment_id]);
        $currAppt = $currStmt->fetch(PDO::FETCH_ASSOC);

        if ($currAppt) {
            $newStmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, service, appointment_date) VALUES (?, ?, ?, ?)");
            $newStmt->execute([$currAppt['patient_id'], $currAppt['doctor_id'], $follow_up_service, $follow_up_date]);
        }
    }

    echo json_encode(["success" => true, "message" => "Status updated"]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
