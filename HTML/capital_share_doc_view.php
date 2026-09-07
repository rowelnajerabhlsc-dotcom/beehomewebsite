<?php
/**
 * capital_share_doc_view.php
 *
 * Logs a 'viewed' or 'downloaded' event, then serves the PDF (redirecting
 * to its Cloudinary URL). Access is restricted to the owning member or
 * staff/admin (role >= 2).
 *
 * Logging is split:
 *   - The document's owner opening/downloading it -> capital_share_document_views
 *   - Staff previewing it on the member's behalf   -> capital_share_document_staff_access
 *
 * Deletion: ONLY when the actual owning member performs a real download
 * (action=download) is the file removed from Cloudinary afterward, and
 * capital_share_documents.file_deleted_at is set. Staff previews and
 * plain "view" actions never trigger deletion — this avoids a staff
 * member accidentally consuming a document before the member ever sees
 * it. The document row and all view/access logs are kept permanently
 * either way; only the actual Cloudinary file is purged.
 *
 * Usage: /capital_share_doc_view?id=123&action=view
 *        /capital_share_doc_view?id=123&action=download
 */

session_start();
include "config.php";
include "cloudinary_helpers.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'] ?? 0;

$doc_id = (int) ($_GET['id'] ?? 0);
$action = ($_GET['action'] ?? 'view') === 'download' ? 'downloaded' : 'viewed';

if ($doc_id <= 0) {
    http_response_code(400);
    exit('Invalid document.');
}

$stmt = $conn->prepare("SELECT user_id, file_url, public_id, file_deleted_at FROM capital_share_documents WHERE id = ?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$stmt->bind_result($owner_user_id, $file_url, $public_id, $file_deleted_at);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    http_response_code(404);
    exit('Document not found.');
}

$is_owner = ($current_user_id === (int) $owner_user_id);
$is_staff = ($current_role >= 2);

// ---- Access control: owner or staff+ only ----
if (!$is_owner && !$is_staff) {
    http_response_code(403);
    exit('Not authorized to view this document.');
}

// ---- Already consumed? ----
if ($file_deleted_at !== null) {
    http_response_code(410); // Gone
    if ($is_owner) {
        exit('This document was already downloaded and has been removed from storage. Contact staff if you need it reissued.');
    }
    exit('This document was already downloaded by the member and removed from storage on ' . htmlspecialchars($file_deleted_at) . '.');
}

// ---- Log the event, into the correct table ----
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

if ($is_owner) {
    $logStmt = $conn->prepare("
        INSERT INTO capital_share_document_views (document_id, user_id, action, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $logStmt->bind_param("iiss", $doc_id, $owner_user_id, $action, $ip);
    $logStmt->execute();
    $logStmt->close();
} else {
    $logStmt = $conn->prepare("
        INSERT INTO capital_share_document_staff_access (document_id, staff_user_id, owner_user_id, action, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $logStmt->bind_param("iiiss", $doc_id, $current_user_id, $owner_user_id, $action, $ip);
    $logStmt->execute();
    $logStmt->close();
}

// ---- Documents are private on Cloudinary — build a fresh signed URL
//      from the stored public_id rather than using the plain stored
//      file_url (which won't resolve on its own under type=private). ----
$signed_url = cloudinary_private_url($public_id, 'raw', $cloudinary_config, $action === 'downloaded');

if (!$signed_url) {
    http_response_code(500);
    exit('Could not generate a delivery link for this document (Cloudinary not configured).');
}

// ---- Verify the file is actually reachable before we tell the user it
//      was delivered (and, critically, before we ever consider deleting
//      it). Without this check, a bad signature or blocked delivery would
//      still get "delivered" (redirect sent) and deleted, even though the
//      user never actually received the file. ----
$verify_ch = curl_init();
curl_setopt_array($verify_ch, [
    CURLOPT_URL => $signed_url,
    CURLOPT_NOBODY => true,       // HEAD request, don't download the body
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
]);
curl_exec($verify_ch);
$verify_http_code = curl_getinfo($verify_ch, CURLINFO_HTTP_CODE);
curl_close($verify_ch);

if ($verify_http_code !== 200) {
    http_response_code(502);
    exit(
        'This document could not be delivered right now (Cloudinary returned ' . $verify_http_code . '). ' .
        'If this keeps happening, check that "Allow delivery of PDF and ZIP files" is enabled in your ' .
        'Cloudinary account\'s Security settings, and that the signed-URL signing logic in ' .
        'cloudinary_helpers.php matches Cloudinary\'s current API — this scheme is based on documented ' .
        'signing conventions but hasn\'t been independently verified against a live account.'
    );
}

// ---- Serve the file ----
header("Location: " . $signed_url);

// If running under PHP-FPM (GoDaddy shared hosting typically does),
// this sends the response to the browser immediately and closes the
// connection — the deletion call below then runs "in the background"
// from the user's perspective, so their download isn't delayed by it.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// ---- If this was the OWNER performing a real download, delete the
//      Cloudinary asset afterward (fire-and-forget, best-effort) ----
if ($is_owner && $action === 'downloaded' && $public_id) {
    $destroyed = cloudinary_destroy_private($public_id, 'raw', $cloudinary_config);

    if ($destroyed) {
        $markStmt = $conn->prepare("UPDATE capital_share_documents SET file_deleted_at = NOW() WHERE id = ?");
        $markStmt->bind_param("i", $doc_id);
        $markStmt->execute();
        $markStmt->close();
    }
    // If destroy failed, we deliberately do NOT mark file_deleted_at —
    // better to leave the file live and retry-able than to mark it
    // gone when it might still exist on Cloudinary.
}

exit();
