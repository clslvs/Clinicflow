<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) {
    echo json_encode(["success" => false, "message" => "Patient ID required."]);
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

    $patStmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, allergies, medical_conditions FROM users WHERE id = ?");
    $patStmt->execute([$patient_id]);
    $patient = $patStmt->fetch();

    if (!$patient) {
        echo json_encode(["success" => false, "message" => "Patient not found."]);
        exit;
    }

    $histStmt = $pdo->prepare("
        SELECT a.id, a.service, a.appointment_date, a.appointment_time, a.status, a.notes, a.doctor_notes,
               d.name as doctor_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ? AND a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $histStmt->execute([$patient_id, $doctor_id]);
    $history = $histStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "patient" => $patient,
        "history" => $history
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
