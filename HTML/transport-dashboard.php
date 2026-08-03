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

    header("Location: /transport-dashboard?status=" . urlencode($_GET['status'] ?? 'new'));
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
            header("Location: /transport-dashboard?status=" . urlencode($_GET['status'] ?? 'new') . "&err=notfound");
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
            header("Location: /transport-dashboard?status=" . urlencode($_GET['status'] ?? 'new') . "&err=capacity");
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

    header("Location: /transport-dashboard");
    exit();
}

/* =========================================================
   FILTER + LIST
   ========================================================= */
$statusFilter = $_GET['status'] ?? 'new'; // default view: pending requests

$where  = [];
$params = [];
$types  = '';

if ($statusFilter !== 'all') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}

$sql = "SELECT * FROM rent_requests";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* =========================================================
   FLEET DATA
   ========================================================= */
$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$totalVehicles = 0;
foreach ($vehicles as $v) { $totalVehicles += (int) $v['quantity']; }

/* =========================================================
   CAPACITY WARNING per row (only meaningful for pending requests):
   would approving this request exceed capacity for any date it covers?
   ========================================================= */
function would_exceed_capacity($conn, $id, $fromDate, $untilDate, $totalVehicles) {
    if ($totalVehicles <= 0) return true;
    $stmt = $conn->prepare(
        "SELECT COUNT(*) c FROM rent_requests
         WHERE status = 'approved' AND id != ? AND from_date <= ? AND until_date >= ?"
    );
    $stmt->bind_param("iss", $id, $untilDate, $fromDate);
    $stmt->execute();
    $count = (int) $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    return $count >= $totalVehicles;
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
</style>
</head>
<body>

<?php include "navbar.php"; ?>

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
        <?php while ($row = $result->fetch_assoc()): ?>
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
                    <?php $exceeds = would_exceed_capacity($conn, (int)$row['id'], $row['from_date'], $row['until_date'], $totalVehicles); ?>
                    <a href="?action=approve&id=<?= (int)$row['id']; ?>&status=<?= urlencode($statusFilter); ?>"
                       onclick="return confirm('<?= $exceeds ? 'Warning: this may exceed vehicle capacity for these dates. ' : '' ?>Approve this rent request?')">
                        <button class="action-btn approve">Approve</button>
                    </a>
                    <a href="?action=reject&id=<?= (int)$row['id']; ?>&status=<?= urlencode($statusFilter); ?>"
                       onclick="return confirm('Reject this rent request?')">
                        <button class="action-btn reject">Reject</button>
                    </a>
                    <?php if ($exceeds): ?>
                        <div class="warn-badge">⚠ May exceed capacity</div>
                    <?php endif; ?>
                <?php elseif ($row['status'] === 'approved'): ?>
                    <a href="?action=cancel&id=<?= (int)$row['id']; ?>&status=<?= urlencode($statusFilter); ?>"
                       onclick="return confirm('Cancel this approved booking?')">
                        <button class="action-btn cancel">Cancel</button>
                    </a>
                <?php endif; ?>

                <?php if (is_manager_or_admin()): ?>
                    <a href="?action=delete&id=<?= (int)$row['id']; ?>&status=<?= urlencode($statusFilter); ?>"
                       onclick="return confirm('Permanently delete this request?')">
                        <button class="action-btn delete">Delete</button>
                    </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
<?php $conn->close(); ?>
