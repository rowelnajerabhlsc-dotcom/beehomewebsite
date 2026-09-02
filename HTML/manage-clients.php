<?php
session_start();
include "config.php";
include "permissions.php";

/* =========================================================
   ACCESS CONTROL — Administrator (role 4) ONLY
   ---------------------------------------------------------
   NOTE: this project's other pages call require_role(N) from
   permissions.php. I'm assuming require_role(4) exists and behaves
   the same way it does on records.php / generate_reg_link.php (i.e.
   redirects/exits for anyone below that role). If require_role()
   does NOT exist yet, delete the two lines below this comment block
   and uncomment the inline fallback check instead.
   ========================================================= */
require_role(4); // Administrators only

// --- Inline fallback (uncomment if require_role() isn't available) ---
// if (!isset($_SESSION['user_id']) || (int) ($_SESSION['role'] ?? 0) < 4) {
//     header("Location: /login");
//     exit();
// }

/* =========================================================
   CSRF TOKEN
   ========================================================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function csrf_ok(): bool {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/* =========================================================
   HELPERS
   ========================================================= */

// Case-insensitive duplicate-name check against active + inactive rows.
// $excludeId lets update-checks skip the row currently being edited.
function client_name_taken(mysqli $conn, string $name, ?int $excludeId = null): bool {
    if ($excludeId !== null) {
        $stmt = $conn->prepare("SELECT id FROM clients WHERE LOWER(client_name) = LOWER(?) AND id != ? LIMIT 1");
        $stmt->bind_param("si", $name, $excludeId);
    } else {
        $stmt = $conn->prepare("SELECT id FROM clients WHERE LOWER(client_name) = LOWER(?) LIMIT 1");
        $stmt->bind_param("s", $name);
    }
    $stmt->execute();
    $stmt->store_result();
    $taken = $stmt->num_rows > 0;
    $stmt->close();
    return $taken;
}

function redirect_with(string $param, string $value = '1'): void {
    header("Location: /manage-clients?" . urlencode($param) . "=" . urlencode($value));
    exit();
}

/* =========================================================
   HANDLE POST ACTIONS (create / update / toggle / delete)
   All state-changing actions are POST + CSRF-token gated.
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_ok()) {
        redirect_with('error', 'Security check failed. Please try again.');
    }

    $action = $_POST['action'] ?? '';

    /* ---------------- CREATE ---------------- */
    if ($action === 'create') {
        $name = trim($_POST['client_name'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            redirect_with('error', 'Client name is required.');
        }
        if (mb_strlen($name) > 150) {
            redirect_with('error', 'Client name is too long (150 characters max).');
        }
        if (client_name_taken($conn, $name)) {
            redirect_with('error', 'A client with that name already exists.');
        }

        $stmt = $conn->prepare("INSERT INTO clients (client_name, is_active) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $isActive);
        if (!$stmt->execute()) {
            error_log('manage_clients create failed: ' . $stmt->error);
            redirect_with('error', 'Could not create client. Please try again.');
        }
        $stmt->close();
        redirect_with('created');
    }

    /* ---------------- UPDATE ---------------- */
    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['client_name'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0) {
            redirect_with('error', 'Invalid client.');
        }
        if ($name === '') {
            redirect_with('error', 'Client name is required.');
        }
        if (mb_strlen($name) > 150) {
            redirect_with('error', 'Client name is too long (150 characters max).');
        }
        if (client_name_taken($conn, $name, $id)) {
            redirect_with('error', 'A client with that name already exists.');
        }

        $stmt = $conn->prepare("UPDATE clients SET client_name = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sii", $name, $isActive, $id);
        if (!$stmt->execute()) {
            error_log('manage_clients update failed: ' . $stmt->error);
            redirect_with('error', 'Could not update client. Please try again.');
        }
        $stmt->close();
        redirect_with('updated');
    }

    /* ---------------- TOGGLE ACTIVE (quick soft delete / restore) ---------------- */
    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect_with('error', 'Invalid client.');
        }

        $stmt = $conn->prepare("UPDATE clients SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log('manage_clients toggle failed: ' . $stmt->error);
            redirect_with('error', 'Could not update client status.');
        }
        $stmt->close();
        redirect_with('statuschanged');
    }

    /* ---------------- HARD DELETE ---------------- */
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect_with('error', 'Invalid client.');
        }

        // clients.id <- user_profiles.client_id is ON DELETE SET NULL,
        // so this will not error — it will just clear the assignment on
        // any member profile that referenced this client.
        $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log('manage_clients delete failed: ' . $stmt->error);
            redirect_with('error', 'Could not delete client.');
        }
        $stmt->close();
        redirect_with('deleted');
    }

    redirect_with('error', 'Unknown action.');
}

/* =========================================================
   LIST VIEW — search, sort, and per-client assignment counts
   ========================================================= */
$search = trim($_GET['search'] ?? '');

$sortColumn = $_GET['sort'] ?? 'name';
$sortDir    = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$sortMap = [
    'name'    => 'c.client_name',
    'created' => 'c.created_at',
    'updated' => 'c.updated_at',
];
$orderBy = $sortMap[$sortColumn] ?? $sortMap['name'];

$sql = "
    SELECT
        c.id, c.client_name, c.is_active, c.created_at, c.updated_at,
        (SELECT COUNT(*) FROM user_profiles up WHERE up.client_id = c.id) AS member_count
    FROM clients c
";
$params = [];
$types  = '';

if ($search !== '') {
    $sql .= " WHERE c.client_name LIKE ? ";
    $params[] = '%' . $search . '%';
    $types   .= 's';
}

$sql .= " ORDER BY $orderBy $sortDir";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Pre-fetch data for the edit modal if ?edit=ID is present, from the
   already-loaded $clients array (no extra query needed). */
$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editRow = null;
if ($editingId) {
    foreach ($clients as $c) {
        if ((int) $c['id'] === $editingId) {
            $editRow = $c;
            break;
        }
    }
}

function sort_link(string $column, string $label, string $currentSort, string $currentDir, string $search): string {
    $nextDir = ($currentSort === $column && $currentDir === 'ASC') ? 'desc' : 'asc';
    $arrow = '';
    if ($currentSort === $column) {
        $arrow = $currentDir === 'ASC' ? ' &uarr;' : ' &darr;';
    }
    $qs = http_build_query(['sort' => $column, 'dir' => $nextDir, 'search' => $search]);
    return '<a href="?' . $qs . '" class="sort-link">' . htmlspecialchars($label) . $arrow . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Clients</title>
<link rel="stylesheet" href="../CSS/navbar.css">
<link rel="icon" href="IMAGES/logo.png">
<style>
    :root {
        --primary: #096D2B;
        --accent: #2cab4a;
        --mid: #26a753;
        --tint-1: #e7f5ea;
        --tint-2: #cdeed8;
        --ink: #1e2b22;
        --muted: #5a6b5f;
        --muted-2: #7c8a80;
        --gold: #F5C233;
        --border: #e2e8e4;
    }
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #f7f9f7; margin: 0; color: var(--ink); }
    .page-wrap { max-width: 1200px; margin: 0 auto; padding: 30px 20px 60px; }

    .section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
    }
    .section-header .accent-bar {
        width: 5px;
        height: 22px;
        background: var(--accent);
        border-radius: 3px;
    }
    .section-header h1 {
        font-size: 1.4em;
        font-weight: 700;
        margin: 0;
        color: var(--ink);
    }
    .page-subtitle { color: var(--muted); margin: 0 0 22px 15px; font-size: 0.92em; }

    .banner {
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 18px;
        font-weight: 600;
        font-size: 0.92em;
    }
    .banner.success { background: var(--tint-1); color: var(--primary); border: 1px solid var(--tint-2); }
    .banner.error   { background: #fdeaea; color: #a12626; border: 1px solid #f3c9c9; }

    .card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(9, 109, 43, 0.05);
        margin-bottom: 20px;
    }

    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }
    .search-form { display: flex; gap: 8px; }
    .search-form input[type="text"] {
        padding: 9px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.9em;
        min-width: 220px;
    }
    .search-form button, .btn {
        padding: 9px 16px;
        border: none;
        border-radius: 8px;
        font-size: 0.9em;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-primary { background: var(--gold); color: #4a3800; }
    .btn-primary:hover { filter: brightness(0.96); }
    .btn-outline { background: #fff; border: 1px solid var(--primary); color: var(--primary); }
    .btn-outline:hover { background: var(--tint-1); }

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 10px; border-bottom: 1px solid var(--border); text-align: left; font-size: 0.9em; }
    th { color: var(--muted); font-weight: 600; font-size: 0.82em; text-transform: uppercase; letter-spacing: 0.02em; }
    .sort-link { color: var(--muted); text-decoration: none; }
    .sort-link:hover { color: var(--primary); }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.78em;
        font-weight: 700;
    }
    .badge.active { background: var(--tint-1); color: var(--primary); }
    .badge.inactive { background: #eee; color: #777; }

    .member-count {
        color: var(--muted-2);
        font-size: 0.88em;
    }

    .row-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .row-actions form { display: inline; }
    .action-btn {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        font-size: 0.82em;
        font-weight: 600;
        cursor: pointer;
        color: #fff;
    }
    .action-edit { background: var(--mid); }
    .action-toggle { background: var(--muted-2); }
    .action-delete { background: #c0392b; }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--muted);
    }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1000;
        background: rgba(0,0,0,0.5);
        align-items: flex-start;
        justify-content: center;
        padding: 60px 16px;
        overflow-y: auto;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #fff;
        border-radius: 14px;
        max-width: 440px;
        width: 100%;
        padding: 26px;
    }
    .modal-box h2 { margin: 0 0 18px; font-size: 1.15em; color: var(--ink); }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; margin-bottom: 6px; font-size: 0.88em; font-weight: 600; color: var(--muted); }
    .form-group input[type="text"] {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.95em;
    }
    .checkbox-row { display: flex; align-items: center; gap: 8px; }
    .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
</style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="page-wrap">

    <div class="section-header">
        <span class="accent-bar"></span>
        <h1>Manage Clients</h1>
    </div>
    <p class="page-subtitle">Clients available for member "Client Assignment" — Administrator only.</p>

    <?php if (isset($_GET['created'])): ?>
        <div class="banner success">Client created successfully.</div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="banner success">Client updated successfully.</div>
    <?php elseif (isset($_GET['statuschanged'])): ?>
        <div class="banner success">Client status updated.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="banner success">Client deleted. Any member assignments to it have been cleared.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="banner error"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search by client name..." value="<?= htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-outline">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="/manage-clients" class="btn btn-outline" style="text-decoration:none; display:inline-flex; align-items:center;">Clear</a>
                <?php endif; ?>
            </form>
            <button type="button" class="btn btn-primary" id="openCreateModal">+ Add Client</button>
        </div>

        <?php if (empty($clients)): ?>
            <div class="empty-state">
                <?php if ($search !== ''): ?>
                    No clients match "<?= htmlspecialchars($search); ?>".
                <?php else: ?>
                    No clients have been added yet. Click "+ Add Client" to create the first one.
                <?php endif; ?>
            </div>
        <?php else: ?>
        <table>
            <tr>
                <th><?= sort_link('name', 'Client Name', $sortColumn, $sortDir, $search); ?></th>
                <th>Status</th>
                <th>Members Assigned</th>
                <th><?= sort_link('created', 'Created', $sortColumn, $sortDir, $search); ?></th>
                <th><?= sort_link('updated', 'Updated', $sortColumn, $sortDir, $search); ?></th>
                <th>Actions</th>
            </tr>
            <?php foreach ($clients as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['client_name']); ?></td>
                <td>
                    <span class="badge <?= $c['is_active'] ? 'active' : 'inactive'; ?>">
                        <?= $c['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
                <td class="member-count"><?= (int) $c['member_count']; ?> member(s)</td>
                <td><?= htmlspecialchars($c['created_at']); ?></td>
                <td><?= htmlspecialchars($c['updated_at']); ?></td>
                <td>
                    <div class="row-actions">
                        <button type="button" class="action-btn action-edit"
                                onclick='openEditModal(<?= json_encode([
                                    "id" => (int) $c["id"],
                                    "client_name" => $c["client_name"],
                                    "is_active" => (int) $c["is_active"],
                                ]); ?>)'>
                            Edit
                        </button>

                        <form method="POST" action="/manage-clients">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int) $c['id']; ?>">
                            <button type="submit" class="action-btn action-toggle"
                                    onclick="return confirm('<?= $c['is_active'] ? 'Deactivate' : 'Activate'; ?> this client? <?= $c['is_active'] ? 'It will disappear from the Client Assignment dropdown, but existing assignments are kept.' : ''; ?>')">
                                <?= $c['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </form>

                        <form method="POST" action="/manage-clients">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $c['id']; ?>">
                            <button type="submit" class="action-btn action-delete"
                                    onclick="return confirm('Permanently delete \'<?= htmlspecialchars(addslashes($c['client_name'])); ?>\'? This cannot be undone. <?= (int) $c['member_count']; ?> member profile(s) currently reference this client — their Client Assignment will be cleared (set to none), not blocked.')">
                                Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

</div>

<!-- CREATE MODAL -->
<div class="modal-overlay" id="createModalOverlay">
    <div class="modal-box">
        <h2>Add Client</h2>
        <form method="POST" action="/manage-clients">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label>Client Name</label>
                <input type="text" name="client_name" maxlength="150" required autofocus>
            </div>

            <div class="form-group checkbox-row">
                <input type="checkbox" name="is_active" id="createIsActive" checked>
                <label for="createIsActive" style="margin:0;">Active (visible in Client Assignment dropdown)</label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('createModalOverlay')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Client</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModalOverlay">
    <div class="modal-box">
        <h2>Edit Client</h2>
        <form method="POST" action="/manage-clients">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="editId" value="">

            <div class="form-group">
                <label>Client Name</label>
                <input type="text" name="client_name" id="editClientName" maxlength="150" required>
            </div>

            <div class="form-group checkbox-row">
                <input type="checkbox" name="is_active" id="editIsActive">
                <label for="editIsActive" style="margin:0;">Active (visible in Client Assignment dropdown)</label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModalOverlay')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

document.getElementById('openCreateModal').addEventListener('click', function () {
    openModal('createModalOverlay');
});

function openEditModal(data) {
    document.getElementById('editId').value = data.id;
    document.getElementById('editClientName').value = data.client_name;
    document.getElementById('editIsActive').checked = !!data.is_active;
    openModal('editModalOverlay');
}

// Close modals on backdrop click or Escape
document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.classList.remove('open');
    });
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(function (o) {
            o.classList.remove('open');
        });
    }
});

<?php if ($editRow): ?>
// Auto-open edit modal if ?edit=ID was in the URL
openEditModal(<?= json_encode([
    "id" => (int) $editRow["id"],
    "client_name" => $editRow["client_name"],
    "is_active" => (int) $editRow["is_active"],
]); ?>);
<?php endif; ?>
</script>

</body>
</html>
<?php $conn->close(); ?>
