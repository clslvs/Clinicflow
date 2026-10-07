<?php
// backend/admin_save_user.php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    $data = $_POST;
}

if (!isset($data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['role'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

try {
    if (isset($data['action']) && $data['action'] === 'create_doctor') {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            echo json_encode(["success" => false, "message" => "Email already registered."]);
            exit;
        }

        $pdo->beginTransaction();

        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Handle avatar upload
        $avatarPath = 'assets/images/temporarypicdoctor.jpg';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileInfo = pathinfo($_FILES['avatar']['name']);
            $ext = strtolower($fileInfo['extension']);
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowedExts)) {
                $newFilename = uniqid('doc_') . '.' . $ext;
                $targetFile = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                    $avatarPath = 'assets/images/uploads/' . $newFilename;
                }
            }
        }

        // 1. Insert into doctors table first
        $docName = "Dr. " . $data['first_name'] . " " . $data['last_name'];
        $specialty = $data['specialty'] ?? 'General';
        
        $stmtDoc = $pdo->prepare("INSERT INTO doctors (name, specialty, image_url) VALUES (?, ?, ?)");
        $stmtDoc->execute([$docName, $specialty, $avatarPath]);
        $doctorId = $pdo->lastInsertId();

        // 2. Insert into users table with linked_doctor_id
        $stmtUser = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, role, password, linked_doctor_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtUser->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], 'doctor', $hashed, $doctorId]);

        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Doctor created successfully."]);
        exit;
    }

    if (empty($data['id'])) {
        // Create new regular user
        if (empty($data['password'])) {
            echo json_encode(["success" => false, "message" => "Password is required for new users"]);
            exit;
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            echo json_encode(["success" => false, "message" => "Email already registered."]);
            exit;
        }

        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, role, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['role'], $hashed]);
        
    } else {
        // Update existing user
        if (!empty($data['password'])) {
            $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, role=?, password=? WHERE id=?");
            $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['role'], $hashed, $data['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, role=? WHERE id=?");
            $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['role'], $data['id']]);
        }
    }

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
