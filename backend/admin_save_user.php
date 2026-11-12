<?php

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

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            echo json_encode(["success" => false, "message" => "Email already registered."]);
            exit;
        }

        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);

        $avatarPath = 'assets/images/temporarypicdoctor.jpg';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            // Cap upload size at 2MB
            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                echo json_encode(["success" => false, "message" => "Avatar image must be under 2MB."]);
                exit;
            }

            // Verify content is a genuine image (magic bytes)
            $imageInfo = @getimagesize($_FILES['avatar']['tmp_name']);
            if ($imageInfo === false) {
                echo json_encode(["success" => false, "message" => "Uploaded file is not a valid image."]);
                exit;
            }

            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowedMimes[$imageInfo['mime']])) {
                echo json_encode(["success" => false, "message" => "Only JPG, PNG, and WebP images are allowed."]);
                exit;
            }

            $uploadDir = '../assets/images/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Derive extension purely from validated MIME type
            $ext = $allowedMimes[$imageInfo['mime']];
            $newFilename = uniqid('doc_') . '.' . $ext;
            $targetFile = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                $avatarPath = 'assets/images/uploads/' . $newFilename;
            }
        }

        $pdo->beginTransaction();

        $docName = "Dr. " . $data['first_name'] . " " . $data['last_name'];
        $specialty = $data['specialty'] ?? 'General';
        
        $stmtDoc = $pdo->prepare("INSERT INTO doctors (name, specialty, image_url) VALUES (?, ?, ?)");
        $stmtDoc->execute([$docName, $specialty, $avatarPath]);
        $doctorId = $pdo->lastInsertId();

        $stmtUser = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, role, password, linked_doctor_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtUser->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], 'doctor', $hashed, $doctorId]);

        // Seed default weekday availability (Monday-Friday 09:00 - 17:00, Saturday off)
        $availStmt = $pdo->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_available) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time), is_available = VALUES(is_available)");
        $defaultDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        foreach ($defaultDays as $day) {
            $avail = ($day === 'Saturday') ? 0 : 1;
            $availStmt->execute([$doctorId, $day, '09:00:00', '17:00:00', $avail]);
        }

        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Doctor created successfully."]);
        exit;
    }

    $isPriority = !empty($data['is_priority']) ? 1 : 0;

    if (empty($data['id'])) {

        if (empty($data['password'])) {
            echo json_encode(["success" => false, "message" => "Password is required for new users"]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            echo json_encode(["success" => false, "message" => "Email already registered."]);
            exit;
        }

        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, role, password, is_priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['role'], $hashed, $isPriority]);
        
    } else {

        if (!empty($data['password'])) {
            $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, role=?, is_priority=?, password=? WHERE id=?");
            $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['role'], $isPriority, $hashed, $data['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, role=?, is_priority=? WHERE id=?");
            $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['role'], $isPriority, $data['id']]);
        }
    }

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>