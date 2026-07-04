<?php
$fp = fsockopen("smtpout.secureserver.net", 443, $errno, $errstr, 10);

if (!$fp) {
    echo "FAILED: $errno - $errstr";
} else {
    echo "CONNECTED!";
    fclose($fp);
}
?>