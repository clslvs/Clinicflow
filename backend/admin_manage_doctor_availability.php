<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized. Admin privileges required."]);
    exit;
}

// Self-healing: ensure doctor_availability table exists
try {
    $pdo->query("SELECT 1 FROM doctor_availability LIMIT 1");
} catch (\PDOException $tblErr) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS doctor_availability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
            start_time TIME NOT NULL DEFAULT '09:00:00',
            end_time TIME NOT NULL DEFAULT '17:00:00',
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
            UNIQUE KEY unique_doc_day (doctor_id, day_of_week)
        )");
    } catch (\Throwable $createErr) {
        // Table creation fallback
    }
}

$daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Helper to format 24h time to 12h AM/PM
function formatTime12($t) {
    if (empty($t)) return '';
    $parts = explode(':', $t);
    $hr = (int)$parts[0];
    $min = $parts[1] ?? '00';
    $ampm = $hr >= 12 ? 'PM' : 'AM';
    $hr12 = $hr % 12 ?: 12;
    return $hr12 . ($min !== '00' ? ':' . $min : '') . $ampm;
}

// Helper to build a readable summary of active days & hours
function buildAvailabilitySummary($slots) {
    if (empty($slots)) {
        return "Not Set";
    }

    $active = array_filter($slots, function($s) {
        return !empty($s['is_available']) && $s['is_available'] == 1;
    });

    if (empty($active)) {
        return "Unavailable (All Off)";
    }

    // Check if hours are uniform
    $first = reset($active);
    $allSameHours = true;
    foreach ($active as $s) {
        if ($s['start_time'] !== $first['start_time'] || $s['end_time'] !== $first['end_time']) {
            $allSameHours = false;
            break;
        }
    }

    $activeDays = array_map(function($s) {
        return substr($s['day_of_week'], 0, 3);
    }, $active);

    $hoursStr = formatTime12(substr($first['start_time'], 0, 5)) . ' - ' . formatTime12(substr($first['end_time'], 0, 5));

    if (count($active) === 6) {
        return "Mon - Sat (" . $hoursStr . ")";
    } elseif (count($active) === 5 && !in_array('Sat', $activeDays)) {
        return "Mon - Fri (" . $hoursStr . ")";
    } elseif ($allSameHours) {
        return implode(', ', $activeDays) . " (" . $hoursStr . ")";
    } else {
        return count($active) . " active days/wk";
    }
}

// -------------------------------------------------------------
// GET: Fetch availability for a specific doctor or all doctors
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;

    try {
        if ($doctor_id) {
            // Get single doctor info
            $docStmt = $pdo->prepare("SELECT id, name, specialty, image_url, is_active FROM doctors WHERE id = ?");
            $docStmt->execute([$doctor_id]);
            $doctor = $docStmt->fetch(PDO::FETCH_ASSOC);

            if (!$doctor) {
                echo json_encode(["success" => false, "message" => "Doctor not found."]);
                exit;
            }

            // Get availability rows
            $stmt = $pdo->prepare("
                SELECT day_of_week, start_time, end_time, is_available 
                FROM doctor_availability 
                WHERE doctor_id = ? 
                ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')
            ");
            $stmt->execute([$doctor_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $indexed = [];
            foreach ($rows as $r) {
                $indexed[$r['day_of_week']] = $r;
            }

            // Fill all 6 days so frontend always has complete structure
            $availability = [];
            foreach ($daysOrder as $day) {
                if (isset($indexed[$day])) {
                    $availability[] = [
                        "day_of_week"  => $day,
                        "start_time"   => substr($indexed[$day]['start_time'], 0, 5),
                        "end_time"     => substr($indexed[$day]['end_time'], 0, 5),
                        "is_available" => (int)$indexed[$day]['is_available']
                    ];
                } else {
                    $availability[] = [
                        "day_of_week"  => $day,
                        "start_time"   => "09:00",
                        "end_time"     => "17:00",
                        "is_available" => ($day === 'Saturday' ? 0 : 1) // default Mon-Fri active
                    ];
                }
            }

            $summary = buildAvailabilitySummary($availability);

            echo json_encode([
                "success"      => true,
                "doctor"       => $doctor,
                "availability" => $availability,
                "summary"      => $summary
            ]);
            exit;

        } else {
            // Get all active doctors with their availability summary
            try {
                $docsStmt = $pdo->query("SELECT id, name, specialty, image_url, is_active FROM doctors WHERE is_active = 1 ORDER BY name ASC");
            } catch (PDOException $e) {
                $docsStmt = $pdo->query("SELECT id, name, specialty, image_url FROM doctors ORDER BY name ASC");
            }
            $doctors = $docsStmt->fetchAll(PDO::FETCH_ASSOC);

            $availStmt = $pdo->query("
                SELECT doctor_id, day_of_week, start_time, end_time, is_available 
                FROM doctor_availability 
                ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')
            ");
            $allAvail = $availStmt->fetchAll(PDO::FETCH_ASSOC);

            $doctorAvailMap = [];
            foreach ($allAvail as $row) {
                $docId = $row['doctor_id'];
                if (!isset($doctorAvailMap[$docId])) {
                    $doctorAvailMap[$docId] = [];
                }
                $doctorAvailMap[$docId][] = $row;
            }

            foreach ($doctors as &$d) {
                $slots = $doctorAvailMap[$d['id']] ?? [];
                $d['availability_summary'] = buildAvailabilitySummary($slots);
                $d['active_days_count'] = count(array_filter($slots, function($s) {
                    return !empty($s['is_available']) && $s['is_available'] == 1;
                }));
            }

            echo json_encode([
                "success" => true,
                "doctors" => $doctors
            ]);
            exit;
        }

    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error loading doctor availability."]);
        exit;
    }
}

// -------------------------------------------------------------
// POST: Save availability for a specific doctor
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $doctor_id = isset($input['doctor_id']) ? (int)$input['doctor_id'] : null;
    $availability = $input['availability'] ?? null;

    if (!$doctor_id || !is_array($availability)) {
        echo json_encode(["success" => false, "message" => "Doctor ID and valid availability schedule array required."]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_available) 
            VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                start_time = VALUES(start_time), 
                end_time = VALUES(end_time), 
                is_available = VALUES(is_available)
        ");

        foreach ($availability as $slot) {
            $day = $slot['day_of_week'] ?? '';
            if (!in_array($day, $daysOrder)) continue;

            $start = !empty($slot['start_time']) ? substr($slot['start_time'], 0, 5) . ':00' : '09:00:00';
            $end   = !empty($slot['end_time']) ? substr($slot['end_time'], 0, 5) . ':00' : '17:00:00';
            $avail = (!empty($slot['is_available']) && $slot['is_available'] == 1) ? 1 : 0;

            $stmt->execute([$doctor_id, $day, $start, $end, $avail]);
        }

        $pdo->commit();

        echo json_encode([
            "success" => true, 
            "message" => "Doctor availability schedule updated successfully."
        ]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log($e->getMessage());
        echo json_encode(["success" => false, "message" => "Failed to save doctor availability."]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid request method."]);

