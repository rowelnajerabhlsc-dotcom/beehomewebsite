<?php
/**
 * serve_profile_photo.php
 *
 * Profile photos are uploaded as Cloudinary type=private, so the stored
 * URL alone isn't enough to display them — this endpoint looks up the
 * requested member's public_id, builds a freshly signed URL, verifies
 * it resolves, then redirects to it.
 *
 * Usage in <img> tags: <img src="/serve_profile_photo?user_id=123">
 *
 * Access: any logged-in user can view any member's photo (this mirrors
 * a normal staff-directory / member-roster use case, not government-ID
 * level sensitivity). Tighten this if that assumption is wrong for your
 * cooperative.
 */

session_start();
include "config.php";
include "cloudinary_helpers.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$target_user_id = (int) ($_GET['user_id'] ?? 0);
if ($target_user_id <= 0) {
    http_response_code(400);
    exit();
}

$public_id = null;
$stmt = $conn->prepare("SELECT profile_photo_public_id FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$stmt->bind_result($public_id);
$stmt->fetch();
$stmt->close();

if (empty($public_id)) {
    // No photo on file — fall back to a transparent 1x1 pixel rather than
    // a broken image icon, so the initials-fallback UI can still show.
    http_response_code(404);
    exit();
}

$signed_url = cloudinary_private_url($public_id, 'image', $cloudinary_config, false);

if (!$signed_url) {
    http_response_code(500);
    exit();
}

// Verify before redirecting (HEAD request) — same safety pattern as
// capital_share_doc_view.php, so a bad signature fails safely.
$verify_ch = curl_init();
curl_setopt_array($verify_ch, [
    CURLOPT_URL => $signed_url,
    CURLOPT_NOBODY => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 8,
]);
curl_exec($verify_ch);
$verify_http_code = curl_getinfo($verify_ch, CURLINFO_HTTP_CODE);
curl_close($verify_ch);

if ($verify_http_code !== 200) {
    http_response_code(502);
    exit();
}

header("Location: " . $signed_url);
exit();
