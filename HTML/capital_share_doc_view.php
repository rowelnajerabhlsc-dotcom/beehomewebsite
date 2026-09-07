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

// ---- Serve the file (Cloudinary secure_url) ----
header("Location: " . $file_url);

// If running under PHP-FPM (GoDaddy shared hosting typically does),
// this sends the response to the browser immediately and closes the
// connection — the deletion call below then runs "in the background"
// from the user's perspective, so their download isn't delayed by it.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// ---- If this was the OWNER performing a real download, delete the
//      Cloudinary asset afterward (fire-and-forget, best-effort) ----
if ($is_owner && $action === 'downloaded') {
    $cloud_name = $cloudinary_config['cloud_name'];
    $api_key    = $cloudinary_config['api_key'];
    $api_secret = $cloudinary_config['api_secret'];

    if ($cloud_name && $api_key && $api_secret && $public_id) {
        $destroy_timestamp = time();
        $destroy_signable = "public_id={$public_id}&timestamp={$destroy_timestamp}{$api_secret}";
        $destroy_signature = sha1($destroy_signable);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.cloudinary.com/v1_1/{$cloud_name}/raw/destroy",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'public_id' => $public_id,
                'api_key'   => $api_key,
                'timestamp' => $destroy_timestamp,
                'signature' => $destroy_signature,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $destroy_response = curl_exec($ch);
        curl_close($ch);

        $destroy_result = json_decode($destroy_response, true);
        if (($destroy_result['result'] ?? '') === 'ok') {
            $markStmt = $conn->prepare("UPDATE capital_share_documents SET file_deleted_at = NOW() WHERE id = ?");
            $markStmt->bind_param("i", $doc_id);
            $markStmt->execute();
            $markStmt->close();
        }
        // If destroy failed, we deliberately do NOT mark file_deleted_at —
        // better to leave the file live and retry-able than to mark it
        // gone when it might still exist on Cloudinary.
    }
}

exit();
