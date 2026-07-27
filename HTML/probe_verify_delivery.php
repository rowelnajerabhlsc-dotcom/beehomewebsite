<?php
/**
 * probe_verify_delivery.php
 *
 * Final verification that mail() actually delivers now that the account's
 * spam block has been lifted. Uses a natural, full-sentence subject and
 * body with no trigger words, per GoDaddy support's guidance.
 *
 *   /HTML/probe_verify_delivery.php?token=YOUR_TOKEN&to=you@example.com
 *
 * Delete this file (and all other probe_*.php files) once confirmed working.
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

$subject = 'Your account update from Bee Home Labor Multipurpose Cooperative';
$body    = "Good day,\n\nThis message confirms that outbound mail delivery from our website is now working correctly.\n\nThank you.";
$headers = "From: Bee Home Labor Multipurpose Cooperative <infoadmin@beehome.ph>\r\n";

$result = mail($to, $subject, $body, $headers);

echo "mail() returned: " . var_export($result, true) . "\n";
echo "Check the inbox (and spam folder, just in case) at {$to}.\n";
