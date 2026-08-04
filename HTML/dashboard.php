<?php
session_start();
include "config.php";
include "permissions.php";

/* ACCESS CONTROL - Manager and Admin only */
require_role(3);

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] == 4;

/* =========================================================
   KPI COUNTS
   ========================================================= */
$kpi = [];

$kpi['employees'] = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];

$kpi['manpower_open'] = $conn->query(
    "SELECT COUNT(*) c FROM manpower_requests"
)->fetch_assoc()['c'];

$kpi['helpdesk_open'] = $conn->query(
    "SELECT COUNT(*) c FROM helpdesk_cases WHERE status NOT IN ('sent','closed')"
)->fetch_assoc()['c'];

$kpi['transport_pending'] = $conn->query(
    "SELECT COUNT(*) c FROM rent_requests WHERE status='new'"
)->fetch_assoc()['c'];

/* =========================================================
   TRENDS: last 6 months, per module
   ========================================================= */
function monthly_counts($conn, $table, $dateCol) {
    $sql = "SELECT DATE_FORMAT($dateCol, '%Y-%m') ym, COUNT(*) c
            FROM $table
            WHERE $dateCol >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY ym ORDER BY ym ASC";
    $res = $conn->query($sql);
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[$row['ym']] = (int) $row['c'];
    }
    return $out;
}

$manpowerTrend  = monthly_counts($conn, 'manpower_requests', 'created_at');
$helpdeskTrend  = monthly_counts($conn, 'helpdesk_cases', 'created_at');
$transportTrend = monthly_counts($conn, 'rent_requests', 'created_at');

/* Build one sorted, deduplicated month axis, then fill each dataset
   against it (0 where a module had no activity that month). This keeps
   json_encode() emitting real arrays (not objects with gapped keys)
   and keeps every dataset correctly aligned to the same labels. */
$allMonths = array_unique(array_merge(
    array_keys($manpowerTrend),
    array_keys($helpdeskTrend),
    array_keys($transportTrend)
));
sort($allMonths);
$allMonths = array_values($allMonths);

function align_to_months(array $trend, array $months): array {
    $out = [];
    foreach ($months as $m) {
        $out[] = $trend[$m] ?? 0;
    }
    return $out;
}

$manpowerTrendAligned  = align_to_months($manpowerTrend, $allMonths);
$helpdeskTrendAligned  = align_to_months($helpdeskTrend, $allMonths);
$transportTrendAligned = align_to_months($transportTrend, $allMonths);

/* =========================================================
   STATUS BREAKDOWNS
   ========================================================= */
function status_breakdown($conn, $table) {
    $res = $conn->query("SELECT status, COUNT(*) c FROM $table GROUP BY status");
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[$row['status']] = (int) $row['c'];
    }
    return $out;
}

$helpdeskStatus  = status_breakdown($conn, 'helpdesk_cases');
$transportStatus = status_breakdown($conn, 'rent_requests');
$manpowerByPosition = [];
$res = $conn->query("SELECT req_position, COUNT(*) c FROM manpower_requests GROUP BY req_position ORDER BY c DESC LIMIT 6");
while ($row = $res->fetch_assoc()) {
    $manpowerByPosition[$row['req_position']] = (int) $row['c'];
}

/* =========================================================
   ADMIN-ONLY DATA (role 4)
   ========================================================= */
$adminData = [];
if ($isAdmin) {
    $adminData['roleBreakdown'] = [];
    $res = $conn->query("SELECT role, COUNT(*) c FROM users GROUP BY role");
    $roleNames = [1 => 'User', 2 => 'Staff', 3 => 'Manager', 4 => 'Admin'];
    while ($row = $res->fetch_assoc()) {
        $label = $roleNames[$row['role']] ?? ('Role ' . $row['role']);
        $adminData['roleBreakdown'][$label] = (int) $row['c'];
    }

    $regTotal = $conn->query("SELECT COUNT(*) c FROM reg_tokens")->fetch_assoc()['c'];
    $regUsed  = $conn->query("SELECT COUNT(*) c FROM reg_tokens WHERE used=1")->fetch_assoc()['c'];
    $regExpired = $conn->query("SELECT COUNT(*) c FROM reg_tokens WHERE used=0 AND expires_at < NOW()")->fetch_assoc()['c'];
    $adminData['regTokens'] = ['total' => $regTotal, 'used' => $regUsed, 'expired' => $regExpired, 'active' => $regTotal - $regUsed - $regExpired];

    $auditRes = $conn->query(
        "SELECT a.action, a.user_role, a.created_at, c.reference_number
         FROM helpdesk_audit_log a
         LEFT JOIN helpdesk_cases c ON a.case_id = c.id
         ORDER BY a.created_at DESC LIMIT 15"
    );
    $adminData['auditLog'] = $auditRes ? $auditRes->fetch_all(MYSQLI_ASSOC) : [];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>
<link rel="stylesheet" href="../CSS/navbar.css">
<link rel="icon" href="IMAGES/logo.png">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<style>
    :root {
        --primary: #096D2B;
        --secondary: #26a753;
        --bg-light: #f9f9f9;
        --border-soft: rgba(9, 109, 43, 0.3);
        --shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: var(--bg-light); margin: 0; color: #1c2b21; }
    .dash-wrap { max-width: 1300px; margin: 0 auto; padding: 30px 20px 60px; }
    h1 { color: var(--primary); margin-bottom: 4px; }
    h2 { color: var(--primary); font-size: 1.15em; margin: 0 0 12px; }
    .dash-subtitle { color: #4a5c50; margin-bottom: 24px; }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .kpi-card {
        background: #fff; border: 1px solid var(--border-soft); border-radius: 10px;
        padding: 18px; box-shadow: var(--shadow);
    }
    .kpi-card .num { font-size: 2em; font-weight: 700; color: var(--primary); }
    .kpi-card .label { color: #5a6b60; font-size: 0.9em; }

    .quick-links { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 28px; }
    .quick-links a {
        background: var(--primary); color: #fff; text-decoration: none;
        padding: 10px 18px; border-radius: 50px; font-size: 0.9em; font-weight: 600;
        border: 2px solid transparent;
    }
    .quick-links a:hover { background: var(--secondary); }
    .quick-links a.ql-tab.active { background: #fff; color: var(--primary); border-color: var(--primary); }

    .page-loader {
        text-align: center; color: var(--primary); font-weight: 600;
        padding: 40px 0;
    }
    .page-content {
        background: #fff; border: 1px solid var(--border-soft); border-radius: 10px;
        padding: 4px; box-shadow: var(--shadow); overflow: auto;
    }

    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 18px;
        margin-bottom: 28px;
    }
    .chart-card {
        background: #fff; border: 1px solid var(--border-soft); border-radius: 10px;
        padding: 18px; box-shadow: var(--shadow);
    }

    .admin-section {
        border: 2px dashed var(--primary);
        border-radius: 10px;
        padding: 20px;
        background: #f2f9f3;
        margin-bottom: 28px;
    }
    .admin-section h2::before { content: "🔒 "; }

    table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 10px; }
    th, td { padding: 8px; border: 1px solid #ccc; text-align: left; font-size: 0.85em; }
    th { background: #f4f4f4; }
</style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="dash-wrap">
    <h1>Dashboard</h1>
    <div class="dash-subtitle">Overview across Manpower, Consumer Assistance, and Transport</div>

    <div class="quick-links" id="quickLinks">
        <a href="#" class="ql-tab active" data-target="dashboardHome">Dashboard</a>
        <a href="/records" class="ql-tab" data-target="records">Records</a>
        <a href="/manpower-request-logs" class="ql-tab" data-target="manpower-request-logs">Manpower Logs</a>
        <a href="/helpdesk_dashboard" class="ql-tab" data-target="helpdesk_dashboard">Consumer Assistance</a>
        <a href="/transport-dashboard" class="ql-tab" data-target="transport-dashboard">Transport Requests</a>
        <?php if ($isAdmin): ?>
            <a href="/generate_reg_link" class="ql-tab" data-target="generate_reg_link">Generate Registration Link</a>
        <?php endif; ?>
    </div>

    <div id="pageLoader" class="page-loader" style="display:none;">Loading…</div>
    <div id="pageContent" class="page-content" style="display:none;"></div>

    <div id="dashboardHome">
        <div class="kpi-card"><div class="num"><?= $kpi['employees']; ?></div><div class="label">Total Employees</div></div>
        <div class="kpi-card"><div class="num"><?= $kpi['manpower_open']; ?></div><div class="label">Manpower Requests</div></div>
        <div class="kpi-card"><div class="num"><?= $kpi['helpdesk_open']; ?></div><div class="label">Open Helpdesk Tickets</div></div>
        <div class="kpi-card"><div class="num"><?= $kpi['transport_pending']; ?></div><div class="label">Pending Transport Requests</div></div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h2>Requests Over Time (6mo)</h2>
            <canvas id="trendChart"></canvas>
        </div>
        <div class="chart-card">
            <h2>Helpdesk Status Breakdown</h2>
            <canvas id="helpdeskChart"></canvas>
        </div>
        <div class="chart-card">
            <h2>Transport Status Breakdown</h2>
            <canvas id="transportChart"></canvas>
        </div>
        <div class="chart-card">
            <h2>Top Requested Manpower Positions</h2>
            <canvas id="positionChart"></canvas>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <div class="admin-section">
        <h2>Admin Only — User & Registration Overview</h2>

        <div class="chart-grid">
            <div class="chart-card">
                <h2>User Role Breakdown</h2>
                <canvas id="roleChart"></canvas>
            </div>
            <div class="chart-card">
                <h2>Registration Links</h2>
                <canvas id="regChart"></canvas>
            </div>
        </div>

        <h2 style="margin-top:20px;">Recent Helpdesk Audit Activity</h2>
        <table>
            <tr><th>Date</th><th>Action</th><th>Role</th><th>Reference</th></tr>
            <?php foreach ($adminData['auditLog'] as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['created_at']); ?></td>
                <td><?= htmlspecialchars($log['action']); ?></td>
                <td><?= htmlspecialchars($log['user_role']); ?></td>
                <td><?= htmlspecialchars($log['reference_number'] ?? '—'); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    </div><!-- /dashboardHome -->

</div>

<script>
const trendCtx = document.getElementById('trendChart');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($allMonths); ?>,
        datasets: [
            { label: 'Manpower', data: <?= json_encode($manpowerTrendAligned); ?>, borderColor: '#096D2B', backgroundColor: 'rgba(9,109,43,0.1)', tension: 0.3 },
            { label: 'Helpdesk', data: <?= json_encode($helpdeskTrendAligned); ?>, borderColor: '#1c5a99', backgroundColor: 'rgba(28,90,153,0.1)', tension: 0.3 },
            { label: 'Transport', data: <?= json_encode($transportTrendAligned); ?>, borderColor: '#a15c00', backgroundColor: 'rgba(161,92,0,0.1)', tension: 0.3 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('helpdeskChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($helpdeskStatus)); ?>,
        datasets: [{ data: <?= json_encode(array_values($helpdeskStatus)); ?>, backgroundColor: ['#f0f0f0','#fff6e0','#fff0e0','#eaf3fd','#eef7ee','#fdeaea'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('transportChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($transportStatus)); ?>,
        datasets: [{ data: <?= json_encode(array_values($transportStatus)); ?>, backgroundColor: ['#f0f0f0','#eef7ee','#fdeaea','#eee'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('positionChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($manpowerByPosition)); ?>,
        datasets: [{ label: 'Requests', data: <?= json_encode(array_values($manpowerByPosition)); ?>, backgroundColor: '#26a753' }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, indexAxis: 'y' }
});

<?php if ($isAdmin): ?>
new Chart(document.getElementById('roleChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_keys($adminData['roleBreakdown'])); ?>,
        datasets: [{ data: <?= json_encode(array_values($adminData['roleBreakdown'])); ?>, backgroundColor: ['#eef7ee','#eaf3fd','#fff6e0','#fdeaea'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('regChart'), {
    type: 'bar',
    data: {
        labels: ['Active', 'Used', 'Expired'],
        datasets: [{
            data: [<?= $adminData['regTokens']['active']; ?>, <?= $adminData['regTokens']['used']; ?>, <?= $adminData['regTokens']['expired']; ?>],
            backgroundColor: ['#096D2B', '#1c5a99', '#a12626']
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
<?php endif; ?>
</script>

<script>
(function () {
    const tabs = document.querySelectorAll('#quickLinks .ql-tab');
    const dashboardHome = document.getElementById('dashboardHome');
    const pageContent = document.getElementById('pageContent');
    const pageLoader = document.getElementById('pageLoader');

    function setActive(tab) {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
    }

    function showDashboard() {
        dashboardHome.style.display = '';
        pageContent.style.display = 'none';
        pageLoader.style.display = 'none';
        pageContent.innerHTML = '';
    }

    async function loadPage(url, tab) {
        dashboardHome.style.display = 'none';
        pageContent.style.display = 'none';
        pageLoader.style.display = '';

        try {
            const res = await fetch(url, { credentials: 'same-origin' });
            if (!res.ok) throw new Error('Request failed: ' + res.status);
            const html = await res.text();

            pageContent.innerHTML = html;
            pageLoader.style.display = 'none';
            pageContent.style.display = '';

            // Re-run any <script> tags injected via innerHTML (they don't execute by default).
            pageContent.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                oldScript.replaceWith(newScript);
            });
        } catch (err) {
            pageLoader.style.display = 'none';
            pageContent.style.display = '';
            pageContent.innerHTML = '<p style="padding:20px;color:#a12626;">Couldn\'t load this page (' + err.message + '). <a href="' + url + '">Open it directly instead</a>.</p>';
        }

        setActive(tab);
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            const url = tab.getAttribute('href');

            if (tab.dataset.target === 'dashboardHome') {
                showDashboard();
                setActive(tab);
                return;
            }

            loadPage(url, tab);
        });
    });
})();
</script>

</body>
</html>
