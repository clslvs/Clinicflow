<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? '';
$date = $_GET['date'] ?? '';

if (empty($doctor_id) || empty($date)) {
    echo json_encode(["success" => false, "message" => "Doctor ID and date are required."]);
    exit;
}

try {

    $dateObj = new DateTime($date);
    $dayOfWeek = $dateObj->format('l'); // e.g., 'Monday'
    
    $settingsStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'closed_days' LIMIT 1");
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    if ($settings && !empty($settings['setting_value'])) {
        $closedDays = json_decode($settings['setting_value'], true);
        if (in_array($dayOfWeek, $closedDays)) {
            echo json_encode(["success" => true, "slots" => [], "times" => [], "message" => "Clinic is closed."]);
            exit;
        }
    }

    // Verify that the doctor exists and is active
    $docStmt = $pdo->prepare("SELECT id, name FROM doctors WHERE id = ? AND is_active = 1");
    $docStmt->execute([$doctor_id]);
    $doctorRow = $docStmt->fetch();
    if (!$doctorRow) {
        echo json_encode(["success" => true, "slots" => [], "times" => [], "message" => "Doctor is not currently available."]);
        exit;
    }

    $availStmt = $pdo->prepare("SELECT is_available, start_time, end_time FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ?");
    $availStmt->execute([$doctor_id, $dayOfWeek]);
    $availability = $availStmt->fetch(PDO::FETCH_ASSOC);

    if ($availability) {
        if ($availability['is_available'] == 0) {
            echo json_encode(["success" => true, "slots" => [], "times" => [], "message" => "Doctor is not available on {$dayOfWeek}s."]);
            exit;
        }
        $start = $availability['start_time'];
        $end   = $availability['end_time'];
    } else {
        // Fallback to clinic operating hours from system_settings if no specific schedule configured yet
        $timesStmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('open_time', 'close_time')");
        $clinicTimes = $timesStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $start = !empty($clinicTimes['open_time']) ? $clinicTimes['open_time'] . ':00' : '09:00:00';
        $end   = !empty($clinicTimes['close_time']) ? $clinicTimes['close_time'] . ':00' : '17:00:00';
    }

    $slots = [];
    $current = strtotime($date . ' ' . $start);
    $endTime = strtotime($date . ' ' . $end);

    while ($current < $endTime) {
        $slots[] = date('H:i', $current);
        $current = strtotime('+30 minutes', $current);
    }

    $bookStmt = $pdo->prepare("
        SELECT appointment_time 
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled' AND appointment_time IS NOT NULL
    ");
    $bookStmt->execute([$doctor_id, $date]);
    $bookedTimes = $bookStmt->fetchAll(PDO::FETCH_COLUMN);

    $bookedTimes = array_map(function($t) {
        return date('H:i', strtotime($t));
    }, $bookedTimes);

    $availableSlots = array_values(array_diff($slots, $bookedTimes));

    // If booking for today, omit slots that have already passed
    if ($date === date('Y-m-d')) {
        $now = time();
        $availableSlots = array_values(array_filter($availableSlots, function($t) use ($date, $now) {
            return strtotime($date . ' ' . $t) > $now;
        }));
    }

    echo json_encode([
        "success" => true,
        "slots" => $availableSlots,
        "times" => $availableSlots
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage()); echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
}
?>
