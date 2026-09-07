<?php
/**
 * cloudinary_helpers.php
 *
 * Include this after config.php (needs $cloudinary_config) in any file
 * that uploads to, or serves from, a private Cloudinary asset.
 *
 * IMPORTANT: this uses Cloudinary's documented API-signing scheme — the
 * same canonical-string + SHA1 approach already used successfully in
 * this project for uploads and asset deletion. It has NOT been separately
 * verified against Cloudinary's CDN-level "authenticated delivery"
 * signature scheme (a different, less-documented mechanism for directly
 * signed res.cloudinary.com URLs). If a signed URL built here ever
 * returns a non-200 from Cloudinary, the calling code (see
 * capital_share_doc_view.php / serve_profile_photo.php) verifies with a
 * HEAD request before treating it as delivered — so a signing mistake
 * fails safely (file just won't load) rather than silently.
 */

/**
 * Build a signed, time-bound Cloudinary "download" URL for a private/
 * authenticated asset. Works for both inline display (images, via <img>)
 * and true downloads (PDFs) — browsers render <img> subresources
 * regardless of any Content-Disposition header on the response.
 *
 * @param string $public_id     The asset's Cloudinary public_id
 * @param string $resource_type 'image' or 'raw'
 * @param array  $cloudinary_config  ['cloud_name'=>..,'api_key'=>..,'api_secret'=>..]
 * @param bool   $attachment    true = force download, false = inline
 * @return string|null  The signed URL, or null if config is incomplete
 */
function cloudinary_private_url($public_id, $resource_type, $cloudinary_config, $attachment = false) {
    $cloud_name = $cloudinary_config['cloud_name'] ?? null;
    $api_key    = $cloudinary_config['api_key'] ?? null;
    $api_secret = $cloudinary_config['api_secret'] ?? null;

    if (!$cloud_name || !$api_key || !$api_secret || !$public_id) {
        return null;
    }

    $timestamp = time();

    $params_to_sign = [
        'public_id' => $public_id,
        'timestamp' => $timestamp,
        'type'      => 'private',
    ];
    if ($attachment) {
        $params_to_sign['attachment'] = 'true';
    }
    ksort($params_to_sign);

    $signable = '';
    foreach ($params_to_sign as $key => $value) {
        $signable .= ($signable === '' ? '' : '&') . $key . '=' . $value;
    }
    $signature = sha1($signable . $api_secret);

    $query = http_build_query(array_merge($params_to_sign, [
        'api_key'   => $api_key,
        'signature' => $signature,
    ]));

    return "https://api.cloudinary.com/v1_1/{$cloud_name}/{$resource_type}/download?{$query}";
}

/**
 * Delete a private/authenticated asset from Cloudinary.
 * Must pass the same 'type' the asset was uploaded with (private),
 * otherwise Cloudinary won't find a matching asset to destroy.
 */
function cloudinary_destroy_private($public_id, $resource_type, $cloudinary_config) {
    $cloud_name = $cloudinary_config['cloud_name'] ?? null;
    $api_key    = $cloudinary_config['api_key'] ?? null;
    $api_secret = $cloudinary_config['api_secret'] ?? null;

    if (!$cloud_name || !$api_key || !$api_secret || !$public_id) {
        return false;
    }

    $timestamp = time();
    $signable = "public_id={$public_id}&timestamp={$timestamp}&type=private" . $api_secret;
    $signature = sha1($signable);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.cloudinary.com/v1_1/{$cloud_name}/{$resource_type}/destroy",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'public_id' => $public_id,
            'type'      => 'private',
            'api_key'   => $api_key,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return ($result['result'] ?? '') === 'ok';
}
