<?php
require __DIR__ . '/config.php';

// Same token gate as probe.php / test_email.php
$expected = $test_email_token ?? '';
$provided = $_GET['token']    ?? '';
if ($expected === '' || !hash_equals($expected, $provided)) {
    http_response_code(404);
    exit('Not found.');
}

header('Content-Type: text/plain');

$targets = [
    ['smtp.gmail.com', 465],
    ['smtp.gmail.com', 587],
];

foreach ($targets as [$host, $port]) {
    echo "Testing {$host}:{$port} ... ";
    $start = microtime(true);
    $fp = @fsockopen($host, $port, $errno, $errstr, 8);
    $elapsed = round((microtime(true) - $start) * 1000);

    if ($fp) {
        echo "CONNECTED ({$elapsed}ms)\n";
        fclose($fp);
    } else {
        echo "FAILED — errno={$errno} errstr={$errstr} ({$elapsed}ms)\n";
    }
}

echo "\nIf both failed (or hung until timeout), outbound to Gmail is blocked at the\n";
echo "network level and Option 1/2 (sending as infoadmin@beehome.ph via Google) will\n";
echo "not work no matter how the alias/app-password side is configured.\n";
echo "\nDelete this file once you've read the output.\n";
