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
function cf_graphql_request(string $query, array $variables): array {
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
    curl_close($ch);

    if ($response === false) {
        return ['errors' => [['message' => 'cURL error: ' . $curlError]]];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['errors' => [['message' => 'Invalid JSON from Cloudflare']]];
    }

    return $decoded;
}

/**
 * Get daily visits + unique visitor counts for the last $days days.
 * Uses cache when fresh; otherwise refetches and stores.
 *
 * @return array{ok:bool, daily:array, error?:string}
 */
function cf_get_daily_visitors(mysqli $conn, int $days = 30): array {
    cf_ensure_cache_table($conn);

    $cacheKey = "daily_visitors_v2_{$days}d";
    $cached = cf_read_cache($conn, $cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    $zoneId = getenv('CF_ZONE_ID');
    if (!$zoneId) {
        return ['ok' => false, 'daily' => [], 'error' => 'CF_ZONE_ID not set in secrets.php'];
    }

    $since = gmdate('Y-m-d\TH:i:s\Z', strtotime("-{$days} days"));
    $until = gmdate('Y-m-d\TH:i:s\Z');

    // Note: httpRequestsAdaptiveGroups does NOT support uniq{} (only sum/avg).
    // uniq{uniques} is only available on the legacy rollup datasets, so we use
    // httpRequests1dGroups here for daily visits + unique visitor estimates.
    // (This dataset also doesn't support the requestSource:"eyeball" filter,
    // so bot traffic isn't excluded from these daily numbers.)
    $query = '
        query DailyVisitors($zoneTag: String!, $since: Time!, $until: Time!) {
            viewer {
                zones(filter: { zoneTag: $zoneTag }) {
                    httpRequests1dGroups(
                        limit: 10000,
                        filter: { date_geq: $since, date_leq: $until }
                        orderBy: [date_ASC]
                    ) {
                        sum { visits }
                        uniq { uniques }
                        dimensions { date }
                    }
                }
            }
        }
    ';

    $result = cf_graphql_request($query, [
        'zoneTag' => $zoneId,
        'since' => substr($since, 0, 10), // httpRequests1dGroups wants Date, not Time
        'until' => substr($until, 0, 10),
    ]);

    if (!empty($result['errors'])) {
        return ['ok' => false, 'daily' => [], 'error' => $result['errors'][0]['message'] ?? 'Unknown Cloudflare API error'];
    }

    $groups = $result['data']['viewer']['zones'][0]['httpRequests1dGroups'] ?? [];

    $daily = [];
    foreach ($groups as $group) {
        $daily[] = [
            'date' => $group['dimensions']['date'] ?? '',
            'visits' => $group['sum']['visits'] ?? 0,
            'uniques' => $group['uniq']['uniques'] ?? 0,
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
    ]);

    if (!empty($result['errors'])) {
        return ['ok' => false, 'countries' => [], 'error' => $result['errors'][0]['message'] ?? 'Unknown Cloudflare API error'];
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
