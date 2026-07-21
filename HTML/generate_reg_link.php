<?php
session_start();
require "config.php";

// --- Gate: only roles 3 and 4 (same gate used elsewhere in navbar.php) ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], [3, 4], true)) {
    header("Location: /login");
    exit();
}

$generatedLink = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {

    // Cryptographically random token — 32 bytes -> 64 hex chars
    $token = bin2hex(random_bytes(32));

    // No real expiry wanted (only invalidate after use). We still populate
    // expires_at because the column/check already exists in reg_token_check.php —
    // setting it far in the future means that check never trips.
    $farFuture = date('Y-m-d H:i:s', strtotime('+100 years'));

    $stmt = $conn->prepare("INSERT INTO reg_tokens (token, expires_at, used, created_at) VALUES (?, ?, 0, NOW())");
    $stmt->bind_param("ss", $token, $farFuture);

    if ($stmt->execute()) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'];
        $generatedLink = "{$scheme}://{$host}/register?token={$token}";
    } else {
        $errorMsg = "Failed to generate link: " . $stmt->error;
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
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .link-box {
            display: flex;
            align-items: stretch;
            gap: 8px;
            margin-top: 15px;
            width: 100%;
        }
        .link-box input[type="text"] {
            flex: 1 1 auto;
            width: auto;
            min-width: 0;
            height: auto;
            padding: 10px;
            font-family: monospace;
            font-size: 0.85em;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .link-box button {
            flex: 0 0 auto;
            width: auto;
            padding: 10px 16px;
            white-space: nowrap;
        }
        .copied-msg {
            display: none;
            color: #155724;
            margin-top: 8px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Generate Registration Link</h2>

        <?php if ($errorMsg): ?>
            <div class="alert error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <p>Generates a one-time link that allows exactly one person to register.
           Once used, the link stops working — it cannot be reused or shared again.</p>

        <form method="POST">
            <button type="submit" name="generate" value="1">Generate New Link</button>
        </form>

        <?php if ($generatedLink): ?>
            <div class="link-box">
                <input type="text" id="genLink" value="<?php echo htmlspecialchars($generatedLink); ?>" readonly>
                <button type="button" onclick="copyLink()">Copy</button>
            </div>
            <div class="copied-msg" id="copiedMsg">Copied to clipboard.</div>
        <?php endif; ?>

    </div>
</div>

<script>
function copyLink() {
    const input = document.getElementById('genLink');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        const msg = document.getElementById('copiedMsg');
        msg.style.display = 'block';
        setTimeout(function() { msg.style.display = 'none'; }, 2000);
    });
}
</script>

</body>
</html>