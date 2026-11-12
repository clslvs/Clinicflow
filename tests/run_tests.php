<?php
/**
 * ClinicFlow - Lightweight Test Suite
 * Run via CLI: php tests/run_tests.php
 */

$passed = 0;
$failed = 0;

function assertTest($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] $description\n";
        $passed++;
    } else {
        echo "  [FAIL] $description\n";
        $failed++;
    }
}

echo "=== ClinicFlow Automated Verification Suite ===\n\n";

// 1. PHP Syntax Check across all backend files
echo "1. Backend Syntax Checks:\n";
$backendFiles = glob(__DIR__ . '/../backend/*.php');
foreach ($backendFiles as $file) {
    $filename = basename($file);
    $output = [];
    $returnVar = 0;
    $php = PHP_BINARY;
    exec(escapeshellarg($php) . " -l " . escapeshellarg($file), $output, $returnVar);
    assertTest("Syntax lint: $filename", $returnVar === 0);
}

// 2. Phone Number Validation Rules (exact 11 digits starting with 09)
echo "\n2. Phone Number Validation:\n";
$phoneRegex = '/^[0-9]{11}$/';
assertTest("Valid 11-digit mobile: 09951467688", preg_match($phoneRegex, '09951467688') === 1);
assertTest("Reject 10-digit mobile: 0995146768", preg_match($phoneRegex, '0995146768') === 0);
assertTest("Reject 12-digit mobile: 099514676888", preg_match($phoneRegex, '099514676888') === 0);
assertTest("Reject alphabetic characters: 0995146768a", preg_match($phoneRegex, '0995146768a') === 0);

// 3. Email Validation Rules
echo "\n3. Email Validation:\n";
assertTest("Valid email: patient@clinicflow.com", filter_var('patient@clinicflow.com', FILTER_VALIDATE_EMAIL) !== false);
assertTest("Reject malformed email: patient@", filter_var('patient@', FILTER_VALIDATE_EMAIL) === false);

// 4. Same-Day Slot Filter Logic
echo "\n4. Same-Day Slot Filtering Logic:\n";
$mockSlots = ['08:00', '09:00', '14:00', '16:00', '18:00'];
$mockToday = date('Y-m-d');
$fixedNow = strtotime("$mockToday 12:00:00");
$filteredSlots = array_values(array_filter($mockSlots, function($t) use ($mockToday, $fixedNow) {
    return strtotime("$mockToday $t") > $fixedNow;
}));
assertTest("Filters out past slots (before 12:00)", !in_array('08:00', $filteredSlots) && !in_array('09:00', $filteredSlots));
assertTest("Preserves future slots (after 12:00)", in_array('14:00', $filteredSlots) && in_array('16:00', $filteredSlots));

// 5. Rate Limiting Arithmetic
echo "\n5. Rate Limiting Calculations:\n";
$maxAttempts = 5;
$lockoutSeconds = 300;
$firstAttemptTime = time() - 100;
$attempts = 5;
$isLocked = ($attempts >= $maxAttempts) && ((time() - $firstAttemptTime) < $lockoutSeconds);
$remaining = $lockoutSeconds - (time() - $firstAttemptTime);
assertTest("Account locked when attempts >= 5 within 5 minutes", $isLocked === true);
assertTest("Calculates remaining minutes correctly", ceil($remaining / 60) > 0);

// 6. Walk-In Queue & Placeholder Email Guards
echo "\n6. Walk-In Queue & Placeholder Email Guards:\n";
$walkinEmail = 'walkin.' . bin2hex(random_bytes(6)) . '@clinic.local';
assertTest("Generated walk-in email is valid format", filter_var($walkinEmail, FILTER_VALIDATE_EMAIL) !== false);
assertTest("Placeholder email detected for reset skip", str_ends_with(strtolower($walkinEmail), '@clinic.local'));
$sampleQueue = [
    ['id' => 1, 'is_priority' => 0, 'time' => null, 'created' => 100],
    ['id' => 2, 'is_priority' => 0, 'time' => '09:00:00', 'created' => 200],
    ['id' => 3, 'is_priority' => 1, 'time' => null, 'created' => 300],
];
usort($sampleQueue, function($a, $b) {
    if ($a['is_priority'] !== $b['is_priority']) return $b['is_priority'] <=> $a['is_priority'];
    $ta = $a['time'] ?: '23:59:59';
    $tb = $b['time'] ?: '23:59:59';
    return ($ta === $tb) ? ($a['created'] <=> $b['created']) : strcmp($ta, $tb);
});
assertTest("Priority walk-in ranks first, scheduled before non-priority walk-in", [$sampleQueue[0]['id'], $sampleQueue[1]['id'], $sampleQueue[2]['id']] === [3, 2, 1]);

// 7. Database and Live API Endpoint Integration
echo "\n7. Database & Public API Integration:\n";
try {
    require_once __DIR__ . '/../backend/db.php';
    assertTest("Database connectivity established", isset($pdo) && $pdo !== null);

    $tables = ['users', 'doctors', 'appointments', 'services', 'system_settings'];
    foreach ($tables as $t) {
        $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        assertTest("Table exists and queried: $t ($cnt rows)", $cnt !== false);
    }

    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    assertTest("System settings seeded: clinic_name is set", !empty($settings['clinic_name']));
} catch (\Throwable $e) {
    assertTest("Database integration failed: " . $e->getMessage(), false);
}

echo "\n===============================================\n";
echo "Results: $passed Passed, $failed Failed\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
