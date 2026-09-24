<?php
// db.php
// Database connection configuration for XAMPP

$host = '127.0.0.1'; // or localhost
$db   = 'clinicflow';
$user = 'root'; // default XAMPP user
$pass = ''; // default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If the database doesn't exist, this will fail.
    // Ensure you have created the database and imported database.sql in phpMyAdmin
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Database connection failed. Please ensure XAMPP MySQL is running and the 'clinicflow' database exists."]);
    exit;
}
?>
