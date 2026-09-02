<?php
/**
 * dashboard_analytics.php
 *
 * Embedded tab page for dashboard.php's inline tab system (loaded via fetch()).
 * Shows Cloudflare traffic stats: daily visits/uniques line chart + top
 * countries. Admin-only, same gating pattern as your audit/token sections.
 */

session_start();
require_once __DIR__ . '/config.php';           // gives $conn
require_once __DIR__ . '/permissions.php';       // require_role(), etc.
require_once __DIR__ . '/cloudflare_analytics.php';

// Match your existing inline role check pattern (more reliable than helpers
// not being loaded everywhere).
if (!isset($_SESSION['role']) || $_SESSION['role'] < 4) {
    http_response_code(403);
    echo '<p>You do not have access to this section.</p>';
    exit;
}

$days = 30;
$dailyResult = cf_get_daily_visitors($conn, $days);
$countryResult = cf_get_top_countries($conn, $days, 10);

$dailyLabels = [];
$dailyVisits = [];
$dailyUniques = [];
foreach ($dailyResult['daily'] as $row) {
    $dailyLabels[] = $row['date'];
    $dailyVisits[] = $row['visits'];
    $dailyUniques[] = $row['uniques'];
}

$countryLabels = [];
$countryVisits = [];
foreach ($countryResult['countries'] as $row) {
    $countryLabels[] = $row['country'];
    $countryVisits[] = $row['visits'];
}

$totalVisits = array_sum($dailyVisits);
$totalUniques = array_sum($dailyUniques);
?>
<div class="analytics-tab">
    <h2>Site Traffic (last <?= (int)$days ?> days)</h2>

    <?php if (!$dailyResult['ok']): ?>
        <div class="alert alert-error">
            Couldn't load Cloudflare analytics: <?= htmlspecialchars($dailyResult['error'] ?? 'Unknown error') ?>
        </div>
    <?php else: ?>

        <div class="kpi-row">
            <div class="kpi-card">
                <span class="kpi-label">Total Visits</span>
                <span class="kpi-value"><?= number_format($totalVisits) ?></span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Total Unique Visitors (est.)</span>
                <span class="kpi-value"><?= number_format($totalUniques) ?></span>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="cfDailyChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="cfCountryChart"></canvas>
        </div>

        <script>
        (function () {
            // IIFE per your JS scoping convention — avoids const collisions
            // when this embedded page's script runs inside dashboard.php.
            const dailyLabels = <?= json_encode($dailyLabels) ?>;
            const dailyVisits = <?= json_encode($dailyVisits) ?>;
            const dailyUniques = <?= json_encode($dailyUniques) ?>;
            const countryLabels = <?= json_encode($countryLabels) ?>;
            const countryVisits = <?= json_encode($countryVisits) ?>;

            const dailyCtx = document.getElementById('cfDailyChart');
            if (dailyCtx) {
                new Chart(dailyCtx, {
                    type: 'line',
                    data: {
                        labels: dailyLabels,
                        datasets: [
                            {
                                label: 'Visits',
                                data: dailyVisits,
                                borderColor: '#096D2B',
                                backgroundColor: 'rgba(9, 109, 43, 0.1)',
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Unique Visitors (est.)',
                                data: dailyUniques,
                                borderColor: '#F5C233',
                                backgroundColor: 'rgba(245, 194, 51, 0.1)',
                                tension: 0.3,
                                fill: true,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        plugins: { title: { display: true, text: 'Daily Visits vs Unique Visitors' } },
                    },
                });
            }

            const countryCtx = document.getElementById('cfCountryChart');
            if (countryCtx) {
                new Chart(countryCtx, {
                    type: 'bar',
                    data: {
                        labels: countryLabels,
                        datasets: [{
                            label: 'Visits',
                            data: countryVisits,
                            backgroundColor: '#2cab4a',
                        }],
                    },
                    options: {
                        responsive: true,
                        indexAxis: 'y',
                        plugins: { title: { display: true, text: 'Top Countries by Visits' }, legend: { display: false } },
                    },
                });
            }
        })();
        </script>

    <?php endif; ?>
</div>
