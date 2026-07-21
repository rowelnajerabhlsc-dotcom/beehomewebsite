<?php
session_start();

// Only show this page if it was actually reached via a successful registration.
// Prevents someone from just typing the URL and seeing a fake "success" screen.
if (!isset($_SESSION['just_registered']) || $_SESSION['just_registered'] !== true) {
    header("Location: /login");
    exit();
}
unset($_SESSION['just_registered']);

$username = $_SESSION['registered_username'] ?? '';
unset($_SESSION['registered_username']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration Successful</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <style>
        .success-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background-color: #d4edda;
            color: #155724;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            margin: 0 auto 20px auto;
        }
        .auth-card {
            text-align: center;
        }
        .auth-card p {
            margin-bottom: 20px;
        }
        .btn-login {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 4px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="auth-container">
    <div class="auth-card">
        <div class="success-icon">&#10003;</div>
        <h2>Registration Successful</h2>
        <p>
            <?php if ($username): ?>
                Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>! Your account has been created.
            <?php else: ?>
                Your account has been created.
            <?php endif; ?>
            <br>
            You can now log in using the email and password you set.
        </p>
        <a href="/login" class="btn-login">Go to Login</a>
    </div>
</div>

</body>
</html>
