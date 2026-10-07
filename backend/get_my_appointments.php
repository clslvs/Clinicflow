<?php
// backend/get_my_appointments.php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch all appointments for the patient, join with doctors to get doctor name
    $stmt = $pdo->prepare("
        SELECT 
            a.id, 
            a.doctor_id,
            a.service, 
            a.appointment_date, 
            a.appointment_time, 
            a.status,
            a.notes,
            d.name as doctor_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "appointments" => $appointments
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
