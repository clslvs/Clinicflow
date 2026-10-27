<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Direct web access forbidden.");
}
require 'db.php';

try {
    $pdo->exec("ALTER TABLE appointments ADD COLUMN notes TEXT DEFAULT NULL;");
    echo "appointments table altered successfully with notes.\n";
} catch (PDOException $e) {
    echo "Error altering appointments: " . $e->getMessage() . "\n";
}
?>
