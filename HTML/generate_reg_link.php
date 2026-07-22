<?php
session_start();
require "config.php";
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Gate: only roles 3 and 4 (same gate used elsewhere in navbar.php) ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], [3, 4], true)) {
    header("Location: /login");
    exit();
}

$generatedLink = null;
$emailSent     = false;
$emailSentTo   = null;
$errorMsg      = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient  = trim($_POST['recipient'] ?? '');
    $isEmailPath = isset($_POST['email_link']);

    // Validate recipient up-front on the email path. No DB write yet — keeps
    // bad input from burning a token.
    if ($isEmailPath && ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL))) {
        $errorMsg = "Please enter a valid recipient email address.";
    } else {
        // Cryptographically random token — 32 bytes -> 64 hex chars
        $token = bin2hex(random_bytes(32));

        // No real expiry wanted (only invalidate after use). We still populate
        // expires_at because the column/check already exists in reg_token_check.php —
        // setting it far in the future means that check never trips.
        $farFuture = date('Y-m-d H:i:s', strtotime('+100 years'));

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'];
        $regLink = "{$scheme}://{$host}/register?token={$token}";

        $stmt = $conn->prepare("INSERT INTO reg_tokens (token, expires_at, used, created_at) VALUES (?, ?, 0, NOW())");
        $stmt->bind_param("ss", $token, $farFuture);

        if (!$stmt->execute()) {
            $errorMsg = "Failed to generate link: " . $stmt->error;
        } elseif (isset($_POST['generate'])) {
            $generatedLink = $regLink;
        } elseif ($isEmailPath) {
            $sendOk = sendRegLinkEmail($regLink, $recipient, $conn, $token);
            if ($sendOk) {
                $emailSent   = true;
                $emailSentTo = $recipient;
                // Link consumed once emailed — same one-time model as the
                // existing register_process.php burn.
                burnToken($conn, $token);
            } else {
                // SMTP failure also burns the token: admin must click
                // "Generate New Link" again to get a fresh one.
                burnToken($conn, $token);
                $errorMsg = "Could not send the email. A new link is required. "
                          . "If this keeps happening, ask the administrator to check the mail configuration.";
            }
        }
    }
}

function burnToken(mysqli $conn, string $token): void {
    $b = $conn->prepare("UPDATE reg_tokens SET used = 1 WHERE token = ?");
    $b->bind_param("s", $token);
    $b->execute();
}

function sendRegLinkEmail(string $link, string $to, mysqli $conn, string $tokenForLog): bool {
    global $reg_mail_config;

    if (empty($reg_mail_config['username']) || empty($reg_mail_config['password'])) {
        error_log("Reg-link mail not configured (REG_MAIL_USER / REG_MAIL_PASS missing).");
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $reg_mail_config['host'];
        $mail->Port       = $reg_mail_config['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $reg_mail_config['username'];
        $mail->Password   = $reg_mail_config['password'];
        $mail->SMTPSecure = $reg_mail_config['secure'] === 'tls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Timeout    = 15;

        $mail->setFrom($reg_mail_config['from_email'], $reg_mail_config['from_name']);
        $mail->addAddress($to);
        $mail->Subject = 'Your Bee Home registration link';
        $mail->isHTML(true);

        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        $mail->Body = "Hello,<br><br>"
            . "You've been invited to create an account on the Bee Home Labor Multipurpose Cooperative system.<br><br>"
            . "Click the link below to register. The link can be used once:<br><br>"
            . "<a href=\"{$safeLink}\">Create your account</a><br><br>"
            . "If you didn't request this, you can ignore this email.<br><br>"
            . "Bee Home Labor Multipurpose Cooperative";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Reg-link mail failed for token {$tokenForLog}: " . $mail->ErrorInfo);
        return false;
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
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .reg-link-actions {
            display: flex;
            align-items: stretch;
            gap: 8px;
            margin-top: 10px;
            width: 100%;
        }
        .reg-link-actions input[type="email"] {
            flex: 1 1 auto;
            min-width: 0;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .reg-link-actions button {
            flex: 0 0 auto;
            padding: 10px 16px;
            white-space: nowrap;
        }
        .reg-link-divider {
            margin: 18px 0;
            border: none;
            border-top: 1px solid #eee;
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

        <p>Generates a one-time link that allows exactly one person to register.
           Once used, the link stops working — it cannot be reused or shared again.</p>

        <form method="POST">
            <div class="reg-link-actions">
                <button type="submit" name="generate" value="1">Generate New Link</button>
            </div>

            <hr class="reg-link-divider">

            <label for="recipient">Or email the link to a recipient:</label>
            <div class="reg-link-actions">
                <input type="email" id="recipient" name="recipient" placeholder="name@example.com"
                       value="<?php echo htmlspecialchars($_POST['recipient'] ?? ''); ?>">
                <button type="submit" name="email_link" value="1">Send via Email</button>
            </div>
        </form>

        <?php if ($errorMsg): ?>
            <div class="alert error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <?php if ($emailSent): ?>
            <div class="alert success">
                Registration link sent to <strong><?php echo htmlspecialchars($emailSentTo); ?></strong>.
                They'll receive an email shortly.
            </div>
            <p class="muted-note">A new link was created and used. The recipient's link is in their email only.</p>
        <?php endif; ?>

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