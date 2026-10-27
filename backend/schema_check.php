<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Direct web access forbidden.");
}
require 'db.php';
$stmt = $pdo->query('DESCRIBE users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
