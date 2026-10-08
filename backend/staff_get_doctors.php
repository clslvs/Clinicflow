<?php

session_start();
require 'db.php';

header('Content-Type: application/json');



try {
    $stmt = $pdo->query("SELECT id, name, specialty, image_url FROM doctors ORDER BY name ASC");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "doctors" => $doctors]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
