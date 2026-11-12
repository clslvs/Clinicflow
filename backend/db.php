<?php
// Ensure clinic timezone (Philippine Standard Time UTC+8) for all date/time calculations
date_default_timezone_set('Asia/Manila');

// Apply baseline security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Ensure session cookies are protected across all endpoints
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
}

// Load local configuration overrides if present
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Smart environment detection: local Laragon vs live InfinityFree hosting
$is_local = (
    php_sapi_name() === 'cli' ||
    empty($_SERVER['HTTP_HOST']) ||
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], '.test') !== false
);

$charset = 'utf8mb4';

if ($is_local) {
    $host = defined('DB_LOCAL_HOST') ? DB_LOCAL_HOST : '127.0.0.1';
    $port = defined('DB_LOCAL_PORT') ? DB_LOCAL_PORT : 3306;
    $db   = defined('DB_LOCAL_NAME') ? DB_LOCAL_NAME : 'clinicflow';
    $user = defined('DB_LOCAL_USER') ? DB_LOCAL_USER : 'root';
    $pass = defined('DB_LOCAL_PASS') ? DB_LOCAL_PASS : '';
    $dsn  = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
} else {
    $host = defined('DB_HOST') ? DB_HOST : 'sql306.infinityfree.com';
    $db   = defined('DB_NAME') ? DB_NAME : 'if0_43109570_clinicflow';
    $user = defined('DB_USER') ? DB_USER : 'if0_43109570';
    $pass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');
    $dsn  = "mysql:host=$host;dbname=$db;charset=$charset";
}
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    try {
        $pdo->exec("SET time_zone = '+08:00'");
    } catch (\Throwable $tzErr) {
        // Non-critical: ignore if database timezone setting fails
    }
} catch (\PDOException $e) {
    // If local connection failed, attempt intelligent fallbacks (localhost, or with password if set)
    if ($is_local) {
        // Fallback 1: Try host 'localhost' instead of '127.0.0.1' (or vice-versa)
        if ($host === '127.0.0.1' && !isset($pdo)) {
            try {
                $altDsn = "mysql:host=localhost;dbname=$db;charset=$charset";
                $pdo = new PDO($altDsn, $user, $pass, $options);
                try { $pdo->exec("SET time_zone = '+08:00'"); } catch (\Throwable $tzErr) {}
            } catch (\PDOException $eAlt) {
                $e = $eAlt;
            }
        }

        // Fallback 2: If empty password failed and DB_PASS is defined, try DB_PASS
        if (!isset($pdo) && empty($pass) && defined('DB_PASS') && DB_PASS !== '') {
            try {
                $pdo = new PDO($dsn, $user, DB_PASS, $options);
                try { $pdo->exec("SET time_zone = '+08:00'"); } catch (\Throwable $tzErr) {}
            } catch (\PDOException $e2) {
                // Also try with localhost + DB_PASS
                try {
                    $altDsn = "mysql:host=localhost;dbname=$db;charset=$charset";
                    $pdo = new PDO($altDsn, $user, DB_PASS, $options);
                    try { $pdo->exec("SET time_zone = '+08:00'"); } catch (\Throwable $tzErr) {}
                } catch (\PDOException $e3) {
                    $e = $e3;
                }
            }
        }
    }

    if (!isset($pdo)) {
        header('Content-Type: application/json');
        $errorDetails = $is_local ? " (" . $e->getMessage() . ")" : "";
        echo json_encode([
            "success" => false, 
            "message" => "Database connection failed. Please ensure MySQL is running in Laragon." . $errorDetails
        ]);
        exit;
    }
}
?>
