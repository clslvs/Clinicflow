<?php
// backend/get_active_services.php
require 'db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, name FROM services WHERE is_active = TRUE ORDER BY name ASC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "services" => $services]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
