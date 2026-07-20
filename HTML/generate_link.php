<?php
require "config.php";
require "auth_check.php"; // must be logged in

// Same role gate used on records.php etc.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 3 && $_SESSION['role'] != 4)) {
    header("Location: /home");
    exit();
}

$generatedLink = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutes, adjust as needed
    $adminId = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO reg_tokens (token, created_by, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $token, $adminId, $expiresAt);

    if ($stmt->execute()) {
        $generatedLink = "https://beehome.ph/register?token=" . $token;
    } else {
        $error = "Could not generate link: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate Registration Link</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <style>
        .link-box {
            margin-top: 15px;
            padding: 12px;
            background: #f4f4f4;
            border-radius: 6px;
            word-break: break-all;
        }
        .alert.error {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Generate Registration Link</h2>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <button type="submit">Generate Link</button>
        </form>

        <?php if ($generatedLink): ?>
            <p>Send this link to the person you want to register. It expires in 15 minutes and can only be used once.</p>
            <div class="link-box" id="linkBox"><?= htmlspecialchars($generatedLink) ?></div>
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('linkBox').innerText)">
                Copy Link
            </button>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
