<?php

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

$firstName = trim($data['firstName'] ?? '');
$middleName = !empty($data['middleName']) ? trim($data['middleName']) : null;
$lastName = trim($data['lastName'] ?? '');
$sex = !empty($data['sex']) && in_array($data['sex'], ['Male', 'Female', 'Other']) ? $data['sex'] : null;
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$password = $data['password'] ?? '';
$role = $data['role'] ?? 'patient';
$is_priority = !empty($data['is_priority']) ? 1 : 0;

if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($password)) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit;
}

if (!preg_match('/^[0-9]{11}$/', $phone)) {
    echo json_encode(["success" => false, "message" => "Phone number must be exactly 11 digits (e.g., 09123456789)."]);
    exit;
}

if (empty($data['terms_agreed'])) {
    echo json_encode(["success" => false, "message" => "You must agree to the Terms & Conditions and Privacy Policy."]);
    exit;
}

$role = 'patient'; // Public registration is strictly for patients

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(["success" => false, "message" => "Email is already registered."]);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, sex, email, phone, password, role, is_priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$firstName, $middleName, $lastName, $sex, $email, $phone, $hashedPassword, $role, $is_priority]);

    $userId = $pdo->lastInsertId();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_name'] = $firstName . ($middleName ? ' ' . $middleName : '') . ' ' . $lastName;

    echo json_encode([
        "success" => true, 
        "message" => "Registration successful!",
        "role" => $role
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
