<?php
/**
 * upload_profile_picture.php
 *
 * AJAX endpoint: accepts a single image upload from the logged-in user,
 * pushes it to Cloudinary via a signed upload, stores the resulting URL
 * on user_profiles, and deletes the user's previous photo from Cloudinary
 * (so orphaned images don't pile up in your Cloudinary storage).
 *
 * Expects: multipart/form-data POST with a "photo" file field.
 * Returns: JSON { success: true, url: "...", public_id: "..." }
 *       or { success: false, error: "..." }
 */

session_start();
header('Content-Type: application/json');

include "config.php"; // provides $conn and $cloudinary_config

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    respond(['success' => false, 'error' => 'Not logged in.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['photo'])) {
    respond(['success' => false, 'error' => 'No file received.'], 400);
}

$user_id = $_SESSION['user_id'];
$file = $_FILES['photo'];

// ---- Basic upload error check ----
if ($file['error'] !== UPLOAD_ERR_OK) {
    respond(['success' => false, 'error' => 'Upload failed (code ' . $file['error'] . ').'], 400);
}

// ---- Validate size (max 5MB) ----
$max_bytes = 5 * 1024 * 1024;
if ($file['size'] > $max_bytes) {
    respond(['success' => false, 'error' => 'Image must be under 5MB.'], 400);
}

// ---- Validate actual file type server-side (don't trust the extension) ----
$allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$detected_mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($detected_mime, $allowed_mimes, true)) {
    respond(['success' => false, 'error' => 'Only JPG, PNG, or WEBP images are allowed.'], 400);
}

// ---- Look up the user's existing photo (to delete after successful replace) ----
$old_public_id = null;
$stmt = $conn->prepare("SELECT profile_photo_public_id FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($old_public_id);
$stmt->fetch();
$stmt->close();

// ---- Build the signed Cloudinary upload request ----
$cloud_name = $cloudinary_config['cloud_name'];
$api_key    = $cloudinary_config['api_key'];
$api_secret = $cloudinary_config['api_secret'];

if (!$cloud_name || !$api_key || !$api_secret) {
    respond(['success' => false, 'error' => 'Cloudinary is not configured on the server.'], 500);
}

$timestamp = time();
$folder    = 'bhlmpc/profile_photos';
$public_id = 'user_' . $user_id . '_' . $timestamp;

// Params to sign MUST be sorted alphabetically by key, joined as key=value&key=value,
// with the api_secret appended, then sha1'd. (Cloudinary's signing spec.)
$params_to_sign = [
    'folder'    => $folder,
    'public_id' => $public_id,
    'timestamp' => $timestamp,
];
ksort($params_to_sign);

$signable = '';
foreach ($params_to_sign as $key => $value) {
    $signable .= ($signable === '' ? '' : '&') . $key . '=' . $value;
}
$signature = sha1($signable . $api_secret);

// ---- Send the upload via cURL ----
$upload_url = "https://api.cloudinary.com/v1_1/{$cloud_name}/image/upload";

$post_fields = [
    'file'      => new CURLFile($file['tmp_name'], $detected_mime, $file['name']),
    'api_key'   => $api_key,
    'timestamp' => $timestamp,
    'signature' => $signature,
    'folder'    => $folder,
    'public_id' => $public_id,
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $upload_url,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $post_fields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
]);
$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    respond(['success' => false, 'error' => 'Could not reach Cloudinary: ' . $curl_error], 502);
}

$result = json_decode($response, true);

if ($http_code !== 200 || !isset($result['secure_url'])) {
    $cloud_err = $result['error']['message'] ?? 'Unknown Cloudinary error.';
    respond(['success' => false, 'error' => $cloud_err], 502);
}

$secure_url = $result['secure_url'];
$returned_public_id = $result['public_id'];

// ---- Save the new photo reference to the DB ----
$stmt = $conn->prepare("UPDATE user_profiles SET profile_photo_url = ?, profile_photo_public_id = ? WHERE user_id = ?");
$stmt->bind_param("ssi", $secure_url, $returned_public_id, $user_id);
$stmt->execute();
$stmt->close();

// ---- Best-effort cleanup: delete the old Cloudinary image, if any ----
if (!empty($old_public_id) && $old_public_id !== $returned_public_id) {
    $destroy_timestamp = time();
    $destroy_signable = "public_id={$old_public_id}&timestamp={$destroy_timestamp}{$api_secret}";
    $destroy_signature = sha1($destroy_signable);

    $destroy_ch = curl_init();
    curl_setopt_array($destroy_ch, [
        CURLOPT_URL => "https://api.cloudinary.com/v1_1/{$cloud_name}/image/destroy",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'public_id' => $old_public_id,
            'api_key'   => $api_key,
            'timestamp' => $destroy_timestamp,
            'signature' => $destroy_signature,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($destroy_ch); // fire-and-forget; a failed cleanup isn't fatal to this request
    curl_close($destroy_ch);
}

respond(['success' => true, 'url' => $secure_url, 'public_id' => $returned_public_id]);
