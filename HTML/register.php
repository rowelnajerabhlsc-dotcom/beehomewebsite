<?php
session_start();
require "config.php";
require "reg_token_check.php"; // gates access — exits with unable_to_register.php if invalid
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" href="IMAGES/logo.png">
    <style>
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Register</h2>

        <!-- MESSAGE DISPLAY -->
        <?php
        if(isset($_SESSION['message'])){
            $type = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : 'success';
            echo "<div class='alert $type'>{$_SESSION['message']}</div>";
            unset($_SESSION['message']);
            unset($_SESSION['msg_type']);
        }
        ?>

        <form action="/register_process" method="POST">

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required minlength="8">

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required minlength="8">

            <button type="submit">Register</button>
        </form>

        <div class="auth-link">
            <p>Already have an account? <a href="/login">Login</a></p>
        </div>
    </div>
</div>

</body>
</html>
