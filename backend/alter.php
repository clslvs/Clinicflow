<?php
require 'e:\XAMPP\htdocs\clinicflow\backend\db.php';
$pdo->exec("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending'");
echo "Success";
?>
