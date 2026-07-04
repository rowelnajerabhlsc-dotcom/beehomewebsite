<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   UPDATE DATA
========================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $email = $_POST['email'];
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

    /* ✅ Ensure profile exists */
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

    /* ✅ Update USERS table */
    $stmt = $conn->prepare("
        UPDATE users SET 
            username=?, email=?
        WHERE id=?
    ");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();
    $stmt->close();

    /* ✅ Update PROFILE table */
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

    header("Location: profile.php?updated=1");
    exit();
}

/* =========================
   FETCH DATA
========================= */
$stmt = $conn->prepare("
    SELECT 
        u.username, u.email,
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
    $username, $email, $fname, $mname, $lname,
    $address, $contact_number, $department, $position,
    $birthday, $civil_status, $gender
);

$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="auth-container">
    <div class="auth-card edit-card">

        <h1>Edit Profile</h1>

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
            <label>First Name:</label>
            <input type="text" name="fname" value="<?= htmlspecialchars($fname); ?>" required>
        </div>

        <div class="form-group">
            <label>Middle Name:</label>
            <input type="text" name="mname" value="<?= htmlspecialchars($mname); ?>">
        </div>

        <div class="form-group">
            <label>Last Name:</label>
            <input type="text" name="lname" value="<?= htmlspecialchars($lname); ?>" required>
        </div>

        <div class="form-group">
            <label>Contact Number:</label>
            <input type="text" name="contact_number" value="<?= htmlspecialchars($contact_number); ?>">
        </div>

        <div class="form-group full-width">
            <label>Address:</label>
            <input type="text" name="address" value="<?= htmlspecialchars($address); ?>">
        </div>

        <div class="form-group">
            <label>Department:</label>
            <select id="department" name="department" required onchange="updatePositions()">
                <option value="">Select Department</option>

                <option value="BOD" <?= $department=='BOD'?'selected':'' ?>>Board of Directors</option>
                <option value="Audit" <?= $department=='Audit'?'selected':'' ?>>Audit Committee</option>
                <option value="Election" <?= $department=='Election'?'selected':'' ?>>Election Committee</option>
                <option value="MedCon" <?= $department=='MedCon'?'selected':'' ?>>Med-Con Committee</option>
                <option value="Ethics" <?= $department=='Ethics'?'selected':'' ?>>Ethics Committee</option>
                <option value="Educ" <?= $department=='Educ'?'selected':'' ?>>Education & Training</option>
                <option value="GAD" <?= $department=='GAD'?'selected':'' ?>>GAD Committee</option>
                <option value="Credit" <?= $department=='Credit'?'selected':'' ?>>Credit Committee</option>
                <option value="Business" <?= $department=='Business'?'selected':'' ?>>Other Business</option>
                <option value="Staff" <?= $department=='Staff'?'selected':'' ?>>Management Staff</option>
            </select> 
        </div>

        <div class="form-group">
            <label>Position:</label>
            <select id="position" name="position" required></select> 
        </div>

        <div class="form-group">
            <label>Birthday:</label>
            <input type="date" name="birthday" value="<?= $birthday; ?>">
        </div>

        <div class="form-group">
            <label>Gender:</label>
            <select name="gender">
                <option value="">Select</option>
                <option <?= $gender=='Male'?'selected':'' ?>>Male</option>
                <option <?= $gender=='Female'?'selected':'' ?>>Female</option>
            </select>
        </div>

        <div class="form-group">
            <label>Civil Status:</label>
            <select name="civil_status">
                <option value="">Select</option>
                <option <?= $civil_status=='Single'?'selected':'' ?>>Single</option>
                <option <?= $civil_status=='Married'?'selected':'' ?>>Married</option>
                <option <?= $civil_status=='Widowed'?'selected':'' ?>>Widowed</option>
            </select>
        </div>

    </div>

    <div class="button-group">
        <button type="submit" class="save-btn">Save Changes</button>
        <button type="button" class="cancel-btn" onclick="window.location.href='profile.php'">
            Cancel
        </button>
    </div>

</form>

    </div>
</div>

<script>
function updatePositions(selectedPosition = "") {
    const dept = document.getElementById("department").value;
    const position = document.getElementById("position");

    let options = [];

    if (dept === "BOD") {
        options = ["Chairperson", "Vice Chairperson", "Board Director", "Secretary", "Treasurer"];
    } else if (["Audit","Election","MedCon","Ethics","Educ","GAD","Credit","Business"].includes(dept)) {
        options = ["Chairperson", "Vice Chairperson", "Secretary"];
    } else if (dept === "Staff") {
        options = ["Staff", "Officer"];
    }

    let html = `<option value="">Select Position</option>`;

    options.forEach(opt => {
        let selected = (opt === selectedPosition) ? "selected" : "";
        html += `<option ${selected}>${opt}</option>`;
    });

    position.innerHTML = html;
}

window.onload = function() {
    updatePositions("<?= $position ?>");
};
</script>

</body>
</html>