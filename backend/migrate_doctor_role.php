<?php
require 'db.php';
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'staff', 'admin', 'doctor') NOT NULL DEFAULT 'patient'");

    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (first_name, last_name, email, phone, password, role, linked_doctor_id) VALUES ('John', 'Doe', 'doctor@clinicflow.com', '09123456789', '$password', 'doctor', 1) ON DUPLICATE KEY UPDATE role='doctor', linked_doctor_id=1");

    echo "Migration successful.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
