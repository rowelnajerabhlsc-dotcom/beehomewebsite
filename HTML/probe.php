<?php
/**
 * Diagnostic: where is sendmail, is proc_open available, and what does
 * PHP's mail config say? Read the output to figure out why isSendmail()
 * isn't reaching exim on this host.
 *
 *   /HTML/probe.php?token=YOUR_TEST_EMAIL_TOKEN
 */
require __DIR__ . '/config.php';

// --- Token gate (same as test_email.php) ---
$expected = $test_email_token ?? '';
$provided = $_GET['token']    ?? '';
if ($expected === '' || !hash_equals($expected, $provided)) {
    http_response_code(404);
    exit('Not found.');
}

header('Content-Type: text/plain');

echo "PHP version: " . PHP_VERSION . "\n";
echo "sendmail_path (php.ini): " . (ini_get('sendmail_path') ?: '(not set)') . "\n";
echo "SMTP (php.ini): " . (ini_get('SMTP') ?: '(not set)') . "\n";
echo "smtp_port (php.ini): " . (ini_get('smtp_port') ?: '(not set)') . "\n";

echo "\ndisable_functions: " . (ini_get('disable_functions') ?: '(none)') . "\n";

echo "\n-- Looking for sendmail / exim binaries --\n";
foreach ([
    '/usr/sbin/sendmail',
    '/usr/local/bin/sendmail',
    '/usr/bin/sendmail',
    '/usr/sbin/exim',
    '/usr/local/sbin/exim',
    '/usr/bin/exim',
] as $bin) {
    $exists = file_exists($bin);
    $exec   = $exists && is_executable($bin);
    echo str_pad($bin, 30) . ($exec ? '  OK (executable)' : ($exists ? '  exists but NOT executable' : '  missing')) . "\n";
}

echo "\n-- PHP function checks --\n";
foreach (['proc_open', 'exec', 'shell_exec', 'popen', 'system', 'passthru'] as $fn) {
    echo str_pad($fn, 15) . (function_exists($fn) ? '  available' : '  BLOCKED') . "\n";
}

echo "\nPATH (from this PHP process): " . (getenv('PATH') ?: '(empty)') . "\n";

echo "\n-- Done. Delete this file once you've read the output. --\n";
