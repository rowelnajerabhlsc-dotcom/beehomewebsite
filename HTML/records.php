<?php
session_start();
include "config.php";
include "permissions.php";

/* ACCESS CONTROL */
require_role(3); // must be at least Manager to reach this page

/* =========================================================
   AJAX: FETCH A SINGLE EXTRA COLUMN ON DEMAND
   (?ajax_column=<field>) — used by the "Show More Columns"
   dropdown. Only returns data for whitelisted columns.
   ========================================================= */
if (isset($_GET['ajax_column'])) {
    header('Content-Type: application/json');

    // Whitelist: every field here must exist in user_profiles
    // (schema mirrored from edit_Profile.php), or be the special
    // 'client_name' case which is joined from the clients table.
    $allowed_columns = [
        'tin_no', 'sss_no', 'blood_type', 'pagibig_no', 'philhealth_no',
        'religion', 'pmes_orientation_date', 'facebook_account',
        'department', 'position', 'address', 'contact_number', 'birthday',
        'civil_status', 'gender', 'height_cm', 'weight_kg', 'no_of_dependents',
        'emergency_name', 'emergency_address', 'emergency_relationship', 'emergency_contact_no',
        'batching_id', 'client_name'
    ];

    $col = $_GET['ajax_column'];

    if (!in_array($col, $allowed_columns, true)) {
        echo json_encode(['error' => 'Invalid column']);
        exit();
    }

    if ($col === 'client_name') {
        $sql = "SELECT p.user_id, c.client_name AS value
                FROM user_profiles p
                LEFT JOIN clients c ON p.client_id = c.id";
    } else {
        // $col is safe here — validated against the hardcoded whitelist above
        $sql = "SELECT user_id, `$col` AS value FROM user_profiles";
    }

    $data = [];
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $data[$r['user_id']] = $r['value'];
        }
    }

    echo json_encode($data);
    exit();
}

/* =========================================================
   HANDLE EDIT (POST) — update user and profile, then back to list
   ========================================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['edit'])) {

    $user_id = (int) $_GET['edit'];

    /* ROLE-BASED TARGET CHECK */
    $target_role = get_user_role_by_id($conn, $user_id);
    if ($target_role === null || !can_manage_target($_SESSION['role'], $target_role)) {
        header("Location: /records");
        exit();
    }

    $username       = $_POST['username'];
    $email          = $_POST['email'];
    $role           = $_POST['role'];

    /* PREVENT PRIVILEGE ESCALATION VIA FORM */
    if ($_SESSION['role'] == 3 && $role >= 3) {
        header("Location: /records");
        exit();
    }

    $fname          = $_POST['fname'];
    $mname          = $_POST['mname'];
    $lname          = $_POST['lname'];
    $address        = $_POST['address'];
    $department     = $_POST['department'];
    $position       = $_POST['position'];
    $contact_number = $_POST['contact_number'];
    $birthday       = $_POST['birthday'];
    $civil_status   = $_POST['civil_status'];
    $gender         = $_POST['gender'];

    /* Ensure profile row exists */
    $check = $conn->prepare("SELECT user_id FROM user_profiles WHERE user_id=?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
        $insert->bind_param("i", $user_id);
        $insert->execute();
        $insert->close();
    }
    $check->close();

    /* Update USERS */
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
    $stmt->bind_param("ssii", $username, $email, $role, $user_id);
    $stmt->execute();
    $stmt->close();

    /* Update PROFILE */
    $stmt = $conn->prepare("
        UPDATE user_profiles SET
            fname=?, mname=?, lname=?,
            address=?, contact_number=?,
            department=?, position=?,
            birthday=?, civil_status=?, gender=?
        WHERE user_id=?
    ");
    $stmt->bind_param(
        "ssssssssssi",
        $fname, $mname, $lname,
        $address, $contact_number,
        $department, $position,
        $birthday, $civil_status, $gender,
        $user_id
    );
    $stmt->execute();
    $stmt->close();

    header("Location: /records");
    exit();
}

/* =========================================================
   HANDLE DELETE (GET ?delete=<id>) — self-delete guard + DELETE
   ========================================================= */
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];

    if ($del_id !== (int) $_SESSION['user_id']) {

        /* ROLE-BASED TARGET CHECK */
        $target_role = get_user_role_by_id($conn, $del_id);
        if ($target_role === null || !can_manage_target($_SESSION['role'], $target_role)) {
            header("Location: /records");
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: /records");
    exit();
}

/* =========================================================
   EDIT MODE (GET ?edit=<id>) — fetch row to populate the form
   ========================================================= */
$editing = false;
$edit_row = null;

if (isset($_GET['edit'])) {
    $editing = true;
    $user_id = (int) $_GET['edit'];

    /* ROLE-BASED TARGET CHECK — block viewing the edit modal too */
    $target_role = get_user_role_by_id($conn, $user_id);
    if ($target_role === null || !can_manage_target($_SESSION['role'], $target_role)) {
        header("Location: /records");
        exit();
    }

    $stmt = $conn->prepare("
        SELECT
            u.id, u.username, u.email, u.role,
            p.fname, p.mname, p.lname,
            p.address, p.contact_number,
            p.department, p.position,
            p.birthday, p.civil_status, p.gender
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result(
        $e_id, $username, $email, $role,
        $fname, $mname, $lname,
        $address, $contact_number,
        $department, $position,
        $birthday, $civil_status, $gender
    );

    if ($stmt->fetch()) {
        $edit_row = compact(
            'username','email','role',
            'fname','mname','lname',
            'address','contact_number',
            'department','position',
            'birthday','civil_status','gender'
        );
    }
    $stmt->close();
}

/* =========================================================
   LIST DATA — default columns only.
   Extra profile fields (TIN, SSS, blood type, emergency contact,
   etc.) are intentionally NOT selected here; they're fetched on
   demand via ?ajax_column=<field> when the user picks them from
   the "Show More Columns" dropdown.
   ========================================================= */
$stmt = $conn->prepare("
    SELECT
        u.id,
        u.username,
        u.email,
        u.role,
        p.fname, p.mname, p.lname
    FROM users u
    LEFT JOIN user_profiles p ON u.id = p.user_id
    ORDER BY u.id ASC
");
$stmt->execute();
$result = $stmt->get_result();

/* Column labels used both server-side (dropdown render) and
   passed to JS for building the dynamic <th>/<td> on fetch. */
$extra_columns = [
    'tin_no'                 => 'TIN No.',
    'sss_no'                 => 'SSS No.',
    'blood_type'              => 'Blood Type',
    'pagibig_no'              => 'Pag-IBIG No.',
    'philhealth_no'           => 'PhilHealth No.',
    'religion'                => 'Religion',
    'pmes_orientation_date'   => 'PMES Orientation Date',
    'facebook_account'        => 'Facebook Account',
    'department'              => 'Department',
    'position'                => 'Position',
    'address'                 => 'Address',
    'contact_number'          => 'Contact Number',
    'birthday'                => 'Birthday',
    'civil_status'            => 'Civil Status',
    'gender'                  => 'Gender',
    'height_cm'               => 'Height (cm)',
    'weight_kg'                => 'Weight (kg)',
    'no_of_dependents'        => 'No. of Dependents',
    'emergency_name'          => 'Emergency Contact Name',
    'emergency_address'       => 'Emergency Contact Address',
    'emergency_relationship'  => 'Emergency Relationship',
    'emergency_contact_no'    => 'Emergency Contact No.',
    'batching_id'             => 'Batching ID',
    'client_name'             => 'Client',
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Records</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" href="IMAGES/logo.png">
    <style>
        /* Minimal styling for the column selector — kept inline since
           this is a small, self-contained addition to an existing page. */
        .column-selector { position: relative; display: inline-block; }
        .column-selector .fetch-btn {
            cursor: pointer;
        }
        .column-dropdown-panel {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 20;
            margin-top: 4px;
            padding: 10px 14px;
            background: #ffffff;
            border: 1px solid #cdeed8;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(9, 109, 43, 0.15);
            max-height: 320px;
            overflow-y: auto;
            min-width: 220px;
        }
        .column-dropdown-panel label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
            font-size: 0.9rem;
            color: #1e2b22;
            white-space: nowrap;
        }
        .column-dropdown-panel.open { display: block; }
    </style>
</head>
<body>

<div class="table-container">

    <h2>Employee Records</h2>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search employees...">

        <div class="column-selector">
            <button type="button" id="columnDropdownBtn" class="fetch-btn">Show More Columns</button>
            <div id="columnDropdownPanel" class="column-dropdown-panel">
                <?php foreach ($extra_columns as $field => $label): ?>
                    <label>
                        <input type="checkbox" data-field="<?= htmlspecialchars($field) ?>" data-label="<?= htmlspecialchars($label) ?>">
                        <?= htmlspecialchars($label) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <table>
        <tr>
            <th class="actions">Actions</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Role</th>
        </tr>

        <?php while($row = $result->fetch_assoc()): ?>
        <tr data-user-id="<?= (int)$row['id']; ?>">
            <td class="actions">
                <?php if (can_manage_target($_SESSION['role'], (int)$row['role'])): ?>
                    <a href="?edit=<?= (int)$row['id']; ?>">
                        <button class="action-btn edit">Edit</button>
                    </a>

                    <a href="?delete=<?= (int)$row['id']; ?>" onclick="return confirm('Delete this user?')">
                        <button class="action-btn delete">Delete</button>
                    </a>
                <?php else: ?>
                    <span style="color:#999;">No access</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['username']); ?></td>
            <td><?= htmlspecialchars(trim($row['fname']." "." ".$row['mname']." ".$row['lname'])); ?></td>
            <td><?= htmlspecialchars($row['email']); ?></td>
            <td><?= htmlspecialchars($row['role'] == 1 ? 'User' : ($row['role'] == 2 ? 'Staff' : ($row['role'] == 3 ? 'Manager' : 'Admin'))); ?></td>
        <?php endwhile; ?>

    </table>

</div>

<?php if ($editing && $edit_row): ?>
<div class="auth-container edit-modal" id="editModal">
    <div class="auth-card edit-card">

        <h1>Edit User</h1>

        <form method="POST" action="?edit=<?= (int)$_GET['edit']; ?>" class="profile-form">

            <div class="form-grid">

                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($edit_row['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($edit_row['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Role:</label>
                    <select name="role">
                        <option value="1" <?= $edit_row['role']==1?'selected':'' ?>>User</option>
                        <option value="2" <?= $edit_row['role']==2?'selected':'' ?>>Staff</option>
                        <option value="3" <?= $edit_row['role']==3?'selected':'' ?>>Manager</option>
                        <option value="4" <?= $edit_row['role']==4?'selected':'' ?>>Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" name="fname" value="<?= htmlspecialchars($edit_row['fname']); ?>">
                </div>

                <div class="form-group">
                    <label>Middle Name:</label>
                    <input type="text" name="mname" value="<?= htmlspecialchars($edit_row['mname']); ?>">
                </div>

                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" name="lname" value="<?= htmlspecialchars($edit_row['lname']); ?>">
                </div>

                <div class="form-group full-width">
                    <label>Address:</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($edit_row['address']); ?>">
                </div>

                <div class="form-group">
                    <label>Contact:</label>
                    <input type="text" name="contact_number" value="<?= htmlspecialchars($edit_row['contact_number']); ?>">
                </div>

                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($edit_row['department']); ?>">
                </div>

                <div class="form-group">
                    <label>Position:</label>
                    <input type="text" name="position" value="<?= htmlspecialchars($edit_row['position']); ?>">
                </div>

                <div class="form-group">
                    <label>Birthday:</label>
                    <input type="date" name="birthday" value="<?= htmlspecialchars($edit_row['birthday']); ?>">
                </div>

                <div class="form-group">
                    <label>Gender:</label>
                    <input type="text" name="gender" value="<?= htmlspecialchars($edit_row['gender']); ?>">
                </div>

                <div class="form-group">
                    <label>Civil Status:</label>
                    <input type="text" name="civil_status" value="<?= htmlspecialchars($edit_row['civil_status']); ?>">
                </div>

            </div>

            <div class="button-group">
                <button type="submit" class="save-btn">Save</button>
                <button type="button" class="cancel-btn" id="editCancelBtn">Cancel</button>
            </div>

        </form>

    </div>
</div>
<?php endif; ?>

<?php if ($editing && $edit_row): ?>
<script>
(function () {
    const modal = document.getElementById('editModal');
    if (!modal) return;

    // When this page is embedded inside the centralized dashboard,
    // #pageContent (the dashboard's content wrapper) will be present in
    // the document as an ancestor of this modal. In that case we should
    // just close the modal in place, not navigate — a real navigation to
    // /records here would hit the dashboard's own URL/context, not the
    // standalone records page, and would kick the user out of the
    // dashboard entirely. When running standalone (not embedded),
    // #pageContent won't exist, so we fall back to the original behavior
    // of navigating to a clean /records URL (clearing ?edit=... from it).
    const isEmbedded = !!document.getElementById('pageContent');

    function closeEditModal() {
        if (isEmbedded) {
            modal.remove();
        } else {
            window.location.href = '/records';
        }
    }

    const cancelBtn = document.getElementById('editCancelBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeEditModal);
    }

    // Click on dim backdrop (not on the card itself) closes the modal
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeEditModal();
        }
    });

    // Escape key closes the modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeEditModal();
        }
    });
})();
</script>
<?php endif; ?>

<script>
document.getElementById("searchInput").addEventListener("keyup", function () {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("table tr");

    rows.forEach((row, index) => {
        if (index === 0) return; // Skip header row

        let text = row.textContent.toLowerCase();

        if (text.indexOf(filter) > -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});

/* =========================================================
   "Show More Columns" — real on-demand fetch, not preloaded
   toggling. Each checkbox fetches (or removes) one column.
   ========================================================= */
(function () {
    const btn = document.getElementById('columnDropdownBtn');
    const panel = document.getElementById('columnDropdownPanel');
    const table = document.querySelector('.table-container table');

    if (!btn || !panel || !table) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (panel.classList.contains('open') && !panel.contains(e.target) && e.target !== btn) {
            panel.classList.remove('open');
        }
    });

    function addColumnToTable(field, label, dataMap) {
        const headerRow = table.rows[0];
        const th = document.createElement('th');
        th.textContent = label;
        th.dataset.dynamicCol = field;
        headerRow.appendChild(th);

        table.querySelectorAll('tr[data-user-id]').forEach(function (tr) {
            const uid = tr.getAttribute('data-user-id');
            const td = document.createElement('td');
            td.dataset.dynamicCol = field;
            const val = dataMap[uid];
            td.textContent = (val === null || val === undefined || val === '') ? '—' : val;
            tr.appendChild(td);
        });
    }

    function removeColumnFromTable(field) {
        table.querySelectorAll('[data-dynamic-col="' + field + '"]').forEach(function (el) {
            el.remove();
        });
    }

    function fetchColumn(field, label, checkboxEl) {
        checkboxEl.disabled = true;
        fetch('?ajax_column=' + encodeURIComponent(field), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    alert(data.error);
                    checkboxEl.checked = false;
                    return;
                }
                addColumnToTable(field, label, data);
            })
            .catch(function (err) {
                console.error('Failed to fetch column "' + field + '":', err);
                checkboxEl.checked = false;
            })
            .finally(function () {
                checkboxEl.disabled = false;
            });
    }

    panel.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
        cb.addEventListener('change', function () {
            const field = this.dataset.field;
            const label = this.dataset.label;
            if (this.checked) {
                fetchColumn(field, label, this);
            } else {
                removeColumnFromTable(field);
            }
        });
    });
})();
</script>

</body>
</html>
