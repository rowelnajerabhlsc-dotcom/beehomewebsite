<?php
session_start();
include "config.php";
include "permissions.php";

/* ACCESS CONTROL - Require at least Manager role */
require_role(3);

/* =========================================================
   HANDLE VEHICLE FLEET MANAGEMENT
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehicle_action'])) {

    if ($_POST['vehicle_action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $qty  = max(0, (int) ($_POST['quantity'] ?? 0));
        if ($name !== '') {
            $stmt = $conn->prepare("INSERT INTO vehicles (name, quantity) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $qty);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($_POST['vehicle_action'] === 'update') {
        $vid  = (int) ($_POST['vehicle_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $qty  = max(0, (int) ($_POST['quantity'] ?? 0));
        if ($vid > 0 && $name !== '') {
            $stmt = $conn->prepare("UPDATE vehicles SET name=?, quantity=? WHERE id=?");
            $stmt->bind_param("sii", $name, $qty, $vid);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($_POST['vehicle_action'] === 'delete') {
        $vid = (int) ($_POST['vehicle_id'] ?? 0);
        if ($vid > 0) {
            $stmt = $conn->prepare("DELETE FROM vehicles WHERE id=?");
            $stmt->bind_param("i", $vid);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: /transport-dashboard?status=" . urlencode($_GET['status'] ?? 'new') . "&page=" . (int)($_GET['page'] ?? 1));
    exit();
}

/* =========================================================
   HANDLE STATUS CHANGE / DELETE (GET ?action=approve|reject|delete&id=)
   ========================================================= */
if (isset($_GET['action'], $_GET['id'])) {
    $id     = (int) $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        /* Capacity check: don't allow approval if it would push any
           overlapping date past total vehicle capacity. */
        $reqStmt = $conn->prepare("SELECT from_date, until_date FROM rent_requests WHERE id=?");
        $reqStmt->bind_param("i", $id);
        $reqStmt->execute();
        $reqRow = $reqStmt->get_result()->fetch_assoc();
        $reqStmt->close();

        if (!$reqRow) {
            header("Location: /transport-dashboard?status=" . urlencode($_GET['status'] ?? 'new') . "&page=" . (int)($_GET['page'] ?? 1) . "&err=notfound");
            exit();
        }

        $totalVehiclesCheck = (int) ($conn->query("SELECT COALESCE(SUM(quantity),0) t FROM vehicles")->fetch_assoc()['t']);

        $overlapStmt = $conn->prepare(
            "SELECT COUNT(*) c FROM rent_requests
             WHERE status = 'approved' AND id != ? AND from_date <= ? AND until_date >= ?"
        );
        $overlapStmt->bind_param("iss", $id, $reqRow['until_date'], $reqRow['from_date']);
        $overlapStmt->execute();
        $overlapCount = (int) $overlapStmt->get_result()->fetch_assoc()['c'];
        $overlapStmt->close();

        if ($totalVehiclesCheck <= 0 || $overlapCount >= $totalVehiclesCheck) {
            header("Location: /transport-dashboard?status=" . urlencode($_GET['status'] ?? 'new') . "&page=" . (int)($_GET['page'] ?? 1) . "&err=capacity");
            exit();
        }

        $stmt = $conn->prepare("UPDATE rent_requests SET status='approved' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE rent_requests SET status='rejected' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'cancel') {
        $stmt = $conn->prepare("UPDATE rent_requests SET status='cancelled' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM rent_requests WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: /transport-dashboard?status=" . urlencode($_GET['status'] ?? 'new') . "&page=" . (int)($_GET['page'] ?? 1));
    exit();
}

/* =========================================================
   FILTER + PAGINATION + LIST
   ========================================================= */
$statusFilter = $_GET['status'] ?? 'new'; // default view: pending requests

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

if ($statusFilter === 'all') {
    $totalCount = (int) $conn->query("SELECT COUNT(*) c FROM rent_requests")->fetch_assoc()['c'];

    $stmt = $conn->prepare("SELECT * FROM rent_requests ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) c FROM rent_requests WHERE status = ?");
    $countStmt->bind_param("s", $statusFilter);
    $countStmt->execute();
    $totalCount = (int) $countStmt->get_result()->fetch_assoc()['c'];
    $countStmt->close();

    $stmt = $conn->prepare("SELECT * FROM rent_requests WHERE status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $statusFilter, $perPage, $offset);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$totalPages = max(1, (int) ceil($totalCount / $perPage));
$page = min($page, $totalPages); // clamp in case someone requests an out-of-range page

/* =========================================================
   FLEET DATA
   ========================================================= */
$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$totalVehicles = 0;
foreach ($vehicles as $v) { $totalVehicles += (int) $v['quantity']; }


$approvedRequests = [];
if ($statusFilter === 'new' || $statusFilter === 'all') {
    $approvedStmt = $conn->query("SELECT id, from_date, until_date FROM rent_requests WHERE status = 'approved'");
    $approvedRequests = $approvedStmt->fetch_all(MYSQLI_ASSOC);
    $approvedStmt->close();
}

/* =========================================================
   CAPACITY WARNING per row (only meaningful for pending requests):
   would approving this request exceed capacity for any date it covers?
   ========================================================= */
function would_exceed_capacity($approvedRequests, $id, $fromDate, $untilDate, $totalVehicles) {
    if ($totalVehicles <= 0) {
        return true;
    }
    $count = 0;
    foreach ($approvedRequests as $appr) {
        if ((int)$appr['id'] === (int)$id) {
            continue;
        }
        // Check if the approved request overlaps with the request in question
        // Overlap: [from_date, until_date] overlaps with [$fromDate, $untilDate]
        // Condition: approved.from_date <= $untilDate && approved.until_date >= $fromDate
        if ($appr['from_date'] <= $untilDate && $appr['until_date'] >= $fromDate) {
            $count++;
        }
    }
    return $count >= $totalVehicles;
}
function is_manager_or_admin() {
    // User has already passed require_role(3) check, so they are at least a manager
    // Return true to allow display of admin/manager-specific UI elements
    return true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transport Requests</title>
<link rel="stylesheet" href="../CSS/navbar.css">
<link rel="icon" href="IMAGES/logo.png">
<style>
    .table-container { padding: 20px; }
    .filter-bar { margin-bottom: 15px; display: flex; gap: 8px; flex-wrap: wrap; }
    .filter-bar a {
        padding: 6px 14px; border-radius: 50px; text-decoration: none;
        border: 1px solid #096D2B; color: #096D2B; font-size: 0.85em; font-weight: 600;
    }
    .filter-bar a.active { background: #096D2B; color: #fff; }
    table { width: 100%; border-collapse: collapse; background: white; }
    th, td { padding: 10px; border: 1px solid #ccc; text-align: center; font-size: 0.9em; }
    th { background: #f4f4f4; }
    .badge { padding: 4px 10px; border-radius: 50px; font-size: 0.8em; font-weight: 600; }
    .badge.new { background: #f0f0f0; color: #555; }
    .badge.approved { background: #eef7ee; color: #096D2B; }
    .badge.rejected { background: #fdeaea; color: #a12626; }
    .badge.cancelled { background: #eee; color: #888; }
    .action-btn { padding: 5px 10px; margin: 2px; border: none; cursor: pointer; border-radius: 4px; color: #fff; }
    .approve { background: #4CAF50; }
    .reject { background: #f44336; }
    .cancel { background: #999; }
    .delete { background: #333; }
    .warn-badge {
        display: inline-block; margin-top: 4px; padding: 3px 8px; border-radius: 50px;
        background: #fff0e0; color: #a15c00; font-size: 0.75em; font-weight: 600;
    } 
    .error-banner {
        background: #fdeaea; border: 1px solid #a12626; color: #a12626;
        padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-weight: 600;
    }
    .pagination {
        display: flex; align-items: center; justify-content: center;
        gap: 6px; margin-top: 16px; flex-wrap: wrap;
    }
    .pagination a, .pagination span {
        padding: 6px 12px; border-radius: 6px; text-decoration: none;
        border: 1px solid #ccc; color: #333; font-size: 0.85em;
    }
    .pagination a:hover { background: #f0f0f0; }
    .pagination .current { background: #096D2B; color: #fff; border-color: #096D2B; font-weight: 700; }
    .pagination .disabled { color: #bbb; border-color: #eee; }
    .pagination-info { text-align: center; color: #5a6b60; font-size: 0.85em; margin-top: 8px; }
</style>
</head>
<body>

<?php if (($_GET['err'] ?? '') === 'capacity'): ?>
<div class="table-container" style="padding-bottom:0;">
    <div class="error-banner">
        ⚠ Could not approve — approved vehicles for those dates are already at full capacity (<?= $totalVehicles; ?> total). Reject it, adjust the fleet quantity, or free up an existing approved booking first.
    </div>
</div>
<?php elseif (($_GET['err'] ?? '') === 'notfound'): ?>
<div class="table-container" style="padding-bottom:0;">
    <div class="error-banner">⚠ That request could not be found.</div>
</div>
<?php endif; ?>

<div class="table-container">
    <h2>Vehicle Fleet</h2>
    <p style="color:#5a6b60; font-size:0.9em; margin-top:-8px;">
        Total capacity: <strong><?= $totalVehicles; ?></strong> vehicle(s) — this is what the public calendar uses to determine "fully booked".
    </p>

    <table style="margin-bottom: 14px;">
        <tr>
            <th>Name</th>
            <th>Quantity</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($vehicles as $v): ?>
        <tr>
            <td>
                <input type="text" name="name" form="vform-<?= (int)$v['id']; ?>" value="<?= htmlspecialchars($v['name']); ?>" required style="width:90%;">
            </td>
            <td>
                <input type="number" name="quantity" form="vform-<?= (int)$v['id']; ?>" min="0" value="<?= (int)$v['quantity']; ?>" required style="width:70px;">
            </td>
            <td>
                <button type="submit" form="vform-<?= (int)$v['id']; ?>" class="action-btn approve">Save</button>
                <button type="submit" form="vdelform-<?= (int)$v['id']; ?>" class="action-btn delete">Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($vehicles)): ?>
        <tr><td colspan="3" style="color:#999;">No vehicle types configured yet.</td></tr>
        <?php endif; ?>
    </table>

    <?php foreach ($vehicles as $v): ?>
        <form id="vform-<?= (int)$v['id']; ?>" method="POST" style="display:none;">
            <input type="hidden" name="vehicle_action" value="update">
            <input type="hidden" name="vehicle_id" value="<?= (int)$v['id']; ?>">
        </form>
        <form id="vdelform-<?= (int)$v['id']; ?>" method="POST" style="display:none;"
              onsubmit="return confirm('Remove this vehicle type?')">
            <input type="hidden" name="vehicle_action" value="delete">
            <input type="hidden" name="vehicle_id" value="<?= (int)$v['id']; ?>">
        </form>
    <?php endforeach; ?>

    <form method="POST" style="display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="vehicle_action" value="add">
        <input type="text" name="name" placeholder="Vehicle name (e.g. Modern Jeepney)" required style="padding:6px;">
        <input type="number" name="quantity" min="0" placeholder="Quantity" required style="padding:6px; width:100px;">
        <button type="submit" class="action-btn approve">+ Add Vehicle Type</button>
    </form>
</div>

<div class="table-container">
    <h2>Transport / Rent Requests</h2>

    <div class="filter-bar">
        <a href="?status=new" class="<?= $statusFilter === 'new' ? 'active' : '' ?>">Pending</a>
        <a href="?status=approved" class="<?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</a>
        <a href="?status=rejected" class="<?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected</a>
        <a href="?status=cancelled" class="<?= $statusFilter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
        <a href="?status=all" class="<?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Business/Name</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Passengers</th>
            <th>From</th>
            <th>Until</th>
            <th>Pickup</th>
            <th>Dropoff</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($requests as $row): ?>
        <tr>
            <td><?= (int)$row['id']; ?></td>
            <td><?= htmlspecialchars($row['business_name']); ?></td>
            <td><?= htmlspecialchars($row['phone']); ?></td>
            <td><?= htmlspecialchars($row['email']); ?></td>
            <td><?= (int)$row['passengers']; ?></td>
            <td><?= htmlspecialchars($row['from_date']); ?></td>
            <td><?= htmlspecialchars($row['until_date']); ?></td>
            <td><?= htmlspecialchars($row['pickup_address']); ?></td>
            <td><?= htmlspecialchars($row['dropoff_address']); ?></td>
            <td><span class="badge <?= htmlspecialchars($row['status']); ?>"><?= ucfirst($row['status']); ?></span></td>
            <td><?= htmlspecialchars($row['created_at']); ?></td>
            <td>
                <?php if ($row['status'] === 'new'): ?>
                    <?php $exceeds = would_exceed_capacity($approvedRequests, (int)$row['id'], $row['from_date'], $row['until_date'], $totalVehicles); ?>
                    <a href="?action=approve&id=<?= (int)$row['id']; ?>&status=<?= urlencode($statusFilter); ?>&page=<?= $page; ?>"
                       onclick="return confirm('<?= $exceeds ? 'Warning: this may exceed vehicle capacity for these dates. ' : '' ?>Approve this rent request?')">
                        <button class="action-btn approve">Approve</button>
                    </a>
                    <a href="?action=reject&id=<?= (int)$row['id']; ?>&status=<?= urlencode($statusFilter); ?>&page=<?= $page; ?>"
                       onclick="return confirm('Reject this rent request?')">
                        <button class="action-btn reject">Reject</button>
                    </a>
                    <?php if ($exceeds): ?>
                        <div class="warn-badge">⚠ May exceed capacity</div>
                    <?php endif; ?>
                <?php elseif ($row['status'] === 'approved'): ?>
                    <a href="?action=cancel&id=<?= (int)$row['id']; ?>&status=<?= urlencode($statusFilter); ?>&page=<?= $page; ?>"
                       onclick="return confirm('Cancel this approved booking?')">
                        <button class="action-btn cancel">Cancel</button>
                    </a>
                <?php endif; ?>

                <?php if (true): ?>
                    <a href="?action=delete&id=<?= (int)$row['id']; ?>&status=<?= urlencode($statusFilter); ?>&page=<?= $page; ?>"
                       onclick="return confirm('Permanently delete this request?')">
                        <button class="action-btn delete">Delete</button>
                    </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php
        function page_link($p, $statusFilter) {
            return '?status=' . urlencode($statusFilter) . '&page=' . (int) $p;
        }
    ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="<?= page_link($page - 1, $statusFilter); ?>">&laquo; Prev</a>
        <?php else: ?>
            <span class="disabled">&laquo; Prev</span>
        <?php endif; ?>

        <?php
        $windowStart = max(1, $page - 2);
        $windowEnd   = min($totalPages, $page + 2);
        if ($windowStart > 1) echo '<a href="' . page_link(1, $statusFilter) . '">1</a><span>...</span>';
        for ($p = $windowStart; $p <= $windowEnd; $p++):
        ?>
            <?php if ($p === $page): ?>
                <span class="current"><?= $p; ?></span>
            <?php else: ?>
                <a href="<?= page_link($p, $statusFilter); ?>"><?= $p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($windowEnd < $totalPages) echo '<span>...</span><a href="' . page_link($totalPages, $statusFilter) . '">' . $totalPages . '</a>'; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= page_link($page + 1, $statusFilter); ?>">Next &raquo;</a>
        <?php else: ?>
            <span class="disabled">Next &raquo;</span>
        <?php endif; ?>
    </div>
    <div class="pagination-info">
        Showing <?= count($requests); ?> of <?= $totalCount; ?> request(s) — page <?= $page; ?> of <?= $totalPages; ?>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>
