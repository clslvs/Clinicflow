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

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit;
}

try {



    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
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
        } else {
            echo json_encode(["success" => false, "message" => "Incorrect password."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Account not found."]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
