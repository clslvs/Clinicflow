<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$firstName = trim($data['first_name'] ?? '');
$middleName = !empty($data['middle_name']) ? trim($data['middle_name']) : null;
$lastName = trim($data['last_name'] ?? '');
$sex = !empty($data['sex']) && in_array($data['sex'], ['Male', 'Female', 'Other']) ? $data['sex'] : null;
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');

if (empty($firstName) || empty($lastName) || empty($email)) {
    echo json_encode(["success" => false, "message" => "First name, last name, and email are required."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit;
}

if (!empty($phone) && !preg_match('/^[0-9]{11}$/', $phone)) {
    echo json_encode(["success" => false, "message" => "Phone number must be exactly 11 digits (e.g., 09123456789)."]);
    exit;
}

try {
    // Check if email exists for another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email is already in use by another account."]);
        exit;
    }

    // Attempt update with middle_name and sex; fallback if columns not yet migrated
    try {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, sex = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$firstName, $middleName, $lastName, $sex, $email, $phone, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $email, $phone, $_SESSION['user_id']]);
    }

    $_SESSION['user_name'] = $firstName . ($middleName ? ' ' . $middleName : '') . ' ' . $lastName;

    echo json_encode(["success" => true, "message" => "Personal information updated successfully."]);
} catch (PDOException $e) {
    error_log($e->getMessage()); 
    echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
