<?php

session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

// =============================================================================
// EMAIL CONFIGURATION (100% COMPATIBLE WITH INFINITYFREE)
// =============================================================================
// InfinityFree blocks standard PHP mail() and SMTP ports (25, 465, 587).
// To send emails 100% free and reliably on InfinityFree, we use the Brevo (Sendinblue)
// REST API which operates over standard HTTPS (Port 443) via cURL.
//
// Free tier: 300 emails/day forever (no credit card required).
//
// Quick Setup:
// 1. Sign up for free at https://www.brevo.com
// 2. Go to: Profile / Settings -> SMTP & API -> API Keys -> Generate a new API key.
// 3. Paste your API key below and set your sender email (must be a verified email in Brevo).
// =============================================================================
// Load credentials from untracked local config file if present, else fallback to constants
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
if (!defined('BREVO_API_KEY')) define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');
if (!defined('SENDER_EMAIL'))  define('SENDER_EMAIL', getenv('SENDER_EMAIL') ?: '');
if (!defined('SENDER_NAME'))   define('SENDER_NAME', getenv('SENDER_NAME') ?: 'ClinicFlow Dental Care');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data provided."]);
    exit;
}

$email = trim($data['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Please provide a valid email address."]);
    exit;
}

// Rate limiting: allow at most one request every 60 seconds per session
if (isset($_SESSION['last_reset_request']) && (time() - $_SESSION['last_reset_request']) < 60) {
    echo json_encode([
        "success" => false,
        "message" => "Please wait 60 seconds before requesting another password reset."
    ]);
    exit;
}
$_SESSION['last_reset_request'] = time();

try {
    // 1. Find user by email
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || (isset($user['is_active']) && $user['is_active'] == 0)) {
        // Generic success message to prevent account enumeration
        echo json_encode([
            "success" => true,
            "message" => "If an account matches that email address, password reset instructions have been sent. Please check your inbox and spam folder."
        ]);
        exit;
    }

    // 2. Ensure Email service is active before resetting credentials
    if (BREVO_API_KEY === 'YOUR_BREVO_API_KEY_HERE' || empty(BREVO_API_KEY)) {
        echo json_encode([
            "success" => false,
            "message" => "Automated password reset is currently unavailable. Please contact the clinic administrator to reset your password."
        ]);
        exit;
    }

    // 3. Generate a secure, readable temporary password (e.g. CF-9K3p7X)
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $randomCode = '';
    for ($i = 0; $i < 6; $i++) {
        $randomCode .= $chars[random_int(0, strlen($chars) - 1)];
    }
    $new_password = 'CF-' . $randomCode;

    $userName = trim($user['first_name'] . ' ' . $user['last_name']);

    // Build Email HTML Content
    $emailSubject = "Your New ClinicFlow Password";
    $emailHtml = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4fbfb; margin: 0; padding: 20px; color: #333; }
            .container { max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e0f2f1; }
            .header { background: linear-gradient(135deg, #0C7C77, #329692); padding: 30px 20px; text-align: center; color: #ffffff; }
            .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
            .content { padding: 30px 25px; line-height: 1.6; }
            .pwd-box { background: #E2F4F3; border: 2px dashed #0C7C77; border-radius: 10px; padding: 15px; text-align: center; margin: 20px 0; }
            .pwd-code { font-size: 24px; font-weight: 700; color: #0C7C77; letter-spacing: 2px; }
            .note { font-size: 13px; color: #666; margin-top: 15px; }
            .footer { background: #fafafa; padding: 15px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>ClinicFlow Dental Care</h1>
            </div>
            <div class="content">
                <p>Hello <strong>' . htmlspecialchars($userName) . '</strong>,</p>
                <p>We received a request to reset your ClinicFlow account password. A new temporary password has been generated for you:</p>
                
                <div class="pwd-box">
                    <div style="font-size: 12px; text-transform: uppercase; color: #0C7C77; font-weight: 600; margin-bottom: 5px;">Your Temporary Password</div>
                    <div class="pwd-code">' . htmlspecialchars($new_password) . '</div>
                </div>

                <p>Please log in using this temporary password and update it immediately in your account settings.</p>
                <p class="note">If you did not request this password reset, please secure your account or reach out to clinic staff right away.</p>
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' ClinicFlow Dental Care. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    // Prepare Brevo API Payload
    $payload = [
        "sender" => [
            "name" => SENDER_NAME,
            "email" => SENDER_EMAIL
        ],
        "to" => [
            [
                "email" => $user['email'],
                "name" => $userName
            ]
        ],
        "subject" => $emailSubject,
        "htmlContent" => $emailHtml
    ];

    // cURL to Brevo REST API endpoint (HTTPS Port 443)
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . BREVO_API_KEY,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to connect to email service: " . $curlError
        ]);
        exit;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        // Only update database when email has been verified as sent!
        $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$hashed_new, $user['id']]);

        echo json_encode([
            "success" => true,
            "message" => "If an account matches that email address, password reset instructions have been sent. Please check your inbox and spam folder."
        ]);
    } else {
        $respData = json_decode($response, true);
        $errMsg = $respData['message'] ?? 'Unable to send email via Brevo.';
        error_log("Brevo API error: " . $errMsg);
        echo json_encode([
            "success" => false,
            "message" => "Unable to send reset email at this time. Please try again later or contact the clinic."
        ]);
    }

} catch (PDOException $e) {
    error_log($e->getMessage()); 
    echo json_encode(["success" => false, "message" => "A database error occurred. Please try again later."]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred while processing your request. Please try again later."]);
}
?>
