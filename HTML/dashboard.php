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

// Today's login/logout/lockout counts
$today = date('Y-m-d');
$kpi['logins_today'] = $conn->query(
    "SELECT COUNT(*) c FROM user_logs WHERE event_type = 'login_success' AND DATE(created_at) = '$today'"
)->fetch_assoc()['c'];

$kpi['logouts_today'] = $conn->query(
    "SELECT COUNT(*) c FROM user_logs WHERE event_type = 'logout' AND DATE(created_at) = '$today'"
)->fetch_assoc()['c'];

$kpi['lockouts_today'] = $conn->query(
    "SELECT COUNT(*) c FROM user_logs WHERE event_type = 'lockout' AND DATE(created_at) = '$today'"
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

// Login/logout/lockout trends
// Fix the above - need to use proper SQL
$loginTrend = $conn->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') ym, COUNT(*) c
     FROM user_logs
     WHERE event_type = 'login_success'
     AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY ym ORDER BY ym ASC"
);
$loginTrendData = [];
while ($row = $loginTrend->fetch_assoc()) {
    $loginTrendData[$row['ym']] = (int) $row['c'];
}

$logoutTrend = $conn->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') ym, COUNT(*) c
     FROM user_logs
     WHERE event_type = 'logout'
     AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY ym ORDER BY ym ASC"
);
$logoutTrendData = [];
while ($row = $logoutTrend->fetch_assoc()) {
    $logoutTrendData[$row['ym']] = (int) $row['c'];
}

$lockoutTrend = $conn->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') ym, COUNT(*) c
     FROM user_logs
     WHERE event_type = 'lockout'
     AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY ym ORDER BY ym ASC"
);
$lockoutTrendData = [];
while ($row = $lockoutTrend->fetch_assoc()) {
    $lockoutTrendData[$row['ym']] = (int) $row['c'];
}

/* Build one sorted, deduplicated month axis, then fill each dataset
   against it (0 where a module had no activity that month). This keeps
   json_encode() emitting real arrays (not objects with gapped keys)
   and keeps every dataset correctly aligned to the same labels. */
$allMonths = array_unique([...array_keys($manpowerTrend), ...array_keys($helpdeskTrend), ...array_keys($transportTrend), ...array_keys($loginTrendData), ...array_keys($logoutTrendData), ...array_keys($lockoutTrendData)]);
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
$loginTrendAligned     = align_to_months($loginTrendData, $allMonths);
$logoutTrendAligned    = align_to_months($logoutTrendData, $allMonths);
$lockoutTrendAligned   = align_to_months($lockoutTrendData, $allMonths);

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

// Event type breakdown for user_logs
$eventTypeBreakdown = [];
$res = $conn->query("SELECT event_type, COUNT(*) c FROM user_logs GROUP BY event_type");
while ($row = $res->fetch_assoc()) {
    $eventTypeBreakdown[$row['event_type']] = (int) $row['c'];
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

    // Recent Helpdesk Audit Activity and Recent User Login Activity are now
    // loaded live via dashboard_activity_api.php (search + pagination),
    // so no need to pull them server-side here.
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
    .event-type-login-success { color: #28a745; font-weight: bold; }
    .event-type-logout { color: #6c757d; font-style: italic; }
    .event-type-lockout { color: #dc3545; font-weight: bold; }

    .table-search {
        width: 100%; max-width: 420px; padding: 8px 12px; margin-top: 4px;
        border: 1px solid var(--border-soft); border-radius: 6px; font-size: 0.9em;
    }
    .table-search:focus { outline: none; border-color: var(--primary); }

    .pagination {
        display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; align-items: center;
    }
    .pagination button {
        border: 1px solid var(--border-soft); background: #fff; color: var(--primary);
        border-radius: 6px; padding: 6px 12px; font-size: 0.85em; cursor: pointer;
    }
    .pagination button:hover:not(:disabled) { background: var(--primary); color: #fff; }
    .pagination button:disabled { opacity: 0.4; cursor: default; }
    .pagination button.active { background: var(--primary); color: #fff; font-weight: bold; }
</style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="dash-wrap">
    <h1>Dashboard</h1>
    <div class="dash-subtitle">Overview across Manpower, Consumer Assistance, Transport, and User Activity</div>

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
        <div class="kpi-grid">
            <div class="kpi-card"><div class="num"><?= $kpi['employees']; ?></div><div class="label">Total Employees</div></div>
            <div class="kpi-card"><div class="num"><?= $kpi['manpower_open']; ?></div><div class="label">Manpower Requests</div></div>
            <div class="kpi-card"><div class="num"><?= $kpi['helpdesk_open']; ?></div><div class="label">Open Helpdesk Tickets</div></div>
            <div class="kpi-card"><div class="num"><?= $kpi['transport_pending']; ?></div><div class="label">Pending Transport Requests</div></div>
            <div class="kpi-card"><div class="num"><?= $kpi['logins_today']; ?></div><div class="label">Logins Today</div></div>
            <div class="kpi-card"><div class="num"><?= $kpi['logouts_today']; ?></div><div class="label">Logouts Today</div></div>
            <div class="kpi-card"><div class="num"><?= $kpi['lockouts_today']; ?></div><div class="label">Lockouts Today</div></div>
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
            <div class="chart-card">
                <h2>User Login Activity (6mo)</h2>
                <canvas id="loginTrendChart"></canvas>
            </div>
            <div class="chart-card">
                <h2>Logout/Lockout Trends (6mo)</h2>
                <canvas id="eventTrendChart"></canvas>
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
                <thead><tr><th>Date</th><th>Action</th><th>Role</th><th>Reference</th></tr></thead>
                <tbody id="auditLogBody">
                    <tr><td colspan="4">Loading…</td></tr>
                </tbody>
            </table>
            <div class="pagination" id="auditLogPagination"></div>

            <h2 style="margin-top:20px;">Recent User Login Activity</h2>
            <input
                type="text"
                id="loginActivitySearch"
                class="table-search"
                placeholder="Search by email, username, IP, or event type…"
                autocomplete="off"
            >
            <table>
                <thead><tr><th>Date</th><th>Event Type</th><th>User/Email</th><th>IP Address</th></tr></thead>
                <tbody id="loginActivityBody">
                    <tr><td colspan="4">Loading…</td></tr>
                </tbody>
            </table>
            <div class="pagination" id="loginActivityPagination"></div>
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

// Login trend chart
const loginTrendCtx = document.getElementById('loginTrendChart');
new Chart(loginTrendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($allMonths); ?>,
        datasets: [
            { label: 'Logins', data: <?= json_encode($loginTrendAligned); ?>, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.1)', tension: 0.3 },
            { label: 'Logouts', data: <?= json_encode($logoutTrendAligned); ?>, borderColor: '#6c757d', backgroundColor: 'rgba(108,117,125,0.1)', tension: 0.3 },
            { label: 'Lockouts', data: <?= json_encode($lockoutTrendAligned); ?>, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.1)', tension: 0.3 }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            title: { display: true, text: 'User Authentication Events (Last 6 Months)' }
        }
    }
});

// Event trend chart (alternative view)
const eventTrendCtx = document.getElementById('eventTrendChart');
new Chart(eventTrendCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($allMonths); ?>,
        datasets: [
            { label: 'Logins', data: <?= json_encode($loginTrendAligned); ?>, backgroundColor: '#28a745' },
            { label: 'Logouts', data: <?= json_encode($logoutTrendAligned); ?>, backgroundColor: '#6c757d' },
            { label: 'Lockouts', data: <?= json_encode($lockoutTrendAligned); ?>, backgroundColor: '#dc3545' }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Monthly Authentication Events' }
        },
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
        }
    }
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
    const auditBody = document.getElementById('auditLogBody');
    const loginBody = document.getElementById('loginActivityBody');

    // Only present for admins — bail otherwise.
    if (!auditBody && !loginBody) {
        return;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function renderPagination(container, page, totalPages, onPageClick) {
        container.innerHTML = '';
        if (totalPages <= 1) {
            return;
        }

        function makeButton(label, targetPage, disabled, active) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = label;
            btn.disabled = disabled;
            if (active) btn.classList.add('active');
            btn.addEventListener('click', () => onPageClick(targetPage));
            return btn;
        }

        container.appendChild(makeButton('« Prev', page - 1, page <= 1, false));

        for (let p = 1; p <= totalPages; p++) {
            container.appendChild(makeButton(String(p), p, false, p === page));
        }

        container.appendChild(makeButton('Next »', page + 1, page >= totalPages, false));
    }

    /* ---------- Recent Helpdesk Audit Activity (pagination only) ---------- */
    function loadAudit(page) {
        fetch('/dashboard_activity_api?type=audit&page=' + encodeURIComponent(page), { credentials: 'same-origin' })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.error) {
                    auditBody.innerHTML = '<tr><td colspan="4">' + escapeHtml(data.error) + '</td></tr>';
                    return;
                }
                auditBody.innerHTML = data.rows.length
                    ? data.rows.map(log => `
                        <tr>
                            <td>${escapeHtml(log.created_at)}</td>
                            <td>${escapeHtml(log.action)}</td>
                            <td>${escapeHtml(log.user_role)}</td>
                            <td>${escapeHtml(log.reference_number ?? '—')}</td>
                        </tr>`).join('')
                    : '<tr><td colspan="4">No records found.</td></tr>';
                renderPagination(document.getElementById('auditLogPagination'), data.page, data.totalPages, loadAudit);
            })
            .catch(err => {
                console.error('Audit activity load failed:', err);
                auditBody.innerHTML = '<tr><td colspan="4">Couldn\'t load audit activity (' + escapeHtml(err.message) + ').</td></tr>';
            });
    }

    /* ---------- Recent User Login Activity (search, live, + pagination) ---------- */
    let loginSearchTerm = '';
    let debounceTimer = null;

    function loadLoginActivity(page) {
        const params = new URLSearchParams({ type: 'logins', page: page, search: loginSearchTerm });
        fetch('/dashboard_activity_api?' + params.toString(), { credentials: 'same-origin' })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.error) {
                    loginBody.innerHTML = '<tr><td colspan="4">' + escapeHtml(data.error) + '</td></tr>';
                    return;
                }
                loginBody.innerHTML = data.rows.length
                    ? data.rows.map(activity => {
                        const userCell = activity.username
                            ? `${escapeHtml(activity.username)} (${escapeHtml(activity.email)})`
                            : `${escapeHtml(activity.email)} (unknown user)`;
                        const eventLabel = escapeHtml((activity.event_type || '').replace(/_/g, ' '))
                            .replace(/^./, c => c.toUpperCase());
                        return `
                            <tr>
                                <td>${escapeHtml(activity.created_at)}</td>
                                <td class="event-type-${escapeHtml(activity.event_type)}">${eventLabel}</td>
                                <td>${userCell}</td>
                                <td>${escapeHtml(activity.ip_address ?? '—')}</td>
                            </tr>`;
                    }).join('')
                    : '<tr><td colspan="4">No matching records.</td></tr>';
                renderPagination(document.getElementById('loginActivityPagination'), data.page, data.totalPages, loadLoginActivity);
            })
            .catch(err => {
                console.error('Login activity load failed:', err);
                loginBody.innerHTML = '<tr><td colspan="4">Couldn\'t load login activity (' + escapeHtml(err.message) + ').</td></tr>';
            });
    }

    const searchInput = document.getElementById('loginActivitySearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const value = this.value;
            debounceTimer = setTimeout(() => {
                loginSearchTerm = value;
                loadLoginActivity(1); // any new search starts back at page 1
            }, 300); // debounce so it's not firing a request per keystroke
        });
    }

    if (auditBody) loadAudit(1);
    if (loginBody) loadLoginActivity(1);
})();
</script>

<script>
(function () {
    const tabs = document.querySelectorAll('#quickLinks .ql-tab');
    const dashboardHome = document.getElementById('dashboardHome');
    const pageContent = document.getElementById('pageContent');
    const pageLoader = document.getElementById('pageLoader');

    // The URL of whatever page is currently loaded into #pageContent.
    // Embedded forms have no (or a relative) action attribute, so we need
    // this to know where they actually should submit to.
    let currentPageUrl = null;

    function setActive(tab) {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
    }

    function showDashboard() {
        dashboardHome.style.display = '';
        pageContent.style.display = 'none';
        pageLoader.style.display = 'none';
        pageContent.innerHTML = '';
        currentPageUrl = null;
    }

    function runScripts(container) {
        // Re-run any <script> tags injected via innerHTML (they don't execute by default).
        container.querySelectorAll('script').forEach(oldScript => {
            const newScript = document.createElement('script');
            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            oldScript.replaceWith(newScript);
        });
    }

    function wireForms(container, pageUrl) {
        container.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                // Resolve the real target: explicit action attribute (relative
                // or absolute) if present, otherwise the page this form came from.
                const rawAction = form.getAttribute('action');
                const targetUrl = rawAction ? new URL(rawAction, pageUrl).href : pageUrl;
                const method = (form.getAttribute('method') || 'GET').toUpperCase();

                const submitter = e.submitter; // the actual button clicked, so name/value (e.g. "generate") is included
                const formData = new FormData(form);
                if (submitter && submitter.name) {
                    formData.append(submitter.name, submitter.value);
                }

                pageContent.style.display = 'none';
                pageLoader.style.display = '';

                try {
                    let res;
                    if (method === 'GET') {
                        const qs = new URLSearchParams(formData).toString();
                        res = await fetch(targetUrl + (targetUrl.includes('?') ? '&' : '?') + qs, { credentials: 'same-origin' });
                    } else {
                        res = await fetch(targetUrl, { method, body: formData, credentials: 'same-origin' });
                    }
                    if (!res.ok) throw new Error('Request failed: ' + res.status);
                    const html = await res.text();

                    // If the endpoint redirected (e.g. to /transport-dashboard after an action),
                    // res.url reflects the final URL — keep that as the new "page" context.
                    currentPageUrl = res.url || targetUrl;

                    pageContent.innerHTML = html;
                    pageLoader.style.display = 'none';
                    pageContent.style.display = '';

                    runScripts(pageContent);
                    wireForms(pageContent, currentPageUrl);
                } catch (err) {
                    pageLoader.style.display = 'none';
                    pageContent.style.display = '';
                    pageContent.innerHTML = '<p style="padding:20px;color:#a12626;">Something went wrong submitting this form (' + err.message + '). <a href="' + targetUrl + '">Open the page directly instead</a>.</p>';
                }
            });
        });
    }

    async function loadPage(url, tab) {
        dashboardHome.style.display = 'none';
        pageContent.style.display = 'none';
        pageLoader.style.display = '';

        try {
            const res = await fetch(url, { credentials: 'same-origin' });
            if (!res.ok) throw new Error('Request failed: ' + res.status);
            const html = await res.text();

            currentPageUrl = res.url || url;

            pageContent.innerHTML = html;
            pageLoader.style.display = 'none';
            pageContent.style.display = '';

            runScripts(pageContent);
            wireForms(pageContent, currentPageUrl);
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