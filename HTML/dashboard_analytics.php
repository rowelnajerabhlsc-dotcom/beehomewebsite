<?php
/**
 * dashboard_analytics.php
 * Modern Analytics Dashboard
 */

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/cloudflare_analytics.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] < 4) {
    http_response_code(403);
    echo '<p>You do not have access to this section.</p>';
    exit;
}

/*
|--------------------------------------------------------------------------
| Range Filter
|--------------------------------------------------------------------------
*/
$range = $_GET['range'] ?? '1m';

switch ($range) {
    case '1d':
        $days = 1;
        $rangeLabel = 'Today';
        break;

    case '1w':
        $days = 7;
        $rangeLabel = 'Last 7 Days';
        break;

    case '6m':
        $days = 180;
        $rangeLabel = 'Last 6 Months';
        break;

    default:
        $days = 30;
        $range = '1m';
        $rangeLabel = 'Last 30 Days';
}

/*
|--------------------------------------------------------------------------
| Analytics Data
|--------------------------------------------------------------------------
*/
$dailyResult = cf_get_daily_visitors($conn, $days);

$dailyLabels = [];
$dailyVisits = [];
$dailyUniques = [];

if ($dailyResult['ok']) {
    foreach ($dailyResult['daily'] as $row) {
        $dailyLabels[] = $row['date'];
        $dailyVisits[] = (int)$row['visits'];
        $dailyUniques[] = (int)$row['uniques'];
    }
}

/*
|--------------------------------------------------------------------------
| KPI Calculations
|--------------------------------------------------------------------------
*/
$totalVisits = array_sum($dailyVisits);
$totalUniques = array_sum($dailyUniques);

$todayVisits = !empty($dailyVisits)
    ? end($dailyVisits)
    : 0;

$peakVisits = !empty($dailyVisits)
    ? max($dailyVisits)
    : 0;

$averageVisits = count($dailyVisits)
    ? round($totalVisits / count($dailyVisits))
    : 0;

$chartHeight = ($days > 90) ? 120 : 90;
?>

<div class="analytics-dashboard">

    <div class="analytics-header">
        <div>
            <h2>Site Analytics</h2>
            <p><?= htmlspecialchars($rangeLabel) ?></p>
        </div>

        <div class="analytics-filters">
            <button class="range-btn <?= $range === '1d' ? 'active' : '' ?>" data-range="1d">
                1D
            </button>

            <button class="range-btn <?= $range === '1w' ? 'active' : '' ?>" data-range="1w">
                1W
            </button>

            <button class="range-btn <?= $range === '1m' ? 'active' : '' ?>" data-range="1m">
                1M
            </button>

            <button class="range-btn <?= $range === '6m' ? 'active' : '' ?>" data-range="6m">
                6M
            </button>
        </div>
    </div>

    <?php if (!$dailyResult['ok']): ?>

        <div class="analytics-error">
            <?= htmlspecialchars($dailyResult['error'] ?? 'Unknown error') ?>
        </div>

    <?php else: ?>

        <div class="analytics-kpis">

            <div class="analytics-card">
                <span>Total Visits</span>
                <h3><?= number_format($totalVisits) ?></h3>
            </div>

            <div class="analytics-card">
                <span>Unique Visitors</span>
                <h3><?= number_format($totalUniques) ?></h3>
            </div>

            <div class="analytics-card">
                <span>Today's Visits</span>
                <h3><?= number_format($todayVisits) ?></h3>
            </div>

            <div class="analytics-card">
                <span>Peak Day</span>
                <h3><?= number_format($peakVisits) ?></h3>
            </div>

            <div class="analytics-card">
                <span>Average Daily</span>
                <h3><?= number_format($averageVisits) ?></h3>
            </div>

        </div>

        <div class="analytics-chart-card">
            <canvas id="cfDailyChart"></canvas>
        </div>

        <script>
        (function() {

            const labels = <?= json_encode($dailyLabels) ?>;
            const visits = <?= json_encode($dailyVisits) ?>;
            const uniques = <?= json_encode($dailyUniques) ?>;

            const ctx = document.getElementById('cfDailyChart');

            if (!ctx) return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Visits',
                            data: visits,
                            borderColor: '#096D2B',
                            backgroundColor: 'rgba(9,109,43,0.08)',
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 2
                        },
                        {
                            label: 'Unique Visitors',
                            data: uniques,
                            borderColor: '#F5C233',
                            backgroundColor: 'rgba(245,194,51,0.08)',
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },

                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },

                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            document.querySelectorAll('.range-btn').forEach(btn => {

                btn.addEventListener('click', function() {

                    const range = this.dataset.range;

                    if (typeof loadDashboardTab === 'function') {

                        loadDashboardTab(
                            'analytics',
                            'dashboard_analytics.php?range=' + range
                        );

                    } else {

                        window.location =
                            'dashboard.php?tab=analytics&range=' + range;
                    }

                });

            });

        })();
        </script>

    <?php endif; ?>

</div>

<style>

.analytics-dashboard{
    padding:20px;
}

.analytics-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    margin-bottom:20px;
    flex-wrap:wrap;
}

.analytics-header h2{
    margin:0;
}

.analytics-header p{
    margin:5px 0 0;
    color:#666;
}

.analytics-filters{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.range-btn{
    border:none;
    padding:10px 16px;
    border-radius:999px;
    cursor:pointer;
    background:#ececec;
    transition:.2s;
    font-weight:600;
}

.range-btn:hover{
    background:#dddddd;
}

.range-btn.active{
    background:#096D2B;
    color:#fff;
}

.analytics-kpis{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:16px;
    margin-bottom:20px;
}

.analytics-card{
    background:#fff;
    border-radius:14px;
    padding:20px;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
}

.analytics-card span{
    color:#777;
    font-size:13px;
}

.analytics-card h3{
    margin:10px 0 0;
    font-size:28px;
}

.analytics-chart-card{
    background:#fff;
    border-radius:14px;
    padding:20px;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
    height:500px;
}

.analytics-error{
    background:#ffe7e7;
    color:#a10000;
    padding:15px;
    border-radius:10px;
}

@media (max-width:768px){

    .analytics-chart-card{
        height:350px;
    }

}

</style>