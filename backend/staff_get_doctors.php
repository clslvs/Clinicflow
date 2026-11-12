<?php

session_start();
require 'db.php';

header('Content-Type: application/json');



try {
    try {
        $stmt = $pdo->query("SELECT id, name, specialty, image_url FROM doctors WHERE is_active = 1 ORDER BY name ASC");
    } catch (PDOException $e) {
        $stmt = $pdo->query("SELECT id, name, specialty, image_url FROM doctors ORDER BY name ASC");
    }
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Load weekly availability for each doctor
    try {
        $availStmt = $pdo->query("
            SELECT doctor_id, day_of_week, start_time, end_time, is_available 
            FROM doctor_availability 
            ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')
        ");
        $allAvail = $availStmt->fetchAll(PDO::FETCH_ASSOC);
        $availMap = [];
        foreach ($allAvail as $av) {
            $availMap[$av['doctor_id']][] = $av;
        }

        foreach ($doctors as &$doc) {
            $doc['availability'] = $availMap[$doc['id']] ?? [];
        }
        unset($doc);
    } catch (PDOException $availErr) {
        foreach ($doctors as &$doc) {
            $doc['availability'] = [];
        }
        unset($doc);
    }
    
    echo json_encode(["success" => true, "doctors" => $doctors]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
