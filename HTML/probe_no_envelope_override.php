<?php
/**
 * probe_no_envelope_override.php
 *
 * Diagnostic: identical to previous tests, EXCEPT $mail->Sender is never
 * set at all — letting PHP's mail() use its default envelope sender
 * instead of forcing one via -f. GoDaddy support confirmed plain mail()
 * works when they tested it; our failing tests all explicitly set
 * Sender, which adds a -f flag that cPanel's wrapper may be silently
 * rejecting.
 *
 *   /HTML/probe_no_envelope_override.php?token=YOUR_TOKEN&to=you@example.com
 *
 * Delete this file once you've read the result.
 */
require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;

$expected = $test_email_token ?? '';
$provided = $_GET['token']    ?? '';
if ($expected === '' || !hash_equals($expected, $provided)) {
    http_response_code(404);
    exit('Not found.');
}

$to = filter_var($_GET['to'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$to) {
    http_response_code(400);
    exit('Pass a valid ?to=');
}

header('Content-Type: text/plain');

$mail = new PHPMailer(true);
$mail->isMail();

$mail->SMTPDebug  = 2;
$mail->Debugoutput = function ($str, $level) {
    echo "SMTP[{$level}]: {$str}\n";
};

$mail->setFrom($mail_config['from_email'], $mail_config['from_name']);
// Deliberately NOT setting $mail->Sender this time — no -f override.

$mail->addAddress($to);
$mail->Subject = 'No envelope override test';
$mail->Body    = 'Testing plain mail() behavior without a forced -f envelope sender.';

try {
    $mail->send();
    echo "\nRESULT: Email handed off successfully to {$to}.\n";
    echo "Check Track Delivery (if it ever populates) and the actual inbox/spam folder.\n";
} catch (Exception $e) {
    echo "\nRESULT: FAILED — " . $mail->ErrorInfo . "\n";
}
