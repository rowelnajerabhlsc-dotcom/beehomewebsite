<?php
session_start();
include "config.php";       // provides $conn (mysqli)
include "permissions.php";

/* ACCESS CONTROL - Require at least Manager role */
require_role(3);

/* ============================================================
   PAGINATION
   ============================================================ */
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

/* Total row count (for total pages) */
$count_result = $conn->query("SELECT COUNT(*) AS total FROM manpower_requests");
$total_rows   = $count_result ? (int)$count_result->fetch_assoc()['total'] : 0;
$total_pages  = max(1, (int)ceil($total_rows / $per_page));

/* Clamp page to valid range in case of a stale/manual ?page= value */
if ($page > $total_pages) {
    $page   = $total_pages;
    $offset = ($page - 1) * $per_page;
}

/* FETCH REQUESTS (paginated) */
$stmt = $conn->prepare(
    "SELECT * FROM manpower_requests ORDER BY id DESC LIMIT ? OFFSET ?"
);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

/* ---- TEMP DEBUG: remove once row count is confirmed ---- */
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "total_rows (COUNT query): " . $total_rows . "\n";
    echo "total_pages: " . $total_pages . "\n";
    echo "page: " . $page . " | offset: " . $offset . " | per_page: " . $per_page . "\n";
    echo "result->num_rows (this page): " . $result->num_rows . "\n";
    $check = $conn->query("SELECT COUNT(*) c FROM manpower_requests");
    echo "raw COUNT(*) direct query: " . $check->fetch_assoc()['c'] . "\n";
    echo "connected database: " . $conn->query("SELECT DATABASE() d")->fetch_assoc()['d'] . "\n";
    echo "</pre>";
}
/* ---------------------------------------------------------- */
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manpower Request Logs</title>

<link rel="stylesheet" href="../CSS/navbar.css">
<link rel="stylesheet" href="../CSS/home.css">
<link rel="stylesheet" href="../CSS/manpower-request-logs.css">
<link rel="icon" href="IMAGES/logo.png">

<style>
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin: 24px 0;
        flex-wrap: wrap;
    }
    .pagination .page-link {
        display: inline-block;
        padding: 8px 14px;
        border: 1px solid #ddd;
        border-radius: 6px;
        text-decoration: none;
        color: #333;
        font-size: 14px;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .pagination .page-link:hover {
        background-color: #f0f0f0;
    }
    .pagination .page-link.active {
        background-color: #333;
        color: #fff;
        border-color: #333;
        font-weight: bold;
    }
</style>

</head>
<body>

<div id="page-content">

    <?php include "navbar.php"; ?>

    <div class="logs-container">

        <h2>Manpower Request Logs</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Business Name</th>
                    <th>Contact Person</th>
                    <th>Position (Contact)</th>
                    <th>Email</th>
                    <th>Telephone</th>
                    <th>Fax</th>
                    <th>Website</th>
                    <th>Requested Position</th>
                    <th># Required</th>
                    <th>Job Description</th>
                    <th>Place of Assignment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="12" style="text-align:center;">No manpower requests found.</td>
                </tr>
                <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id']; ?></td>
                    <td><?= htmlspecialchars($row['business_name']); ?></td>
                    <td><?= htmlspecialchars($row['contact_person']); ?></td>
                    <td><?= htmlspecialchars($row['position']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['telephone']); ?></td>
                    <td><?= htmlspecialchars($row['fax']); ?></td>
                    <td><?= htmlspecialchars($row['website']); ?></td>
                    <td><?= htmlspecialchars($row['req_position']); ?></td>
                    <td><?= (int)$row['number_required']; ?></td>
                    <td><?= nl2br(htmlspecialchars($row['job_description'])); ?></td>
                    <td><?= nl2br(htmlspecialchars($row['assignment_place'])); ?></td>
                    <td>
                        <?php if (is_manager_or_admin()): ?>
                        <a href="delete-manpower.php?id=<?= (int)$row['id']; ?>"
                           onclick="return confirm('Delete this manpower request?')">
                            <button class="action-btn delete">Delete</button>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">

            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="page-link">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>"
                   class="page-link <?= $i === $page ? 'active' : '' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>

        </div>
        <?php endif; ?>

    </div>

</div>

<script>
// JavaScript for modals and interactivity would go here
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
