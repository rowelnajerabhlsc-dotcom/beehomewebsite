<?php
session_start();
require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: /register");
    exit();
}

$username = trim($_POST['username']);
$email = trim($_POST['email']);

/* Validate email format */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = "Invalid email format.";
    $_SESSION['msg_type'] = "error";
    header("Location: /register");
    exit();
}

/* Check if domain exists */
$domain = substr(strrchr($email, "@"), 1);
if (!checkdnsrr($domain, "MX")) {
    $_SESSION['message'] = "Email domain is not valid.";
    $_SESSION['msg_type'] = "error";
    header("Location: /register");
    exit();
}

/* Check duplicate email */
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['message'] = "Email already registered.";
    $_SESSION['msg_type'] = "error";
    header("Location: /register");
    exit();
}

/* Generate temporary password */
$tempPassword = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
$hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

/* Create mail object */
$mail = new PHPMailer(true);

try {

    // SMTP Settings
   $mail->Host = 'smtp.titan.email';
$mail->SMTPAuth = true;
$mail->Username = 'infoadmin@beehome.ph';
$mail->Password = 'Beehome@2011';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

    $mail->Timeout = 30;

    // Optional (helps debugging)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    // Sender & Recipient
    $mail->setFrom('infoadmin@beehome.ph', 'Bee Home Labor Multipurpose Cooperative');
    $mail->addAddress($email, $username);

    // Email Content
    $mail->isHTML(true);
    $mail->Subject = 'Your Temporary Password';

    $mail->Body = "
        <h2>Welcome to Bee Home Labor Multipurpose Cooperative</h2>

        <p>Hello <strong>{$username}</strong>,</p>

        <p>Your account has been successfully created.</p>

        <p>
            <strong>Temporary Password:</strong>
            {$tempPassword}
        </p>

        <p>Please login and change your password immediately.</p>

        <p>
            <a href='https://beehome.ph/login'>
                Click here to Login
            </a>
        </p>

        <br>

        <p>Thank you!</p>
    ";

    // Send Email
    $mail->send();

    // Insert user after successful email
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, temp_password) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    if ($stmt->execute()) {

        $_SESSION['message'] = "Registration successful! Please check your email.";
        $_SESSION['msg_type'] = "success";

    } else {

        $_SESSION['message'] = "Database Error: " . $stmt->error;
        $_SESSION['msg_type'] = "error";
    }

} catch (Exception $e) {

    $_SESSION['message'] = "Email Error: " . $mail->ErrorInfo;
    $_SESSION['msg_type'] = "error";
}

header("Location: /register");
exit();
