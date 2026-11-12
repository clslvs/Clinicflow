<?php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data provided."]);
    exit;
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = $data['role'] ?? 'patient'; // The role selected on the login page

// Rate limiting: max 5 failed attempts per 5 minutes per session
$maxAttempts = 5;
$lockoutSeconds = 300;

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_first_attempt'] = time();
}

if ($_SESSION['login_attempts'] >= $maxAttempts) {
    if ((time() - $_SESSION['login_first_attempt']) < $lockoutSeconds) {
        $remaining = $lockoutSeconds - (time() - $_SESSION['login_first_attempt']);
        echo json_encode([
            "success" => false,
            "message" => "Too many failed login attempts. Please wait " . ceil($remaining / 60) . " minute(s) before trying again."
        ]);
        exit;
    } else {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_first_attempt'] = time();
    }
}

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (!empty($user['is_deleted'])) {
            echo json_encode(["success" => false, "message" => "Account not found or has been deleted."]);
            exit;
        }

        if ($user['is_active'] == 0) {
            echo json_encode(["success" => false, "message" => "Your account has been deactivated. Please contact an administrator."]);
            exit;
        }

        if (password_verify($password, $user['password'])) {

            if ($user['role'] !== 'admin') {
                if ($role === 'staff' && !in_array($user['role'], ['staff', 'doctor'])) {
                    echo json_encode(["success" => false, "message" => "Your account is not registered as Staff or Doctor."]);
                    exit;
                } else if ($role === 'patient' && $user['role'] !== 'patient') {
                    echo json_encode(["success" => false, "message" => "Your account is not registered as a Patient."]);
                    exit;
                }
            }

            // Reset failed login tracking on success
            unset($_SESSION['login_attempts'], $_SESSION['login_first_attempt']);

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $midInitial = !empty($user['middle_name']) ? ' ' . mb_substr($user['middle_name'], 0, 1) . '.' : '';
            $_SESSION['user_name'] = $user['first_name'] . $midInitial . ' ' . $user['last_name'];

            echo json_encode([
                "success" => true,
                "message" => "Login successful!",
                "role" => $user['role']
            ]);
            exit;
        }
    }

    // Authentication failed: increment attempt counter and return generic message
    $_SESSION['login_attempts']++;
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
