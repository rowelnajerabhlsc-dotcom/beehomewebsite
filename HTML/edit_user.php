<?php
session_start();
include "config.php";
include "permissions.php";

// TEMP DEBUG - remove after testing
echo "Session role: " . var_export($_SESSION['role'] ?? 'not set', true) . "<br>";
echo "Target user_id: " . var_export($_GET['id'] ?? 'not set', true) . "<br>";

/* ACCESS CONTROL */
require_role(3); // must be at least Manager to reach this page

/* GET USER ID */
if (!isset($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$user_id = $_GET['id'];

/* ROLE-BASED TARGET CHECK */
$target_role = get_user_role_by_id($conn, $user_id);

if ($target_role === null) {
    header("Location: records.php");
    exit();
}

if (!can_manage_target($_SESSION['role'], $target_role)) {
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
                <option value="3" <?= $role==3?'selected':'' ?>>Manager</option>
                <option value="4" <?= $role==4?'selected':'' ?>>Admin</option>
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