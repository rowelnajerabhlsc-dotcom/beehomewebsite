<?php
session_start();
require 'config.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: /register");
    exit();
}

// Must have a valid registration session (set by reg_token_check.php on register.php)
if (
    !isset($_SESSION['reg_valid']) || $_SESSION['reg_valid'] !== true
    || !isset($_SESSION['reg_expires']) || time() > $_SESSION['reg_expires']
) {
    unset($_SESSION['reg_valid'], $_SESSION['reg_expires'], $_SESSION['reg_token']);
    header("Location: /register");
    exit();
}

$username        = trim($_POST['username'] ?? '');
$email           = trim($_POST['email'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

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

reglog("--- registration attempt --- username={$username} email={$email} ip=" . ($_SERVER['REMOTE_ADDR'] ?? '?'));

/* Basic field checks */
if ($username === '' || $email === '') {
    $_SESSION['message'] = "Username and email are required.";
    $_SESSION['msg_type'] = "error";
    header("Location: /register");
    exit();
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

/* Password validation */
if (strlen($password) < 8) {
    $_SESSION['message'] = "Password must be at least 8 characters.";
    $_SESSION['msg_type'] = "error";
    header("Location: /register");
    exit();
}

if ($password !== $confirmPassword) {
    $_SESSION['message'] = "Passwords do not match.";
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

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

/* Insert user — temp_password = 0 since the user chose their own password */
$stmt = $conn->prepare("INSERT INTO users (username, email, password, temp_password) VALUES (?, ?, ?, 0)");
$stmt->bind_param("sss", $username, $email, $hashedPassword);

if ($stmt->execute()) {

    $new_user_id = $stmt->insert_id;
    reglog("DB: user inserted id=" . $new_user_id);

    // Create the matching user_profiles row right away, so this account
    // is never orphaned (a bug we hit where accounts existed in `users`
    // but had no user_profiles row, silently breaking profile edits).
    $profileStmt = $conn->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
    $profileStmt->bind_param("i", $new_user_id);
    if (!$profileStmt->execute()) {
        // Don't fail the whole registration over this — but log it loudly,
        // since a failure here reproduces the orphaned-account bug.
        reglog("DB ERROR creating user_profiles row for user_id={$new_user_id}: " . $profileStmt->error);
    }
    $profileStmt->close();

    // Mark the registration token as used so the link cannot be reused
    if (isset($_SESSION['reg_token'])) {
        $usedStmt = $conn->prepare("UPDATE reg_tokens SET used = 1, used_at = NOW() WHERE token = ?");
        $usedStmt->bind_param("s", $_SESSION['reg_token']);
        $usedStmt->execute();
    }
    unset($_SESSION['reg_valid'], $_SESSION['reg_expires'], $_SESSION['reg_token']);

    $_SESSION['just_registered']     = true;
    $_SESSION['registered_username'] = $username;

    header("Location: /registration_success");
    exit();

} else {

    reglog("DB ERROR: " . $stmt->error);
    $_SESSION['message'] = "Database Error: " . $stmt->error;
    $_SESSION['msg_type'] = "error";
}

header("Location: /register");
exit();