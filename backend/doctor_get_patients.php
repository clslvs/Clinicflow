<?php
// backend/doctor_get_patients.php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
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

    // Get all unique patients who have appointments with this doctor
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            CONCAT(u.first_name, ' ', u.last_name) as name,
            u.email,
            u.phone,
            u.allergies,
            u.medical_conditions,
            COUNT(a.id) as total_visits,
            MAX(a.appointment_date) as last_visit,
            MIN(CASE WHEN a.appointment_date >= CURDATE() AND a.status NOT IN ('cancelled','completed') THEN a.appointment_date END) as next_appointment
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        WHERE a.doctor_id = ?
        GROUP BY u.id
        ORDER BY last_visit DESC
    ");
    $stmt->execute([$doctor_id]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "patients" => $patients,
        "total" => count($patients)
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
