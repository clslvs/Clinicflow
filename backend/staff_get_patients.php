<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'admin'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, phone, allergies, medical_conditions 
        FROM users 
        WHERE role = 'patient' 
        ORDER BY first_name ASC
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($patients as $p) {
        $pt_id = "#PT-" . str_pad($p['id'], 4, '0', STR_PAD_LEFT);

        $visitStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'completed'");
        $visitStmt->execute([$p['id']]);
        $total_visits = $visitStmt->fetchColumn();

        $docStmt = $pdo->prepare("
            SELECT d.name 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.patient_id = ? AND a.status = 'completed'
            GROUP BY d.id 
            ORDER BY COUNT(a.id) DESC 
            LIMIT 1
        ");
        $docStmt->execute([$p['id']]);
        $primary_doctor = $docStmt->fetchColumn();

        if (!$primary_doctor) {
            $primary_doctor = "None assigned";
        }

        $result[] = [
            "id" => $p['id'],
            "pt_id" => $pt_id,
            "name" => $p['first_name'] . ' ' . $p['last_name'],
            "email" => $p['email'],
            "phone" => $p['phone'],
            "allergies" => $p['allergies'] ? $p['allergies'] : 'None',
            "medical_conditions" => $p['medical_conditions'] ? $p['medical_conditions'] : 'None',
            "total_visits" => $total_visits,
            "primary_doctor" => $primary_doctor
        ];
    }

    echo json_encode(["success" => true, "patients" => $result]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
