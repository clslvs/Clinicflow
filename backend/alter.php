<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Direct web access forbidden.");
}
require __DIR__ . '/db.php';
$pdo->exec("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending'");
echo "Success";
?>
