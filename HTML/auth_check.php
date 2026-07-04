<?php
include "config.php";

// ❌ Not logged in
if (!isset($_SESSION['user_id'])) {

    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    $_SESSION['must_login'] = true;

    header("Location: login.php");
    exit();
}

// 🔥 FORCE PASSWORD CHANGE LOCK
if (isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'] === true) {

    if (basename($_SERVER['PHP_SELF']) !== 'change_password.php' &&
        basename($_SERVER['PHP_SELF']) !== 'update_password.php') {

        header("Location: change_password.php");
        exit();
    }
}
?>