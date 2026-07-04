<?php
include "config.php";

if (isset($_SESSION['must_login'])) {
    echo "<script>alert('Please log in first.');</script>";
    unset($_SESSION['must_login']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Login</h1>

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
            <input type="email" name="email" required>

            <label>Password:</label>
            <div class="password-wrapper">
                <input type="password" name="password" required>
                <span class="toggle-password">👁️</span>
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="auth-link">
            <p>Don't have an account? <a href="/register">Register</a></p>
        </div>
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
