<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = !empty($_POST) ? $_POST : json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? ($_GET['action'] ?? '');

try {
    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO doctors (name, specialty) VALUES (?, ?)");
        $stmt->execute([$data['name'], $data['specialty']]);
        echo json_encode(["success" => true]);
    } else if ($action === 'update_doctor') {
        $docId = (int)($data['doctor_id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $specialty = trim($data['specialty'] ?? '');

        if (!$docId || empty($name) || empty($specialty)) {
            echo json_encode(["success" => false, "message" => "Doctor ID, Name, and Specialty are required."]);
            exit;
        }

        // Verify doctor exists
        $stmt = $pdo->prepare("SELECT id, image_url FROM doctors WHERE id = ?");
        $stmt->execute([$docId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            echo json_encode(["success" => false, "message" => "Doctor not found."]);
            exit;
        }

        $avatarPath = $existing['image_url'];

        // Handle optional avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                echo json_encode(["success" => false, "message" => "Avatar image must be under 2MB."]);
                exit;
            }

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

            $ext = $allowedMimes[$imageInfo['mime']];
            $newFilename = uniqid('doc_') . '.' . $ext;
            $targetFile = $uploadDir . $newFilename;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                $avatarPath = 'assets/images/uploads/' . $newFilename;
            }
        }

        $pdo->beginTransaction();

        $upd = $pdo->prepare("UPDATE doctors SET name = ?, specialty = ?, image_url = ? WHERE id = ?");
        $upd->execute([$name, $specialty, $avatarPath, $docId]);

        // Also sync linked user if present
        $cleanName = preg_replace('/^Dr\.\s*/i', '', $name);
        $parts = explode(' ', $cleanName, 2);
        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? '';

        $updUser = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE linked_doctor_id = ?");
        $updUser->execute([$firstName, $lastName, $docId]);

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Doctor profile updated successfully.",
            "doctor" => [
                "id" => $docId,
                "name" => $name,
                "specialty" => $specialty,
                "image_url" => $avatarPath
            ]
        ]);
        exit;
    } else if ($action === 'delete') {
        // Soft delete doctor so patient medical and appointment history is preserved
        try {
            $stmt = $pdo->prepare("UPDATE doctors SET is_active = 0 WHERE id = ?");
            $stmt->execute([$data['id']]);
        } catch (PDOException $colErr) {
            // Gracefully add column if not yet present in target database
            $pdo->exec("ALTER TABLE doctors ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
            $stmt = $pdo->prepare("UPDATE doctors SET is_active = 0 WHERE id = ?");
            $stmt->execute([$data['id']]);
        }
        echo json_encode(["success" => true]);
    } else if ($action === 'get_availability') {
        $docId = $data['doctor_id'] ?? ($_GET['doctor_id'] ?? null);
        if (!$docId) {
            echo json_encode(["success" => false, "message" => "Doctor ID is required."]);
            exit;
        }

        $docStmt = $pdo->prepare("SELECT id, name, specialty FROM doctors WHERE id = ?");
        $docStmt->execute([$docId]);
        $doctor = $docStmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) {
            echo json_encode(["success" => false, "message" => "Doctor not found."]);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT day_of_week, start_time, end_time, is_available 
            FROM doctor_availability 
            WHERE doctor_id = ? 
            ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')
        ");
        $stmt->execute([$docId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $availMap = [];
        foreach ($rows as $r) {
            $availMap[$r['day_of_week']] = $r;
        }

        $availability = [];
        foreach ($daysOrder as $dName) {
            if (isset($availMap[$dName])) {
                $availability[] = [
                    "day_of_week"  => $dName,
                    "start_time"   => substr($availMap[$dName]['start_time'], 0, 5),
                    "end_time"     => substr($availMap[$dName]['end_time'], 0, 5),
                    "is_available" => (int)$availMap[$dName]['is_available']
                ];
            } else {
                $availability[] = [
                    "day_of_week"  => $dName,
                    "start_time"   => "09:00",
                    "end_time"     => "17:00",
                    "is_available" => ($dName === 'Saturday' ? 0 : 1)
                ];
            }
        }

        echo json_encode([
            "success" => true,
            "doctor" => $doctor,
            "availability" => $availability
        ]);
    } else if ($action === 'save_availability') {
        $docId = $data['doctor_id'] ?? null;
        $slots = $data['availability'] ?? null;

        if (!$docId || !is_array($slots)) {
            echo json_encode(["success" => false, "message" => "Invalid availability data."]);
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_available) 
            VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time), is_available = VALUES(is_available)
        ");

        foreach ($slots as $slot) {
            $day = $slot['day_of_week'];
            $start = $slot['start_time'] ?? '09:00:00';
            $end = $slot['end_time'] ?? '17:00:00';
            $available = !empty($slot['is_available']) ? 1 : 0;

            $stmt->execute([$docId, $day, $start, $end, $available]);
        }

        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Doctor availability saved successfully."]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
