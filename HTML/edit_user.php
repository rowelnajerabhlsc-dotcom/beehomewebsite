<?php
session_start();
include "config.php";
include "permissions.php";

/* ACCESS CONTROL - Require at least Manager role */
require_role(3);

/* Additional check: Managers (role 3) can only manage Users (1) and Staff (2), not other Managers or Admins */
$user_role = $_SESSION['role'];
if ($user_role == 3) {
    // Managers can only edit users with role < 3 (User or Staff)
    // This check will be enhanced when we load the user data below
}

/* GET USER ID */
if (!isset($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$user_id = $_GET['id'];

/* Fetch user data to check if manager is trying to edit another manager/admin */
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // User not found
    header("Location: records.php");
    exit();
}

$stmt->bind_result($target_role);
$stmt->fetch();
$stmt->close();

// Additional permission check for managers
if ($user_role == 3 && $target_role >= 3) {
    // Managers cannot edit other managers or admins
    header("Location: records.php");
    exit();
}

/* =========================
   UPDATE DATA
   ========================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Additional validation: Managers cannot assign role >= 3 (Manager or Admin)
    if ($user_role == 3 && $role >= 3) {
        // Prevent managers from promoting users to manager or admin
        $role = 2; // Force to staff level or lower
    }

    $fname = $_POST['fname'];
    $mname = $_POST['mname'];
    $lname = $_POST['lname'];
    $address = $_POST['address'];
    $department = $_POST['department'];
    $position = $_POST['position'];
    $contact_number = $_POST['contact_number'];
    $birthday = $_POST['birthday'];
    $civil_status = $_POST['civil_status'];
    $gender = $_POST['gender'];

    /* Ensure profile exists */
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
    $stmt = $conn->prepare("
        UPDATE users SET
            username=?, email=?, role=?
        WHERE id=?
    ");
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

    header("Location: records.php");
    exit();
}

/* =========================
   FETCH DATA
   ========================= */
$stmt = $conn->prepare("
    SELECT
        u.username, u.email, u.role,
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
    $username, $email, $role,
    $fname, $mname, $lname,
    $address, $contact_number,
    $department, $position,
    $birthday, $civil_status, $gender
);

$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="auth-container">
    <div class="auth-card edit-card">

        <h1>Edit User</h1>

        <form method="POST" class="profile-form">

            <div class="form-grid">

                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($username); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                    <label>Role:</label>
                    <select name="role">
                        <option value="1" <?= $role==1?'selected':'' ?>>User</option>
                        <option value="2" <?= $role==2?'selected':'' ?>>Staff</option>
                        <?php
                        // Only show Manager and Admin options to actual Admins (role 4)
                        if ($user_role == 4): ?>
                            <option value="3" <?= $role==3?'selected':'' ?>>Manager</option>
                            <option value="4" <?= $role==4?'selected':'' ?>>Admin</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" name="fname" value="<?= htmlspecialchars($fname); ?>">
                </div>

                <div class="form-group">
                    <label>Middle Name:</label>
                    <input type="text" name="mname" value="<?= htmlspecialchars($mname); ?>">
                </div>

                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" name="lname" value="<?= htmlspecialchars($lname); ?>">
                </div>

                <div class="form-group full-width">
                    <label>Address:</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($address); ?>">
                </div>

                <div class="form-group">
                    <label>Contact:</label>
                    <input type="text" name="contact_number" value="<?= htmlspecialchars($contact_number); ?>">
                </div>

                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($department); ?>">
                </div>

                <div class="form-group">
                    <label>Position:</label>
                    <input type="text" name="position" value="<?= htmlspecialchars($position); ?>">
                </div>

                <div class="form-group">
                    <label>Birthday:</label>
                    <input type="date" name="birthday" value="<?= $birthday; ?>">
                </div>

                <div class="form-group">
                    <label>Gender:</label>
                    <input type="text" name="gender" value="<?= htmlspecialchars($gender); ?>">
                </div>

                <div class="form-group">
                    <label>Civil Status:</label>
                    <input type="text" name="civil_status" value="<?= htmlspecialchars($civil_status); ?>">
                </div>

            </div>

            <div class="button-group">
                <button type="submit" class="save-btn">Save</button>
                <button type="button" class="cancel-btn" onclick="window.location.href='records.php'">Cancel</button>
            </div>

        </form>

    </div>
</div>

</body>
</html>