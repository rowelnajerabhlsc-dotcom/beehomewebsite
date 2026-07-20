<?php
/**
 * reg_token_check.php
 *
 * Include this at the very top of register.php.
 * It ensures the visitor either:
 *   - has a valid, unused, unexpired token in the URL, OR
 *   - already has a valid registration session from that token
 *
 * If neither is true, it shows unable_to_register.php and exits.
 *
 * Assumes config.php has already been included (for $conn + session_start()).
 */

const REG_SESSION_LIFETIME = 900; // 15 minutes, adjust as needed

function reg_show_denied_and_exit() {
    include __DIR__ . '/unable_to_register.php';
    exit();
}

// Case 1: Already have a valid registration session
if (isset($_SESSION['reg_valid']) && $_SESSION['reg_valid'] === true) {

    if (!isset($_SESSION['reg_expires']) || time() > $_SESSION['reg_expires']) {
        unset($_SESSION['reg_valid'], $_SESSION['reg_expires'], $_SESSION['reg_token']);
        reg_show_denied_and_exit();
    }

    // still valid, let them through
    return;
}

// Case 2: No active session yet — must have a token in the URL
$token = $_GET['token'] ?? '';

if ($token === '') {
    reg_show_denied_and_exit();
}

$stmt = $conn->prepare("SELECT id, expires_at, used FROM reg_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    reg_show_denied_and_exit(); // token doesn't exist
}

if ((int)$row['used'] === 1) {
    reg_show_denied_and_exit(); // already used
}

if (strtotime($row['expires_at']) < time()) {
    reg_show_denied_and_exit(); // expired
}

// Token is valid — start a registration session
$_SESSION['reg_valid']   = true;
$_SESSION['reg_expires'] = time() + REG_SESSION_LIFETIME;
$_SESSION['reg_token']   = $token; // remembered so register_process.php can mark it used
