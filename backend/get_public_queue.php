<?php
// backend/get_public_queue.php
require 'db.php';
header('Content-Type: application/json');

try {
    // Count how many patients are waiting or currently being seen today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as queue_length 
        FROM appointments 
        WHERE appointment_date = CURDATE() AND status IN ('pending', 'confirmed', 'in_progress')
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Also check if the clinic is currently "open" based on system settings (optional, keeping it simple for now)
    
    echo json_encode([
        "success" => true,
        "queue_length" => (int) $result['queue_length']
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
