<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

$mail->SMTPDebug = 2;
$mail->Debugoutput = 'html';
$mail->Timeout = 15;

$mail->isSMTP();
$mail->Host = 'smtp.office365.com';
$mail->SMTPAuth = true;
$mail->Username = 'infoadmin@beehome.ph';
$mail->Password = 'YOUR_EMAIL_PASSWORD';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom('infoadmin@beehome.ph', 'Bee Home');
$mail->addAddress('YOUR_PERSONAL_EMAIL@example.com');

$mail->Subject = 'SMTP Test';
$mail->Body = 'This is a test email.';

try {
    $mail->send();
    echo "Email sent!";
} catch (Exception $e) {
    echo "Error: " . $mail->ErrorInfo;
}