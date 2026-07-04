<?php

$fp = fsockopen("smtp.secureserver.net", 587, $errno, $errstr, 10);

if (!$fp) {
    echo "Connection failed<br>";
    echo "Error Number: $errno<br>";
    echo "Error: $errstr";
} else {
    echo "Connected successfully!";
    fclose($fp);
}