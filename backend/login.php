<?php
// backend/login.php
session_start();
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
    // Check if user exists with the specified email AND role
    // (If they try to login as admin but their role is patient, this will block it, 
    // or we can just fetch by email and check role after. Fetch by email is better.)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_active'] == 0) {
            echo json_encode(["success" => false, "message" => "Your account has been deactivated. Please contact an administrator."]);
            exit;
        }

        // Verify password
        if (password_verify($password, $user['password'])) {
            
            // If the user is an admin, let them login regardless of which radio button they chose
            if ($user['role'] !== 'admin') {
                // Check if the role they selected matches their actual database role
                // Since "Staff / Admin" radio submits "staff", allow staff, admin, and doctor
                if ($role === 'staff' && !in_array($user['role'], ['staff', 'doctor'])) {
                    echo json_encode(["success" => false, "message" => "Your account is not registered as Staff or Doctor."]);
                    exit;
                } else if ($role === 'patient' && $user['role'] !== 'patient') {
                    echo json_encode(["success" => false, "message" => "Your account is not registered as a Patient."]);
                    exit;
                }
            }

            // Login successful, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

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
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
