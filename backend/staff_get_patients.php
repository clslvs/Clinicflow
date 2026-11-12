<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'admin'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {

    // 1. Fetch all patients and their completed visit count in a single query
    $baseSelect = "
        SELECT 
            u.id, u.first_name, u.middle_name, u.last_name, u.sex, u.email, u.phone, u.allergies, u.medical_conditions, u.is_priority,
            COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as total_visits
        FROM users u
        LEFT JOIN appointments a ON u.id = a.patient_id
    ";
    try {
        $stmt = $pdo->prepare($baseSelect . " WHERE u.role = 'patient' AND (u.is_deleted = 0 OR u.is_deleted IS NULL) GROUP BY u.id ORDER BY u.first_name ASC");
        $stmt->execute();
    } catch (PDOException $colErr) {
        $stmt = $pdo->prepare($baseSelect . " WHERE u.role = 'patient' GROUP BY u.id ORDER BY u.first_name ASC");
        $stmt->execute();
    }
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch primary (most frequent) doctor for each patient in a single grouped query
    $docStmt = $pdo->query("
        SELECT a.patient_id, d.name as doctor_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.status = 'completed'
        GROUP BY a.patient_id, a.doctor_id
        ORDER BY COUNT(a.id) DESC
    ");
    $docMap = [];
    while ($row = $docStmt->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($docMap[$row['patient_id']])) {
            $docMap[$row['patient_id']] = $row['doctor_name'];
        }
    }

    $result = [];
    foreach ($patients as $p) {
        $pt_id = "#PT-" . str_pad($p['id'], 4, '0', STR_PAD_LEFT);
        $mid = !empty($p['middle_name']) ? ' ' . mb_substr($p['middle_name'], 0, 1) . '.' : '';

        $result[] = [
            "id" => $p['id'],
            "pt_id" => $pt_id,
            "name" => $p['first_name'] . $mid . ' ' . $p['last_name'],
            "sex" => $p['sex'] ?? 'Not specified',
            "email" => $p['email'],
            "phone" => $p['phone'],
            "allergies" => !empty($p['allergies']) ? $p['allergies'] : 'None',
            "medical_conditions" => !empty($p['medical_conditions']) ? $p['medical_conditions'] : 'None',
            "is_priority" => (bool)($p['is_priority'] ?? false),
            "total_visits" => (int)$p['total_visits'],
            "primary_doctor" => $docMap[$p['id']] ?? "None assigned"
        ];
    }

    echo json_encode(["success" => true, "patients" => $result]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
