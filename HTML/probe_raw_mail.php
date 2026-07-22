<?php
/**
 * probe_raw_mail.php
 *
 * No PHPMailer at all — just PHP's native mail(), no -f envelope override,
 * no custom headers beyond the basics. This matches exactly what GoDaddy
 * support tested and confirmed works on this account.
 *
 *   /HTML/probe_raw_mail.php?token=YOUR_TOKEN&to=you@example.com
 *
 * Delete this file once you've read the result.
 */
require __DIR__ . '/config.php';

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

$subject = 'Raw mail() test — no PHPMailer, no -f override';
$body    = 'This is a plain PHP mail() test with no additional parameters at all.';
$headers = "From: infoadmin@beehome.ph\r\n";

echo "Calling mail() with no 5th parameter (no -f override)...\n\n";

$result = mail($to, $subject, $body, $headers);

echo "mail() returned: " . var_export($result, true) . "\n";
echo "Now check the actual inbox and spam folder at {$to}.\n";
