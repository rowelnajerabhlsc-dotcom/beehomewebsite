<?php
/**
 * probe_envelope_match.php
 *
 * Diagnostic variant of test_email.php — identical except the envelope
 * sender (-f) is set to infoadmin@beehome.ph instead of the cPanel
 * account address, to test whether a mismatch between envelope sender
 * and From: header is why exim is silently dropping mail.
 *
 * Note: infoadmin@beehome.ph is NOT a local mailbox on this server (it
 * lives on Google Workspace), so this may fail differently than before —
 * e.g. an outright rejection instead of a silent drop. That's useful
 * diagnostic information either way.
 *
 *   /HTML/probe_envelope_match.php?token=YOUR_TOKEN&to=you@example.com
 *
 * Delete this file once you've read the result.
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

header('Content-Type: text/plain');

// --- Send ------------------------------------------------------------------
$mail = new PHPMailer(true);
$mail->isMail(); // same local sendmail path as test_email.php

$mail->SMTPDebug  = 2;
$mail->Debugoutput = function ($str, $level) {
    echo "SMTP[{$level}]: {$str}\n";
};

$mail->setFrom($mail_config['from_email'], $mail_config['from_name']);

// The one deliberate change from test_email.php: envelope sender now
// matches the visible From address exactly, instead of the cPanel account.
$mail->Sender = $mail_config['from_email']; // infoadmin@beehome.ph

$mail->addAddress($to);
$mail->Subject = 'Envelope-match test';
$mail->Body    = 'Testing whether envelope sender must match From: exactly.';

try {
    $mail->send();
    echo "\nRESULT: Email handed off successfully to {$to}.\n";
    echo "Now check cPanel Track Delivery and the actual inbox/spam folder.\n";
} catch (Exception $e) {
    echo "\nRESULT: FAILED — " . $mail->ErrorInfo . "\n";
}
