<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "User ID is required."]);
    exit;
}

if ($id == $_SESSION['user_id']) {
    echo json_encode(["success" => false, "message" => "You cannot delete your own admin account."]);
    exit;
}

try {
    // Ensure is_deleted column exists on users table
    try {
        $pdo->query("SELECT is_deleted FROM users LIMIT 1");
    } catch (PDOException $colErr) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE");
    }

    $stmt = $pdo->prepare("SELECT id, role, linked_doctor_id, email FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit;
    }

    $pdo->beginTransaction();

    // Scramble email to free up original address for re-registration and permanently disable login credentials
    $scrambledEmail = 'deleted_' . $user['id'] . '_' . time() . '@deleted.clinicflow';
    $delStmt = $pdo->prepare("UPDATE users SET is_deleted = 1, is_active = 0, email = ?, password = '' WHERE id = ?");
    $delStmt->execute([$scrambledEmail, $id]);

    // If user is linked to a doctor profile, soft-deactivate the doctor profile
    if ($user['role'] === 'doctor' || !empty($user['linked_doctor_id'])) {
        $docId = $user['linked_doctor_id'];
        if ($docId) {
            try {
                $pdo->prepare("UPDATE doctors SET is_active = 0 WHERE id = ?")->execute([$docId]);
            } catch (PDOException $docErr) {
                // Table column might not exist or doctor already deactivated
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "User permanently deleted while historical records remain intact."]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
