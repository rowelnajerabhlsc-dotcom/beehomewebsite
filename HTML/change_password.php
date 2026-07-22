<?php
include "config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get temp_password value from DB
$stmt = $conn->prepare("SELECT temp_password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($temp_password);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" href="IMAGES/logo.png">
    <script>
    function togglePassword(id) {
        var input = document.getElementById(id);
        input.type = input.type === "password" ? "text" : "password";
    }
    </script>
</head>
<body>
    

<?php include "navbar.php"; ?>

<div class="auth-container">
    <div class="auth-card">

        <h1>Change Password</h1>

        <?php if ($temp_password == 1): ?>
            <p style="color:red; text-align:center;">
                You are required to change your password before continuing.
            </p>
        <?php endif; ?>

        <form action="/update_password" method="POST" class="password-form">

    <?php if ($temp_password == 1): ?>
        <div class="warning-box">
            ⚠ You are required to change your password before continuing.
        </div>
    <?php endif; ?>

    <div class="form-group full-width">
        <label>New Password</label>
        <div class="password-wrapper">
            <input type="password" name="new_password" placeholder="Enter new password" required>
            <span class="toggle-password">👁️</span>
        </div>
    </div>

    <div class="form-group full-width">
        <label>Confirm Password</label>
        <div class="password-wrapper">
            <input type="password" name="confirm_password" placeholder="Confirm password" required>
            <span class="toggle-password">👁️</span>
        </div>
    </div>

    <!-- PASSWORD RULES -->
    <div class="password-rules">
        <p>Password must:</p>
        <ul>
            <li>At least 8 characters</li>
            <li>At least 1 letter</li>
            <li>At least 1 number</li>
        </ul>
    </div>

    <?php
    if (isset($_GET['error'])) {
        echo "<div class='error-message'>" . $_GET['error'] . "</div>";
    }
    ?>

    <button type="submit" class="primary-btn">Update Password</button>

</form>

        <div class="auth-link">
            <a href="/profile">← Back to Profile</a>
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
