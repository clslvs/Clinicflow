<?php
/**
 * ClinicFlow - Local Environment & API Test Suite
 */

require_once __DIR__ . '/../backend/db.php';

$results = [];

function runCheck($name, $callback) {
    global $results;
    try {
        $res = $callback();
        $results[] = ['name' => $name, 'status' => 'PASS', 'details' => $res];
        echo "  [PASS] $name" . ($res ? " - $res" : "") . "\n";
    } catch (\Throwable $t) {
        $results[] = ['name' => $name, 'status' => 'FAIL', 'details' => $t->getMessage()];
        echo "  [FAIL] $name - " . $t->getMessage() . "\n";
    }
}

function httpGetJson($url) {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n",
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ];
    $ctx = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        throw new Exception("Unable to connect to $url");
    }
    $data = json_decode($raw, true);
    if ($data === null) {
        throw new Exception("Invalid JSON returned: " . substr($raw, 0, 100));
    }
    return $data;
}

function httpPostJson($url, $payload) {
    $content = json_encode($payload);
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\nContent-Length: " . strlen($content) . "\r\n",
            'content' => $content,
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ];
    $ctx = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        throw new Exception("Unable to POST to $url");
    }
    $data = json_decode($raw, true);
    if ($data === null) {
        throw new Exception("Invalid JSON returned: " . substr($raw, 0, 100));
    }
    return $data;
}

echo "=== ClinicFlow Local Functional & DB Verification ===\n\n";

// 1. Database & Table Integrity
echo "1. Database & Table Integrity:\n";
runCheck("Database Connection", function() use ($pdo) {
    $ver = $pdo->query("SELECT VERSION()")->fetchColumn();
    return "MySQL $ver";
});

runCheck("Table: users", function() use ($pdo) {
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    return "$count users found";
});

runCheck("Table: system_settings", function() use ($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $clinicName = $settings['clinic_name'] ?? 'Not set';
    $open = $settings['open_time'] ?? '09:00';
    $close = $settings['close_time'] ?? '18:00';
    return "Clinic: $clinicName ($open - $close)";
});

runCheck("Table: appointments", function() use ($pdo) {
    $count = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    return "$count appointments";
});

runCheck("Table: doctors", function() use ($pdo) {
    $count = $pdo->query("SELECT COUNT(*) FROM doctors WHERE is_active = 1")->fetchColumn();
    return "$count active doctors";
});

runCheck("Table: services", function() use ($pdo) {
    $count = $pdo->query("SELECT COUNT(*) FROM services WHERE is_active = 1")->fetchColumn();
    return "$count active services";
});

// 2. Local Apache & HTTP Endpoints
echo "\n2. Local HTTP & REST API Verification:\n";

$baseUrl = 'http://clinicflow.test';

runCheck("HTTP: Landing page (index.html)", function() use ($baseUrl) {
    $content = @file_get_contents("$baseUrl/index.html");
    if (!$content) throw new Exception("Failed to load index.html");
    if (strpos($content, 'NgipinSolution') === false) throw new Exception("NgipinSolution branding missing");
    if (strpos($content, 'lucide.min.js') === false) throw new Exception("lucide.min.js missing");
    return "Page loads with NgipinSolution branding & Lucide script";
});

runCheck("HTTP: Login page (login.html)", function() use ($baseUrl) {
    $content = @file_get_contents("$baseUrl/login.html");
    if (!$content) throw new Exception("Failed to load login.html");
    if (strpos($content, 'NgipinSolution') === false) throw new Exception("Branding missing");
    return "Login page loads successfully";
});

runCheck("HTTP API: get_public_settings.php", function() use ($baseUrl) {
    $data = httpGetJson("$baseUrl/backend/get_public_settings.php");
    if (!isset($data['success']) || !$data['success']) {
        throw new Exception("Endpoint returned success = false");
    }
    $settings = $data['settings'];
    return "Clinic Name: {$settings['clinic_name']}, Phone: {$settings['clinic_phone']}";
});

runCheck("HTTP API: get_active_services.php", function() use ($baseUrl) {
    $data = httpGetJson("$baseUrl/backend/get_active_services.php");
    if (!isset($data['success']) || !$data['success']) {
        throw new Exception("Endpoint returned success = false");
    }
    $count = count($data['services']);
    return "$count services returned";
});

runCheck("HTTP API: get_public_queue.php", function() use ($baseUrl) {
    $data = httpGetJson("$baseUrl/backend/get_public_queue.php");
    if (!isset($data['success']) || !$data['success']) {
        throw new Exception("Endpoint returned success = false");
    }
    return "Current live queue length: " . $data['queue_length'];
});

// 3. Authentication & Security Logic
echo "\n3. Authentication & Security Logic:\n";

runCheck("Auth: Reject wrong password", function() use ($baseUrl) {
    $res = httpPostJson("$baseUrl/backend/login.php", [
        'email' => 'patient@clinicflow.com',
        'password' => 'definitely_wrong_pwd',
        'role' => 'patient'
    ]);
    if (isset($res['success']) && $res['success'] === false) {
        return "Correctly rejected: " . ($res['message'] ?? 'Failed');
    }
    throw new Exception("Wrong password was not rejected: " . json_encode($res));
});

runCheck("Auth: Accept valid patient login", function() use ($baseUrl) {
    $res = httpPostJson("$baseUrl/backend/login.php", [
        'email' => 'patient@clinicflow.com',
        'password' => 'admin123',
        'role' => 'patient'
    ]);
    if (isset($res['success']) && $res['success'] === true && ($res['role'] ?? '') === 'patient') {
        return "Authenticated as Patient (role: {$res['role']})";
    }
    throw new Exception("Patient login failed: " . json_encode($res));
});

runCheck("Auth: Accept valid admin login", function() use ($baseUrl) {
    $res = httpPostJson("$baseUrl/backend/login.php", [
        'email' => 'imadmin123@gmail.com',
        'password' => 'admin123',
        'role' => 'staff'
    ]);
    if (isset($res['success']) && $res['success'] === true && ($res['role'] ?? '') === 'admin') {
        return "Authenticated as Admin (role: {$res['role']})";
    }
    throw new Exception("Admin login failed: " . json_encode($res));
});

runCheck("Auth: Accept valid staff login", function() use ($baseUrl) {
    $res = httpPostJson("$baseUrl/backend/login.php", [
        'email' => 'admin@clinicflow.com',
        'password' => 'admin123',
        'role' => 'staff'
    ]);
    if (isset($res['success']) && $res['success'] === true && ($res['role'] ?? '') === 'staff') {
        return "Authenticated as Staff (role: {$res['role']})";
    }
    throw new Exception("Staff login failed: " . json_encode($res));
});

runCheck("Auth: Accept valid doctor login", function() use ($baseUrl) {
    $res = httpPostJson("$baseUrl/backend/login.php", [
        'email' => 'doctor@clinicflow.com',
        'password' => 'admin123',
        'role' => 'staff'
    ]);
    if (isset($res['success']) && $res['success'] === true && ($res['role'] ?? '') === 'doctor') {
        return "Authenticated as Doctor (role: {$res['role']})";
    }
    throw new Exception("Doctor login failed: " . json_encode($res));
});

// Summary
$passCount = count(array_filter($results, fn($r) => $r['status'] === 'PASS'));
$failCount = count(array_filter($results, fn($r) => $r['status'] === 'FAIL'));
echo "\n===============================================\n";
echo "Test Suite Results: $passCount Passed, $failCount Failed\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
