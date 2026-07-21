<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

$mail->SMTPDebug = 2;
$mail->Debugoutput = 'html';
$mail->Timeout = 15;

$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'adminstmp@beehome.ph';
$mail->Password = 'qipizhdiflzyczly'; // 16-char Google App Password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom('infoadmin@beehome.ph', 'Bee Home');
$mail->addAddress('rowelnajera.bhlsc@gmail.com');

$mail->Subject = 'SMTP Test';
$mail->Body = 'This is a test email.';

try {
    $mail->send();
    echo "Email sent!";
} catch (Exception $e) {
    echo "Error: " . $mail->ErrorInfo;
}