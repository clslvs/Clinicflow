<?php

require 'db.php';
header('Content-Type: application/json');

try {

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as queue_length 
        FROM appointments 
        WHERE appointment_date = CURDATE() AND status IN ('pending', 'confirmed', 'in_progress')
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    
    echo json_encode([
        "success" => true,
        "queue_length" => (int) $result['queue_length']
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
