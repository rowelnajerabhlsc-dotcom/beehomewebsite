<?php
/**
 * cloudflare_analytics.php
 *
 * Fetches traffic stats (visits, unique IPs, top countries) from Cloudflare's
 * GraphQL Analytics API for the beehome.ph zone, with a simple MySQL cache
 * so the dashboard doesn't hit Cloudflare's API on every page load.
 *
 * Requires in secrets.php (outside web root):
 *   putenv('CF_API_TOKEN=xxxxxxxx');   // Zone > Analytics > Read scoped token
 *   putenv('CF_ZONE_ID=xxxxxxxx');     // beehome.ph zone ID
 *
 * Requires $conn (mysqli) from config.php to already be loaded by the caller.
 */

require_once __DIR__ . '/config.php'; // gives us $conn

define('CF_CACHE_TTL_SECONDS', 900); // 15 minutes

/**
 * Ensure the cache table exists. Call once (e.g. from an install script),
 * or leave this here — CREATE TABLE IF NOT EXISTS is cheap enough to run
 * defensively on each request.
 */
function cf_ensure_cache_table(mysqli $conn): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS cf_analytics_cache (
            id INT PRIMARY KEY AUTO_INCREMENT,
            cache_key VARCHAR(64) NOT NULL UNIQUE,
            payload LONGTEXT NOT NULL,
            fetched_at DATETIME NOT NULL
        )
    ");
}

/**
 * Low-level GraphQL call to Cloudflare.
 *
 * @param string $query GraphQL query string
 * @param array  $variables GraphQL variables
 * @return array Decoded JSON response, or ['errors' => [...]] on failure
 */
function cf_graphql_request(string $query, array $variables, ?string $debugLabel = null): array {
    $token = getenv('CF_API_TOKEN');

    if (!$token) {
        return ['errors' => [['message' => 'CF_API_TOKEN not set in secrets.php']]];
    }

    $ch = curl_init('https://api.cloudflare.com/client/v4/graphql');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'query' => $query,
            'variables' => $variables,
        ]),
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Debug logging: set CF_DEBUG=1 in secrets.php to log every request/response
    // to the PHP error log (visible in cPanel's error_log or via tail on GoDaddy).
    if (getenv('CF_DEBUG') === '1') {
        error_log('[CF_DEBUG] ' . ($debugLabel ?? 'request') . " HTTP {$httpCode}");
        error_log('[CF_DEBUG] query: ' . preg_replace('/\s+/', ' ', trim($query)));
        error_log('[CF_DEBUG] variables: ' . json_encode($variables));
        error_log('[CF_DEBUG] response: ' . substr((string)$response, 0, 4000));
    }

    if ($response === false) {
        return ['errors' => [['message' => 'cURL error: ' . $curlError]]];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['errors' => [['message' => 'Invalid JSON from Cloudflare (HTTP ' . $httpCode . '): ' . substr((string)$response, 0, 500)]]];
    }

    if ($httpCode >= 400 && empty($decoded['errors'])) {
        $decoded['errors'] = [['message' => "Cloudflare returned HTTP {$httpCode} with no error detail"]];
    }

    return $decoded;
}

/**
 * Flattens all GraphQL error messages into one readable string, instead of
 * only surfacing the first one. Cloudflare sometimes returns several errors
 * (e.g. one per invalid field) in a single response.
 */
function cf_format_errors(array $errors): string {
    $messages = array_map(function ($e) {
        return $e['message'] ?? json_encode($e);
    }, $errors);
    return implode(' | ', $messages);
}

/**
 * Get daily visits + unique visitor counts for the last $days days.
 * Uses cache when fresh; otherwise refetches and stores.
 *
 * @return array{ok:bool, daily:array, error?:string}
 */
function cf_get_daily_visitors(mysqli $conn, int $days = 30): array {
    cf_ensure_cache_table($conn);

    $cacheKey = "daily_visitors_v3_{$days}d";
    $cached = cf_read_cache($conn, $cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    $zoneId = getenv('CF_ZONE_ID');
    if (!$zoneId) {
        return ['ok' => false, 'daily' => [], 'error' => 'CF_ZONE_ID not set in secrets.php'];
    }

    $sinceTime = gmdate('Y-m-d\TH:i:s\Z', strtotime("-{$days} days"));
    $untilTime = gmdate('Y-m-d\TH:i:s\Z');
    $sinceDate = substr($sinceTime, 0, 10);
    $untilDate = substr($untilTime, 0, 10);

    // Neither dataset has everything we want, so we query both and merge by date:
    //   - httpRequestsAdaptiveGroups: has sum.visits (session-like), but no uniq{}
    //   - httpRequests1dGroups: has uniq.uniques, but no sum.visits (only requests/bytes/etc)
    $visitsQuery = '
        query DailyVisits($zoneTag: String!, $since: Time!, $until: Time!) {
            viewer {
                zones(filter: { zoneTag: $zoneTag }) {
                    httpRequestsAdaptiveGroups(
                        limit: 10000,
                        filter: { datetime_geq: $since, datetime_leq: $until, requestSource: "eyeball" }
                        orderBy: [datetimeDay_ASC]
                    ) {
                        sum { visits }
                        dimensions { datetimeDay }
                    }
                }
            }
        }
    ';

    $uniquesQuery = '
        query DailyUniques($zoneTag: String!, $since: Date!, $until: Date!) {
            viewer {
                zones(filter: { zoneTag: $zoneTag }) {
                    httpRequests1dGroups(
                        limit: 10000,
                        filter: { date_geq: $since, date_leq: $until }
                        orderBy: [date_ASC]
                    ) {
                        uniq { uniques }
                        dimensions { date }
                    }
                }
            }
        }
    ';

    $visitsResult = cf_graphql_request($visitsQuery, [
        'zoneTag' => $zoneId,
        'since' => $sinceTime,
        'until' => $untilTime,
    ], 'daily_visits');

    if (!empty($visitsResult['errors'])) {
        return ['ok' => false, 'daily' => [], 'error' => 'Visits query failed: ' . cf_format_errors($visitsResult['errors'])];
    }

    $uniquesResult = cf_graphql_request($uniquesQuery, [
        'zoneTag' => $zoneId,
        'since' => $sinceDate,
        'until' => $untilDate,
    ], 'daily_uniques');

    if (!empty($uniquesResult['errors'])) {
        return ['ok' => false, 'daily' => [], 'error' => 'Uniques query failed: ' . cf_format_errors($uniquesResult['errors'])];
    }

    $visitGroups = $visitsResult['data']['viewer']['zones'][0]['httpRequestsAdaptiveGroups'] ?? [];
    $uniqueGroups = $uniquesResult['data']['viewer']['zones'][0]['httpRequests1dGroups'] ?? [];

    // Index both result sets by date so we can merge them
    $visitsByDate = [];
    foreach ($visitGroups as $group) {
        $date = substr($group['dimensions']['datetimeDay'] ?? '', 0, 10);
        if ($date === '') {
            continue;
        }
        $visitsByDate[$date] = ($visitsByDate[$date] ?? 0) + ($group['sum']['visits'] ?? 0);
    }

    $uniquesByDate = [];
    foreach ($uniqueGroups as $group) {
        $date = $group['dimensions']['date'] ?? '';
        if ($date === '') {
            continue;
        }
        $uniquesByDate[$date] = $group['uniq']['uniques'] ?? 0;
    }

    $allDates = array_unique(array_merge(array_keys($visitsByDate), array_keys($uniquesByDate)));
    sort($allDates);

    $daily = [];
    foreach ($allDates as $date) {
        $daily[] = [
            'date' => $date,
            'visits' => $visitsByDate[$date] ?? 0,
            'uniques' => $uniquesByDate[$date] ?? 0,
        ];
    }

    $payload = ['ok' => true, 'daily' => $daily];
    cf_write_cache($conn, $cacheKey, $payload);

    return $payload;
}

/**
 * Get top countries by request volume for the last $days days.
 *
 * @return array{ok:bool, countries:array, error?:string}
 */
function cf_get_top_countries(mysqli $conn, int $days = 30, int $limit = 10): array {
    cf_ensure_cache_table($conn);

    $cacheKey = "top_countries_{$days}d_{$limit}";
    $cached = cf_read_cache($conn, $cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    $zoneId = getenv('CF_ZONE_ID');
    if (!$zoneId) {
        return ['ok' => false, 'countries' => [], 'error' => 'CF_ZONE_ID not set in secrets.php'];
    }

    $since = gmdate('Y-m-d\TH:i:s\Z', strtotime("-{$days} days"));
    $until = gmdate('Y-m-d\TH:i:s\Z');

    $query = '
        query TopCountries($zoneTag: String!, $since: Time!, $until: Time!) {
            viewer {
                zones(filter: { zoneTag: $zoneTag }) {
                    httpRequestsAdaptiveGroups(
                        limit: 10000,
                        filter: { datetime_geq: $since, datetime_leq: $until, requestSource: "eyeball" }
                    ) {
                        sum { visits }
                        dimensions { clientCountryName }
                    }
                }
            }
        }
    ';

    $result = cf_graphql_request($query, [
        'zoneTag' => $zoneId,
        'since' => $since,
        'until' => $until,
    ], 'top_countries');

    if (!empty($result['errors'])) {
        return ['ok' => false, 'countries' => [], 'error' => cf_format_errors($result['errors'])];
    }

    $groups = $result['data']['viewer']['zones'][0]['httpRequestsAdaptiveGroups'] ?? [];

    // Aggregate (Cloudflare returns per-hour buckets, not pre-summed by country)
    $byCountry = [];
    foreach ($groups as $group) {
        $country = $group['dimensions']['clientCountryName'] ?? 'Unknown';
        $visits = $group['sum']['visits'] ?? 0;
        $byCountry[$country] = ($byCountry[$country] ?? 0) + $visits;
    }
    arsort($byCountry);
    $top = array_slice($byCountry, 0, $limit, true);

    $countries = [];
    foreach ($top as $country => $visits) {
        $countries[] = ['country' => $country, 'visits' => $visits];
    }

    $payload = ['ok' => true, 'countries' => $countries];
    cf_write_cache($conn, $cacheKey, $payload);

    return $payload;
}

/** Read from cache if fresh (within CF_CACHE_TTL_SECONDS), else null. */
function cf_read_cache(mysqli $conn, string $key): ?array {
    $stmt = $conn->prepare("SELECT payload, fetched_at FROM cf_analytics_cache WHERE cache_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $age = time() - strtotime($row['fetched_at']);
    if ($age > CF_CACHE_TTL_SECONDS) {
        return null;
    }

    $decoded = json_decode($row['payload'], true);
    return is_array($decoded) ? $decoded : null;
}

/** Write/refresh a cache entry. */
function cf_write_cache(mysqli $conn, string $key, array $payload): void {
    $json = json_encode($payload);
    $now = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        INSERT INTO cf_analytics_cache (cache_key, payload, fetched_at)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE payload = VALUES(payload), fetched_at = VALUES(fetched_at)
    ");
    $stmt->bind_param('sss', $key, $json, $now);
    $stmt->execute();
    $stmt->close();
}
