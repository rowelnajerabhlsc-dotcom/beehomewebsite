<?php
session_start();
include "config.php";
include "permissions.php";

/* ACCESS CONTROL - Require at least Manager role */
require_role(3);

/* =========================================================
   HANDLE EDIT (POST) — update user and profile, then back to list
   ========================================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['edit'])) {

    $user_id = (int) $_GET['edit'];

    $username       = $_POST['username'];
    $email          = $_POST['email'];
    $role           = $_POST['role'];
    $fname          = $_POST['fname'];
    $mname          = $_POST['mname'];
    $lname          = $_POST['lname'];
    $address        = $_POST['address'];
    $contact_number = $_POST['contact_number'];
    $department     = $_POST['department'];
    $position       = $_POST['position'];
    $birthday       = $_POST['birthday'];
    $civil_status   = $_POST['civil_status'];
    $gender         = $_POST['gender'];

    // Get current user's role and ID for permission checking
    $current_user_role = $_SESSION['role'];
    $current_user_id   = $_SESSION['user_id'];

    // Prevent users from editing themselves to a higher role than allowed
    // Managers can only assign roles 1-2 (User/Staff), not 3-4 (Manager/Admin)
    if ($current_user_role == 3 && $role >= 3) {
        $role = 2; // Force to Staff level or lower
    }

    // Prevent anyone except admins from assigning Manager/Admin roles
    if ($current_user_role < 4 && $role >= 3) {
        $role = 2; // Force to Staff level or lower
    }

    // Prevent editing of other admins/managers by managers
    if ($current_user_role == 3) {
        // Check target user's role first
        $check_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        $check_stmt->bind_result($target_role);
        $check_stmt->fetch();
        $check_stmt->close();

        // Managers cannot edit other managers or admins
        if ($target_role >= 3 && $user_id != $current_user_id) {
            header("Location: /records");
            exit();
        }
    }

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

    // Get current user info
    $current_user_id = $_SESSION['user_id'];
    $current_user_role = $_SESSION['role'];

    // Prevent self-delete
    if ($del_id === $current_user_id) {
        header("Location: /records");
        exit();
    }

    // Get target user's role to check permissions
    $target_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $target_stmt->bind_param("i", $del_id);
    $target_stmt->execute();
    $target_stmt->store_result();

    if ($target_stmt->num_rows === 0) {
        // User not found
        header("Location: /records");
        exit();
    }

    $target_stmt->bind_result($target_role);
    $target_stmt->fetch();
    $target_stmt->close();

    // Permission check based on role hierarchy
    $can_delete = false;

    // Admin can delete anyone except themselves (already checked above)
    if ($current_user_role >= 4) {
        $can_delete = true;
    }
    // Manager can only delete Users (1) and Staff (2)
    elseif ($current_user_role == 3 && $target_role < 3) {
        $can_delete = true;
    }

    if (!$can_delete) {
        // Not authorized to delete this user
        header("Location: /records");
        exit();
    }

    /* DELETE USER (CASCADE handles profile) */
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $stmt->close();

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

    // Get current user's role for permission checking
    $current_user_role = $_SESSION['role'];
    $current_user_id   = $_SESSION['user_id'];

    // Check if current user is allowed to edit this user
    $can_edit = false;

    // Admins can edit anyone
    if ($current_user_role >= 4) {
        $can_edit = true;
    }
    // Managers can only edit Users and Staff (roles < 3), or themselves
    elseif ($current_user_role == 3) {
        // Get target user's role
        $target_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $target_stmt->bind_param("i", $user_id);
        $target_stmt->execute();
        $target_stmt->store_result();
        $target_stmt->bind_result($target_role);
        $target_stmt->fetch();
        $target_stmt->close();

        // Managers can edit Users(1) and Staff(2), or their own profile
        if ($target_role < 3 || $user_id == $current_user_id) {
            $can_edit = true;
        }
    }

    if (!$can_edit) {
        // Not authorized to edit this user
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
$result = $conn->query("
    SELECT
        u.id,
        u.username,
        u.email,
        p.batching_id,
        p.fname, p.mname, p.lname,
        p.department, p.position,
        p.contact_number
    FROM users u
    LEFT JOIN user_profiles p ON u.id = p.user_id
    ORDER BY u.id ASC
");
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
            <td><?= $row['id']; ?></td>
            <td><?= htmlspecialchars($row['username']); ?></td>
            <td><?= htmlspecialchars(trim($row['fname']." ".$row['mname']." ".$row['lname'])); ?></td>
            <td><?= htmlspecialchars($row['email']); ?></td>
            <td><?= htmlspecialchars($row['department']); ?></td>
            <td><?= htmlspecialchars($row['position']); ?></td>
            <td><?= htmlspecialchars($row['contact_number']); ?></td>

            <td>
                <?php
                // Determine if current user can edit/delete this user
                $current_user_role = $_SESSION['role'];
                $current_user_id   = $_SESSION['user_id'];
                $target_id         = (int)$row['id'];
                $target_role       = null; // We don't have role in this query, need to check separately for permissions

                // For simplicity in listing, we'll show edit/delete buttons based on role
                // but actual permission checking happens in edit_user.php and delete_user.php
                $can_edit = false;
                $can_delete = false;

                // Admins can edit/delete anyone (except self-delete handled in delete script)
                if ($current_user_role >= 4) {
                    $can_edit = true;
                    $can_delete = true;
                }
                // Managers can edit/delete Users and Staff only
                elseif ($current_user_role == 3) {
                    // We'd need to check the target role here, but for simplicity in listing,
                    // we'll rely on the individual scripts to enforce permissions
                    // Show buttons but individual scripts will block unauthorized actions
                    $can_edit = true;
                    $can_delete = true;
                }
                ?>

                <?php if ($can_edit): ?>
                    <a href="?edit=<?= (int)$row['id']; ?>">
                        <button class="action-btn edit">Edit</button>
                    </a>
                <?php endif; ?>

                <?php if ($can_delete && $target_id != $current_user_id): ?>
                    <a href="?delete=<?= (int)$row['id']; ?>" onclick="return confirm('Delete this user?')">
                        <button class="action-btn delete">Delete</button>
                    </a>
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
                        <?php
                        // Only show Manager and Admin options to actual Admins (role 4)
                        $current_user_role = $_SESSION['role'];
                        if ($current_user_role == 4): ?>
                            <option value="3" <?= $edit_row['role']==3?'selected':'' ?>>Manager</option>
                            <option value="4" <?= $edit_row['role']==4?'selected':'' ?>>Admin</option>
                        <?php endif; ?>
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