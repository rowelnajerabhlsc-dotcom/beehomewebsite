<?php
include "config.php";
include "log_helper.php";

// Capture identity + log the logout BEFORE the session is destroyed,
// since $_SESSION won't exist anymore afterward.
$user_id = $_SESSION['user_id'] ?? null;
$email   = $_SESSION['email'] ?? null;

if ($user_id) {
    log_user_event($conn, 'logout', $user_id, $email);
    setcookie('last_login_time', $_SESSION['login_time'] ?? '', time() + (60 * 60 * 24 * 30), '/');
}

session_unset();
session_destroy();

header("Location:/");
exit();
?>
