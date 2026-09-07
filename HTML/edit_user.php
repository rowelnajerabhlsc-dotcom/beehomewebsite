<?php
session_start();
include "config.php";
include "permissions.php";

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
    $contact_number = $_POST['contact_number'];
    $department = $_POST['department'];
    $position = $_POST['position'];
    $birthday = $_POST['birthday'];
    $civil_status = $_POST['civil_status'];
    $gender = $_POST['gender'];

    /* NEW: Profile fields */
    $tin_no = $_POST['tin_no'];
    $sss_no = $_POST['sss_no'];
    $blood_type = $_POST['blood_type'];
    $pagibig_no = $_POST['pagibig_no'];
    $philhealth_no = $_POST['philhealth_no'];
    $religion = $_POST['religion'];
    $pmes_orientation_date = $_POST['pmes_orientation_date'];
    $facebook_account = $_POST['facebook_account'];
    $emergency_name = $_POST['emergency_name'];
    $emergency_address = $_POST['emergency_address'];
    $emergency_relationship = $_POST['emergency_relationship'];
    $emergency_contact_no = $_POST['emergency_contact_no'];

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
            tin_no=?, sss_no=?, blood_type=?, pagibig_no=?, philhealth_no=?,
            religion=?, pmes_orientation_date=?, facebook_account=?,
            emergency_name=?, emergency_address=?, emergency_relationship=?, emergency_contact_no=?,
            department=?, position=?, 
            birthday=?, civil_status=?, gender=?
        WHERE user_id=?
    ");

    $stmt->bind_param(
        "sssssssssssssssssssssii",
        $fname, $mname, $lname,
        $address, $contact_number,
        $tin_no, $sss_no, $blood_type, $pagibig_no, $philhealth_no,
        $religion, $pmes_orientation_date, $facebook_account,
        $emergency_name, $emergency_address, $emergency_relationship, $emergency_contact_no,
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
        p.tin_no, p.sss_no, p.blood_type, p.pagibig_no, p.philhealth_no,
        p.religion, p.pmes_orientation_date, p.facebook_account,
        p.emergency_name, p.emergency_address, p.emergency_relationship, p.emergency_contact_no,
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
    $tin_no, $sss_no, $blood_type, $pagibig_no, $philhealth_no,
    $religion, $pmes_orientation_date, $facebook_account,
    $emergency_name, $emergency_address, $emergency_relationship, $emergency_contact_no,
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

        <div class="form-group">
            <label>TIN No.:</label>
            <input type="text" name="tin_no" value="<?= htmlspecialchars($tin_no ?? ''); ?>" inputmode="numeric" maxlength="13" placeholder="123-456-789-000">
        </div>

        <div class="form-group">
            <label>SSS No.:</label>
            <input type="text" name="sss_no" value="<?= htmlspecialchars($sss_no ?? ''); ?>" inputmode="numeric" maxlength="11" placeholder="12-1234567-8">
        </div>

        <div class="form-group">
            <label>Blood Type:</label>
            <select name="blood_type">
                <option value="">Select</option>
                <option value="A+" <?= $blood_type == 'A+' ? 'selected' : '' ?>>A+</option>
                <option value="A-" <?= $blood_type == 'A-' ? 'selected' : '' ?>>A-</option>
                <option value="B+" <?= $blood_type == 'B+' ? 'selected' : '' ?>>B+</option>
                <option value="B-" <?= $blood_type == 'B-' ? 'selected' : '' ?>>B-</option>
                <option value="AB+" <?= $blood_type == 'AB+' ? 'selected' : '' ?>>AB+</option>
                <option value="AB-" <?= $blood_type == 'AB-' ? 'selected' : '' ?>>AB-</option>
                <option value="O+" <?= $blood_type == 'O+' ? 'selected' : '' ?>>O+</option>
                <option value="O-" <?= $blood_type == 'O-' ? 'selected' : '' ?>>O-</option>
            </select>
        </div>

        <div class="form-group">
            <label>Pag-IBIG No.:</label>
            <input type="text" name="pagibig_no" value="<?= htmlspecialchars($pagibig_no ?? ''); ?>" inputmode="numeric" maxlength="13" placeholder="1234-1234-1234">
        </div>

        <div class="form-group">
            <label>PhilHealth No.:</label>
            <input type="text" name="philhealth_no" value="<?= htmlspecialchars($philhealth_no ?? ''); ?>" inputmode="numeric" maxlength="15" placeholder="12-345678901-2">
        </div>

        <div class="form-group">
            <label>Religion:</label>
            <input type="text" name="religion" value="<?= htmlspecialchars($religion ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>PMES Orientation Date:</label>
            <input type="date" name="pmes_orientation_date" value="<?= htmlspecialchars($pmes_orientation_date ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Facebook Account:</label>
            <input type="text" name="facebook_account" value="<?= htmlspecialchars($facebook_account ?? ''); ?>">
        </div>

        <hr class="my-4">

        <div class="form-group">
            <label>Emergency Name:</label>
            <input type="text" name="emergency_name" value="<?= htmlspecialchars($emergency_name ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Emergency Relationship:</label>
            <input type="text" name="emergency_relationship" value="<?= htmlspecialchars($emergency_relationship ?? ''); ?>">
        </div>

        <div class="form-group full-width">
            <label>Emergency Address:</label>
            <input type="text" name="emergency_address" value="<?= htmlspecialchars($emergency_address ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Emergency Contact No.:</label>
            <input type="text" name="emergency_contact_no" value="<?= htmlspecialchars($emergency_contact_no ?? ''); ?>" inputmode="numeric" maxlength="15" placeholder="0917-123-4567">
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