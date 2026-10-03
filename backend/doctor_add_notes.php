<?php
// backend/doctor_add_notes.php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$appointment_id = $input['appointment_id'] ?? null;
$notes = $input['doctor_notes'] ?? '';

if (!$appointment_id) {
    echo json_encode(["success" => false, "message" => "Appointment ID required."]);
    exit;
}

try {
    $userStmt = $pdo->prepare("SELECT linked_doctor_id FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $userRow = $userStmt->fetch();

    if (!$userRow || empty($userRow['linked_doctor_id'])) {
        echo json_encode(["success" => false, "message" => "Doctor profile not linked."]);
        exit;
    }

    $doctor_id = $userRow['linked_doctor_id'];

    // Verify this appointment belongs to this doctor
    $stmt = $pdo->prepare("UPDATE appointments SET doctor_notes = ? WHERE id = ? AND doctor_id = ?");
    $stmt->execute([$notes, $appointment_id, $doctor_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Treatment notes saved."]);
    } else {
        echo json_encode(["success" => false, "message" => "Appointment not found or not yours."]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
