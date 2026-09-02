<?php
/**
 * cf_debug.php
 *
 * Standalone diagnostic tool for the Cloudflare Analytics integration.
 * Bypasses the cache table entirely and shows raw request/response detail,
 * so you can see exactly what Cloudflare returns without digging through
 * error logs.
 *
 * Admin-only. Delete this file (or move it outside the web root) once
 * everything's confirmed working — it's a debugging aid, not something
 * that should stay reachable long-term.
 *
 * Usage: visit /cf_debug.php while logged in as an admin.
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/cloudflare_analytics.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] < 4) {
    http_response_code(403);
    die('Admin access required.');
}

header('Content-Type: text/html; charset=utf-8');

echo "<pre style='font-family: monospace; padding: 20px; white-space: pre-wrap;'>";

echo "=== Cloudflare Analytics Debug ===\n\n";

$token = getenv('CF_API_TOKEN');
$zoneId = getenv('CF_ZONE_ID');

echo "CF_API_TOKEN set: " . ($token ? 'yes (' . strlen($token) . ' chars)' : 'NO — missing from secrets.php') . "\n";
echo "CF_ZONE_ID set: " . ($zoneId ? htmlspecialchars($zoneId) : 'NO — missing from secrets.php') . "\n\n";

if (!$token || !$zoneId) {
    echo "Stop: add CF_API_TOKEN and CF_ZONE_ID to secrets.php before continuing.\n";
    echo "</pre>";
    exit;
}

// --- Test 1: token validity via Cloudflare's own verify endpoint ---
echo "--- Test 1: Token verification ---\n";
$ch = curl_init('https://api.cloudflare.com/client/v4/user/tokens/verify');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
]);
$verifyResponse = curl_exec($ch);
$verifyCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP {$verifyCode}\n";
echo htmlspecialchars(json_encode(json_decode($verifyResponse), JSON_PRETTY_PRINT)) . "\n\n";

// --- Test 2: minimal GraphQL query (just check zone access + field validity) ---
echo "--- Test 2: Minimal visits query (last 1 day) ---\n";
$since = gmdate('Y-m-d\TH:i:s\Z', strtotime('-1 day'));
$until = gmdate('Y-m-d\TH:i:s\Z');

$testQuery = '
    query TestVisits($zoneTag: String!, $since: Time!, $until: Time!) {
        viewer {
            zones(filter: { zoneTag: $zoneTag }) {
                httpRequestsAdaptiveGroups(
                    limit: 5,
                    filter: { datetime_geq: $since, datetime_leq: $until, requestSource: "eyeball" }
                    orderBy: [date_ASC]
                ) {
                    sum { visits }
                    dimensions { date }
                }
            }
        }
    }
';

$result = cf_graphql_request($testQuery, [
    'zoneTag' => $zoneId,
    'since' => $since,
    'until' => $until,
], 'debug_test');

echo "Raw response:\n";
echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "\n\n";

if (!empty($result['errors'])) {
    echo "ERRORS FOUND:\n" . htmlspecialchars(cf_format_errors($result['errors'])) . "\n\n";
} else {
    echo "OK — query succeeded.\n\n";
}

// --- Test 3: minimal uniques query ---
echo "--- Test 3: Minimal uniques query (last 1 day) ---\n";
$sinceDate = substr($since, 0, 10);
$untilDate = substr($until, 0, 10);

$testUniquesQuery = '
    query TestUniques($zoneTag: String!, $since: Date!, $until: Date!) {
        viewer {
            zones(filter: { zoneTag: $zoneTag }) {
                httpRequests1dGroups(
                    limit: 5,
                    filter: { date_geq: $since, date_leq: $until }
                ) {
                    uniq { uniques }
                    dimensions { date }
                }
            }
        }
    }
';

$uniquesResult = cf_graphql_request($testUniquesQuery, [
    'zoneTag' => $zoneId,
    'since' => $sinceDate,
    'until' => $untilDate,
], 'debug_test_uniques');

echo "Raw response:\n";
echo htmlspecialchars(json_encode($uniquesResult, JSON_PRETTY_PRINT)) . "\n\n";

if (!empty($uniquesResult['errors'])) {
    echo "ERRORS FOUND:\n" . htmlspecialchars(cf_format_errors($uniquesResult['errors'])) . "\n\n";
} else {
    echo "OK — query succeeded.\n\n";
}

// --- Test 4: full merged function as actually used by the dashboard ---
echo "--- Test 4: cf_get_daily_visitors() (bypassing cache) ---\n";
// Force a fresh fetch by deleting any cached entry for a 1-day window first.
$conn->query("DELETE FROM cf_analytics_cache WHERE cache_key LIKE 'daily_visitors_%'");
$liveResult = cf_get_daily_visitors($conn, 1);
echo htmlspecialchars(json_encode($liveResult, JSON_PRETTY_PRINT)) . "\n\n";

echo "=== Done ===\n";
echo "\nTip: set putenv('CF_DEBUG=1'); in secrets.php to log every raw request/response\n";
echo "to PHP's error log (check cPanel's Errors tool or your error_log file) for any\n";
echo "query made through cf_graphql_request(), not just this debug page.\n";

// --- Test 5: Settings node — Cloudflare's own answer for what this zone can query ---
echo "\n--- Test 5: Settings node (actual documented limits for this zone) ---\n";

$settingsQuery = '
    query ZoneSettings($zoneTag: string) {
        viewer {
            zones(filter: { zoneTag: $zoneTag }) {
                settings {
                    httpRequestsAdaptiveGroups {
                        enabled
                        maxDuration
                        maxNumberOfFields
                        maxPageSize
                        notOlderThan
                    }
                    httpRequests1dGroups {
                        enabled
                        maxDuration
                        maxNumberOfFields
                        maxPageSize
                        notOlderThan
                    }
                }
            }
        }
    }
';

$settingsResult = cf_graphql_request($settingsQuery, ['zoneTag' => $zoneId], 'settings_check');

echo "Raw response:\n";
echo htmlspecialchars(json_encode($settingsResult, JSON_PRETTY_PRINT)) . "\n\n";

if (empty($settingsResult['errors'])) {
    $settings = $settingsResult['data']['viewer']['zones'][0]['settings'] ?? [];
    foreach ($settings as $dataset => $limits) {
        if (!$limits) continue;
        $maxDurationDays = isset($limits['maxDuration']) ? round($limits['maxDuration'] / 86400, 1) : '?';
        $notOlderThanDays = isset($limits['notOlderThan']) ? round($limits['notOlderThan'] / 86400, 1) : '?';
        echo "{$dataset}:\n";
        echo "  enabled: " . ($limits['enabled'] ? 'yes' : 'no') . "\n";
        echo "  max query window: {$maxDurationDays} days\n";
        echo "  historical lookback: {$notOlderThanDays} days\n";
        echo "  max page size: " . ($limits['maxPageSize'] ?? '?') . " rows\n\n";
    }
}

echo "</pre>";
