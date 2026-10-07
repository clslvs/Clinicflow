<?php
// backend/get_available_times.php
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
    // 1. Check if the clinic is closed on this day
    $dateObj = new DateTime($date);
    $dayOfWeek = $dateObj->format('l'); // e.g., 'Monday'
    
    $settingsStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'closed_days' LIMIT 1");
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    if ($settings && !empty($settings['setting_value'])) {
        $closedDays = json_decode($settings['setting_value'], true);
        if (in_array($dayOfWeek, $closedDays)) {
            echo json_encode(["success" => true, "slots" => [], "message" => "Clinic is closed."]);
            exit;
        }
    }

    // 2. Get Doctor's custom availability for this day
    $availStmt = $pdo->prepare("SELECT is_available, start_time, end_time FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ?");
    $availStmt->execute([$doctor_id, $dayOfWeek]);
    $availability = $availStmt->fetch(PDO::FETCH_ASSOC);

    // If doctor hasn't set this day to available, return empty
    if (!$availability || $availability['is_available'] == 0) {
        echo json_encode(["success" => true, "slots" => [], "message" => "Doctor is not available."]);
        exit;
    }

    $start = $availability['start_time']; // e.g. "09:00:00"
    $end = $availability['end_time'];

    // Generate 30 minute slots
    $slots = [];
    $current = strtotime($date . ' ' . $start);
    $endTime = strtotime($date . ' ' . $end);

    while ($current < $endTime) {
        $slots[] = date('H:i', $current);
        $current = strtotime('+30 minutes', $current);
    }

    // 3. Get existing bookings for this doctor on this date
    $bookStmt = $pdo->prepare("
        SELECT appointment_time 
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled' AND appointment_time IS NOT NULL
    ");
    $bookStmt->execute([$doctor_id, $date]);
    $bookedTimes = $bookStmt->fetchAll(PDO::FETCH_COLUMN);

    // Format booked times to H:i
    $bookedTimes = array_map(function($t) {
        return date('H:i', strtotime($t));
    }, $bookedTimes);

    // Filter out booked slots
    $availableSlots = array_values(array_diff($slots, $bookedTimes));

    echo json_encode([
        "success" => true,
        "slots" => $availableSlots
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
