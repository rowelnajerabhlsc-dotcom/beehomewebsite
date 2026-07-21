<?php
$to = 'jephthydojino@gmail.com';
$subject = 'mail transport test';
$message = 'test body';
$headers = "From: infoadmin@beehome.ph\r\n";

var_dump(ini_get('sendmail_path'));

$cmd = 'printf "Subject: mail transport test\nFrom: infoadmin@beehome.ph\nTo: '.$to.'\n\nTest body\n" | /usr/sbin/sendmail -v -finfoadmin@beehome.ph '.$to.' 2>&1';
exec($cmd, $output, $code);

echo "<pre>";
echo "Exit code: ".$code."\n";
echo implode("\n", $output);
echo "</pre>";