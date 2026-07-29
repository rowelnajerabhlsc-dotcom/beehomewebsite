<?php
session_start();
include "config.php";
include "permissions.php";

/* ACCESS CONTROL */
require_role(3); // must be at least Manager to reach this page

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
   LIST DATA
   ========================================================= */
$stmt = $conn->prepare("
    SELECT
        u.id,
        u.username,
        u.email,
        u.role,
        p.batching_id,
        p.fname, p.mname, p.lname,
        p.department, p.position,
        p.contact_number
    FROM users u
    LEFT JOIN user_profiles p ON u.id = p.user_id
    ORDER BY u.id ASC
");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Records</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" href="IMAGES/logo.png">

    <style>
        .table-container {
            padding: 20px;
        }

        .search-box {
            margin-bottom: 15px;
        }

        .search-box input {
            width: 300px;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background: #f4f4f4;
        }

        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            cursor: pointer;
        }

        .edit {
            background: #4CAF50;
            color: white;
        }

        .delete {
            background: #f44336;
            color: white;
        }

        /* ===== EDIT MODAL ===== */
        .edit-modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: flex-start;     /* let card sit near top, scroll down if tall */
            padding: 40px 16px;
            background: rgba(0, 0, 0, 0.55);
            overflow-y: auto;             /* modal scrolls, not the page */
            overflow-x: hidden;
        }

        .edit-modal .auth-card {
            max-width: 700px;
            width: 100%;
            max-height: calc(100vh - 80px); /* leave breathing room top + bottom */
            overflow-y: auto;             /* card scrolls if its form is tall */
            overflow-x: hidden;            /* never scroll horizontally */
            margin: 0;
        }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="table-container">

    <h2>Employee Records</h2>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search employees...">
    </div>

    <table>
        <tr>
            <th>Batching ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Position</th>
            <th>Contact</th>
            <th>Actions</th>
        </tr>

        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['id'] ?? ''); ?></td>
            <td><?= htmlspecialchars($row['username']); ?></td>
            <td><?= htmlspecialchars(trim($row['fname']." ".$row['mname']." ".$row['lname'])); ?></td>
            <td><?= htmlspecialchars($row['email']); ?></td>
            <td><?= htmlspecialchars($row['department']); ?></td>
            <td><?= htmlspecialchars($row['position']); ?></td>
            <td><?= htmlspecialchars($row['contact_number']); ?></td>

            <td>
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
        </tr>
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
                <button type="button" class="cancel-btn" onclick="window.location.href='/records'">Cancel</button>
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

    // Click on dim backdrop (not on the card itself) closes the modal
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            window.location.href = '/records';
        }
    });

    // Escape key closes the modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.location.href = '/records';
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
</script>

</body>
</html>