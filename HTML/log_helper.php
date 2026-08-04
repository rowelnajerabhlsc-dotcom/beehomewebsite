<?php
/**
 * Insert a row into user_logs. Safe to call even if the table doesn't
 * exist yet (e.g. before you've run the migration) — it will just
 * silently no-op instead of fatal-erroring the login/logout flow.
 */
function log_user_event($conn, $event_type, $user_id = null, $email = null) {
    if (!$conn) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare(
        "INSERT INTO user_logs (user_id, email, event_type, ip_address) VALUES (?, ?, ?, ?)"
    );

    // prepare() returns false if the table doesn't exist yet — bail quietly.
    if (!$stmt) {
        return;
    }

    $stmt->bind_param("isss", $user_id, $email, $event_type, $ip);
    $stmt->execute();
    $stmt->close();
}
