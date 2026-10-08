<?php



$host = '127.0.0.1'; // or localhost
$db   = 'clinicflow';
$user = 'root'; // default Laragon user
$pass = ''; // default Laragon password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Set Timezone for PHP and MySQL so it matches your local time (Philippines +08:00)
    date_default_timezone_set('Asia/Manila');
    $pdo->exec("SET time_zone = '+08:00';");
    
} catch (\PDOException $e) {


    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Database connection failed. Please ensure Laragon MySQL is running and the 'clinicflow' database exists."]);
    exit;
}
?>
