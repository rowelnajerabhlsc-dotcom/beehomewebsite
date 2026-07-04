<?php
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Initialize session variables if not set
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }

    if (!isset($_SESSION['lockout_time'])) {
        $_SESSION['lockout_time'] = 0;
    }

    // Check if user is still locked out
    if ($_SESSION['lockout_time'] > time()) {

        $remaining = $_SESSION['lockout_time'] - time();
        $minutes = ceil($remaining / 60);

        $_SESSION['login_error'] =
            "Too many failed attempts. Please try again in {$minutes} minute(s).";

        header("Location: login.php");
        exit();
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepared statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {

            // Reset failed attempts on successful login
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lockout_time'] = 0;

            // Sessions
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];

            // Force password change
            if (!empty($row['temp_password'])) {
                $_SESSION['force_password_change'] = true;
                header("Location: change_password.php");
                exit();
            }

            // Redirect
            if (isset($_SESSION['redirect_to'])) {
                $redirect = $_SESSION['redirect_to'];
                unset($_SESSION['redirect_to']);
                header("Location: $redirect");
            } else {
                header("Location:/");
            }

            exit();
        }
    }

    // Failed login
    $_SESSION['login_attempts']++;

    // Lock account for 5 minutes after 5 failed attempts
    if ($_SESSION['login_attempts'] >= 3) {

        $_SESSION['lockout_time'] = time() + (5 * 60); // 5 minutes
        $_SESSION['login_attempts'] = 0;

        $_SESSION['login_error'] =
            "Too many failed attempts. Please wait 5 minutes before trying again.";

    } else {

        $remainingAttempts = 5 - $_SESSION['login_attempts'];

        $_SESSION['login_error'] =
            "Invalid email or password. {$remainingAttempts} attempt(s) remaining.";
    }

    header("Location: login.php");
    exit();
}
?>