<?php
/**
 * probe_relay_send.php
 *
 * One-time test: does smtpout.secureserver.net actually deliver a message
 * where From: differs from the authenticated mailbox? This is the one
 * unverified assumption in the whole registration-link-email feature.
 *
 *   /HTML/probe_relay_send.php?token=YOUR_TEST_EMAIL_TOKEN&to=you@example.com
 *
 * Delete this file once you've confirmed the result either way.
 */
require __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Token gate (same pattern as test_email.php / probe.php) ---
$expected = $test_email_token ?? '';
$provided = $_GET['token']    ?? '';
if ($expected === '' || !hash_equals($expected, $provided)) {
    http_response_code(404);
    exit('Not found.');
}

header('Content-Type: text/plain');

$to = filter_var($_GET['to'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$to) {
    exit("Pass a valid ?to= address to receive the test message.\n");
}

if (empty($reg_mail_config['username']) || empty($reg_mail_config['password'])) {
    exit("reg_mail_config is not populated — check that secrets.php loaded (REG_MAIL_USER / REG_MAIL_PASS missing).\n");
}

echo "Authenticating as: {$reg_mail_config['username']}\n";
echo "Sending From:      {$reg_mail_config['from_email']}\n";
echo "Relay host:        {$reg_mail_config['host']}:{$reg_mail_config['port']}\n\n";

$mail = new PHPMailer(true);
$mail->SMTPDebug = 2;
$mail->Debugoutput = function ($str, $level) {
    echo "SMTP[{$level}]: {$str}";
};

try {
    $mail->isSMTP();
    $mail->Host       = $reg_mail_config['host'];
    $mail->Port       = $reg_mail_config['port'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $reg_mail_config['username'];
    $mail->Password   = $reg_mail_config['password'];
    $mail->SMTPSecure = $reg_mail_config['secure'] === 'tls'
        ? PHPMailer::ENCRYPTION_STARTTLS
        : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Timeout    = 15;

    $mail->setFrom($reg_mail_config['from_email'], $reg_mail_config['from_name']);
    $mail->addAddress($to);
    $mail->Subject = 'Relay mismatched-From test';
    $mail->Body    = 'If you received this, GoDaddy relay allows From != authenticated mailbox.';

    $mail->send();
    echo "\n\nRESULT: send() returned success. Check the inbox at {$to} (and spam folder) to confirm it actually arrived — success here only means the relay accepted it, not that it wasn't silently dropped downstream.\n";
} catch (Exception $e) {
    echo "\n\nRESULT: FAILED — " . $mail->ErrorInfo . "\n";
}
