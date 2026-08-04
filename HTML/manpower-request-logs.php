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

    /* Keep the table compact/scannable; full record is shown in the
       View modal instead of cramming long text into every row */
    .logs-container {
        overflow-x: auto;
    }
    .logs-container td.truncate {
        max-width: 180px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .actions-cell {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .modal-box dl {
        display: grid;
        grid-template-columns: 160px 1fr;
        row-gap: 10px;
        column-gap: 12px;
    }
    .modal-box dt {
        font-weight: bold;
        color: #555;
    }
    .modal-box dd {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
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
                    <th>Email</th>
                    <th>Telephone</th>
                    <th>Requested Position</th>
                    <th># Required</th>
                    <th>Place of Assignment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="9" style="text-align:center;">No manpower requests found.</td>
                </tr>
                <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id']; ?></td>
                    <td class="truncate"><?= htmlspecialchars($row['business_name']); ?></td>
                    <td class="truncate"><?= htmlspecialchars($row['contact_person']); ?></td>
                    <td class="truncate"><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['telephone']); ?></td>
                    <td class="truncate"><?= htmlspecialchars($row['req_position']); ?></td>
                    <td><?= (int)$row['number_required']; ?></td>
                    <td class="truncate"><?= htmlspecialchars($row['assignment_place']); ?></td>
                    <td class="actions-cell">
                        <button type="button"
                                class="btn-view"
                                data-id="<?= (int)$row['id']; ?>"
                                data-business_name="<?= htmlspecialchars($row['business_name']); ?>"
                                data-contact_person="<?= htmlspecialchars($row['contact_person']); ?>"
                                data-position="<?= htmlspecialchars($row['position']); ?>"
                                data-email="<?= htmlspecialchars($row['email']); ?>"
                                data-telephone="<?= htmlspecialchars($row['telephone']); ?>"
                                data-fax="<?= htmlspecialchars($row['fax']); ?>"
                                data-website="<?= htmlspecialchars($row['website']); ?>"
                                data-req_position="<?= htmlspecialchars($row['req_position']); ?>"
                                data-number_required="<?= (int)$row['number_required']; ?>"
                                data-job_description="<?= htmlspecialchars($row['job_description']); ?>"
                                data-assignment_place="<?= htmlspecialchars($row['assignment_place']); ?>">
                            View
                        </button>
                        <?php if (is_manager_or_admin()): ?>
                        <a href="delete-manpower.php?id=<?= (int)$row['id']; ?>"
                           onclick="return confirm('Delete this manpower request?')">
                            <button type="button" class="btn-delete">Delete</button>
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

<!-- VIEW MODAL -->
<div class="modal-overlay" id="viewModalOverlay">
    <div class="modal-box">
        <h2>Manpower Request Details</h2>
        <dl>
            <dt>ID</dt><dd id="vm-id"></dd>
            <dt>Business Name</dt><dd id="vm-business_name"></dd>
            <dt>Contact Person</dt><dd id="vm-contact_person"></dd>
            <dt>Position (Contact)</dt><dd id="vm-position"></dd>
            <dt>Email</dt><dd id="vm-email"></dd>
            <dt>Telephone</dt><dd id="vm-telephone"></dd>
            <dt>Fax</dt><dd id="vm-fax"></dd>
            <dt>Website</dt><dd id="vm-website"></dd>
            <dt>Requested Position</dt><dd id="vm-req_position"></dd>
            <dt># Required</dt><dd id="vm-number_required"></dd>
            <dt>Job Description</dt><dd id="vm-job_description"></dd>
            <dt>Place of Assignment</dt><dd id="vm-assignment_place"></dd>
        </dl>
        <button type="button" class="close-btn" id="vm-close">Close</button>
    </div>
</div>

<script>
(function () {
    var overlay = document.getElementById('viewModalOverlay');
    var pageContent = document.getElementById('page-content');
    var closeBtn = document.getElementById('vm-close');

    var fields = [
        'id', 'business_name', 'contact_person', 'position', 'email',
        'telephone', 'fax', 'website', 'req_position', 'number_required',
        'job_description', 'assignment_place'
    ];

    function openModal(dataset) {
        fields.forEach(function (key) {
            var el = document.getElementById('vm-' + key);
            var value = dataset[key];
            el.textContent = (value === undefined || value === '') ? '—' : value;
        });
        overlay.style.display = 'flex';
        pageContent.classList.add('blur-background');
    }

    function closeModal() {
        overlay.style.display = 'none';
        pageContent.classList.remove('blur-background');
    }

    document.querySelectorAll('.btn-view').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.dataset);
        });
    });

    closeBtn.addEventListener('click', closeModal);

    // Close when clicking the dimmed backdrop (not the modal box itself)
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.style.display === 'flex') {
            closeModal();
        }
    });
})();
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
