<?php
// Smart environment detection: local Laragon vs live InfinityFree hosting
$is_local = (
    php_sapi_name() === 'cli' ||
    empty($_SERVER['HTTP_HOST']) ||
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], '.test') !== false
);

if ($is_local) {
    $host = '127.0.0.1';
    $db   = 'clinicflow';
    $user = 'root';
    $pass = '';
} else {
    $host = 'sql306.infinityfree.com';
    $db   = 'if0_43109570_clinicflow';
    $user = 'if0_43109570';
    $pass = 'Naksnaman12';
}

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
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed. Please ensure MySQL is running."
    ]);
    exit;
}
?>
