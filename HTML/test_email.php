<?php
/**
 * Production-safe SMTP smoke test.
 *
 * Run from the browser with:
 *   /HTML/test_email.php?token=YOUR_TOKEN&to=you@example.com
 *
 * - Reads SMTP credentials from config.php (env-driven, no secrets in this file).
 * - No SMTP debug transcript is emitted to the browser.
 * - Direct access is blocked by .htaccess unless a matching token is supplied.
 * - The recipient defaults to a safe local address but can be overridden with ?to=
 *   (only when the request also carries the valid token).
 */
require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;

// --- Gate: require a matching token ---------------------------------------
$expected = $test_email_token ?? '';
$provided = $_GET['token']    ?? '';
if ($expected === '' || !hash_equals($expected, $provided)) {
    http_response_code(404);
    exit('Not found.');
}

// --- Recipient: from ?to= or safe default ---------------------------------
$defaultTo = 'rowelnajera.bhlsc@gmail.com';
$to        = filter_var($_GET['to'] ?? $defaultTo, FILTER_VALIDATE_EMAIL);
if (!$to) {
    http_response_code(400);
    exit('Invalid recipient.');
}

// --- Send ------------------------------------------------------------------
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = $mail_config['host'];
$mail->SMTPAuth   = true;
$mail->Username   = $mail_config['username'];
$mail->Password   = $mail_config['password'];
$mail->Port       = $mail_config['port'];
$mail->Timeout    = 15;
$mail->SMTPSecure = $mail_config['secure'] === 'ssl'
    ? PHPMailer::ENCRYPTION_SMTPS
    : PHPMailer::ENCRYPTION_STARTTLS;

$mail->setFrom($mail_config['from_email'], $mail_config['from_name']);
$mail->addAddress($to);
$mail->Subject = 'SMTP Test';
$mail->Body    = 'This is a test email.';

try {
    $mail->send();
    echo "Email sent to {$to}.";
} catch (Exception $e) {
    http_response_code(500);
    // Generic message only — never echo ErrorInfo to anonymous callers.
    echo "Email send failed.";
}
