<?php
include "config.php";

if (isset($_SESSION['must_login'])) {
    echo "<script>alert('Please log in first.');</script>";
    unset($_SESSION['must_login']);
}

// --- Status info shown before the user attempts to log in ---
$isLockedOut = isset($_SESSION['lockout_time']) && $_SESSION['lockout_time'] > time();
$lockoutMinutesLeft = $isLockedOut ? ceil(($_SESSION['lockout_time'] - time()) / 60) : 0;

$attemptsUsed = $_SESSION['login_attempts'] ?? 0;
$attemptsRemaining = 5 - $attemptsUsed; // matches the 5-attempt threshold used in login_process.php

// "Last login" survives logout via a cookie (session is destroyed on logout).
$lastLoginDisplay = isset($_COOKIE['last_login_time'])
    ? date('M j, Y g:i A', strtotime($_COOKIE['last_login_time']))
    : null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" href="IMAGES/logo.png">
</head>
<body>


<?php include "navbar.php"; ?>

<div class="auth-container" style="min-height: 100dvh;">
    <div class="auth-card">
        <h1>Login</h1>

        <!-- LOGIN STATUS (shown before any attempt) -->
        <?php if ($isLockedOut): ?>
            <div class="error-message">
                Account temporarily locked. Try again in <?php echo $lockoutMinutesLeft; ?> minute(s).
            </div>
        <?php elseif ($attemptsUsed > 0): ?>
            <div class="status-message">
                <?php echo $attemptsRemaining; ?> login attempt(s) remaining before a temporary lockout.
            </div>
        <?php endif; ?>

        <?php if ($lastLoginDisplay): ?>
            <div class="status-message">
                Last login: <?php echo htmlspecialchars($lastLoginDisplay); ?>
            </div>
        <?php endif; ?>

        <!-- ERROR MESSAGE -->
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="error-message">
                <?php 
                    echo $_SESSION['login_error']; 
                    unset($_SESSION['login_error']); 
                ?>
            </div>
        <?php endif; ?>

        <form action="/login_process" method="POST">

            <label>Email:</label>
            <input type="email" name="email" required <?php echo $isLockedOut ? 'disabled' : ''; ?>>

            <label>Password:</label>
            <div class="password-wrapper">
                <input type="password" name="password" required <?php echo $isLockedOut ? 'disabled' : ''; ?>>
                <span class="toggle-password">👁️</span>
            </div>

            <button type="submit" <?php echo $isLockedOut ? 'disabled' : ''; ?>>Login</button>
        </form>
    </div>
</div>

<script>
document.querySelectorAll(".toggle-password").forEach(icon => {
    icon.addEventListener("click", function () {
        const input = this.previousElementSibling;

        if (input.type === "password") {
            input.type = "text";
            this.textContent = "🙈";
        } else {
            input.type = "password";
            this.textContent = "👁️";
        }
    });
});
</script>

</body>
</html>
