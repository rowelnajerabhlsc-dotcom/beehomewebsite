<?php
include "config.php";
include "log_helper.php";

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

        header("Location: /login");
        exit();
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Remember the attempted email so login.php can show status
    // (e.g. lockout countdown) tied to this address even before a
    // successful login exists.
    $_SESSION['last_attempted_email'] = $email;

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

            // --- Login logging ---
            $now = date('Y-m-d H:i:s');
            $_SESSION['login_time'] = $now;
            $_SESSION['last_login'] = $row['last_login'] ?? null; // previous login, for display

            // Persist to DB (users.last_login + user_logs history row)
            $update = $conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
            if ($update) {
                $update->bind_param("si", $now, $row['id']);
                $update->execute();
                $update->close();
            }
            log_user_event($conn, 'login_success', $row['id'], $row['email']);

            // Also drop a cookie so login.php can show "Last login: ..."
            // on this browser even after the session is destroyed on logout.
            setcookie('last_login_time', $now, time() + (60 * 60 * 24 * 30), '/');

            // Force password change
            if (!empty($row['temp_password'])) {
                $_SESSION['force_password_change'] = true;
                header("Location: /change_password");
                exit();
            }

            // Redirect
            if (isset($_SESSION['redirect_to'])) {
                $redirect = $_SESSION['redirect_to'];
                unset($_SESSION['redirect_to']);
                header("Location: $redirect");
            } else {
                header("Location: /");
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

        // --- Lockout logging ---
        // user_id is unknown/irrelevant here (could be a bad email), so we
        // log by the attempted email only.
        log_user_event($conn, 'lockout', null, $email);

    } else {

        $remainingAttempts = 5 - $_SESSION['login_attempts'];

        $_SESSION['login_error'] =
            "Invalid email or password. {$remainingAttempts} attempt(s) remaining.";
    }

    header("Location: /login");
    exit();
}
?>
