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

// Must have a valid, unexpired registration session (set by reg_token_check.php on register.php)
if (
    !isset($_SESSION['reg_valid']) || $_SESSION['reg_valid'] !== true
    || !isset($_SESSION['reg_expires']) || time() > $_SESSION['reg_expires']
) {
    unset($_SESSION['reg_valid'], $_SESSION['reg_expires'], $_SESSION['reg_token']);
    header("Location: /register");
    exit();
}

$username = trim($_POST['username']);
$email = trim($_POST['email']);

/* ============================================================
   DIAGNOSTIC LOGGING
   Toggle on with: ?debug=1 on the URL that hits this script.
   Writes to /HTML/registration_debug.log (same folder).
   ============================================================ */
$DEBUG = isset($_GET['debug']) && $_GET['debug'] == '1';
$LOG_FILE = __DIR__ . '/registration_debug.log';

function reglog($msg)
{
    global $DEBUG, $LOG_FILE;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    if ($DEBUG) {
        error_log($line);
    }
}

reglog("--- registration attempt ---");
reglog("username={$username} email={$email} ip=" . ($_SERVER['REMOTE_ADDR'] ?? '?'));

// Verify log file is writable. If not, fall back to PHP error_log only.
$logWritable = is_writable($LOG_FILE) || (is_writable(dirname($LOG_FILE)) && !file_exists($LOG_FILE));
if (!$logWritable && $DEBUG) {
    error_log("REGISTRATION DEBUG: log file not writable at {$LOG_FILE}");
}

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

    // SMTP Settings — credentials come from config.php (env-driven).
    // Switches to local sendmail when MAIL_DRIVER=sendmail (host blocks outbound SMTP).
    if ($mail_config['driver'] === 'sendmail') {
        $mail->isSendmail();
    } else {
        $mail->Host       = $mail_config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mail_config['username'];
        $mail->Password   = $mail_config['password'];
        $mail->SMTPSecure = $mail_config['secure'] === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $mail_config['port'];
    }

    $mail->Timeout = 30;

    // Pipe SMTP conversation into our log when ?debug=1
    if ($DEBUG) {
        $mail->SMTPDebug = 2; // 2 = client + server transcript
        $mail->Debugoutput = function ($str, $level) {
            reglog("SMTP[{$level}]: {$str}");
        };
    }

    // Optional (helps debugging)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    // Sender & Recipient
    $mail->setFrom($mail_config['from_email'], $mail_config['from_name']);
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
    reglog("PHPMailer: send() returned true. To={$email}");

    // Insert user after successful email
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, temp_password) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    if ($stmt->execute()) {

        reglog("DB: user inserted id=" . $stmt->insert_id);

        // Mark the registration token as used so the link cannot be reused
        if (isset($_SESSION['reg_token'])) {
            $usedStmt = $conn->prepare("UPDATE reg_tokens SET used = 1, used_at = NOW() WHERE token = ?");
            $usedStmt->bind_param("s", $_SESSION['reg_token']);
            $usedStmt->execute();
        }
        unset($_SESSION['reg_valid'], $_SESSION['reg_expires'], $_SESSION['reg_token']);

        $_SESSION['message'] = "Registration successful! Please check your email.";
        $_SESSION['msg_type'] = "success";

    } else {

        reglog("DB ERROR: " . $stmt->error);
        $_SESSION['message'] = "Database Error: " . $stmt->error;
        $_SESSION['msg_type'] = "error";
    }

} catch (Exception $e) {

    reglog("MAIL EXCEPTION: " . $mail->ErrorInfo);
    $_SESSION['message'] = "Email Error: " . $mail->ErrorInfo;
    $_SESSION['msg_type'] = "error";
}

header("Location: /register");
exit();
