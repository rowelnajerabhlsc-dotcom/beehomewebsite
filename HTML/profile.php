<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

$user_id = $_SESSION['user_id'];

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
    <title>Profile</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="auth-container">
    <div class="auth-card profile-card">

        <h1>My Profile</h1>

<?php if (isset($_GET['updated'])): ?>
    <div class="success-message">
        Profile updated successfully!
    </div>
<?php endif; ?>

<div class="profile-grid">

    <div class="profile-box full-width">
        <span>Username:</span>
        <p><?= htmlspecialchars($username); ?></p>
    </div>

    <div class="profile-box full-width">
        <span>Full Name:</span>
        <p><?= htmlspecialchars(trim("$fname $mname $lname")); ?></p>
    </div>

    <div class="profile-box full-width">
        <span>Email:</span>
        <p><?= htmlspecialchars($email); ?></p>
    </div>

    <div class="profile-box full-width">
        <span>Contact:</span>
        <p><?= htmlspecialchars($contact_number); ?></p>
    </div>

    <div class="profile-box full-width">
        <span>Address:</span>
        <p><?= htmlspecialchars($address); ?></p>
    </div>

    <div class="profile-box full-width">
        <span>Department & Position:</span>
        <p>
        <?= htmlspecialchars($department); ?> - 
        <?= htmlspecialchars($position); ?>
        </p>
    </div>

    <div class="profile-box full-width">
        <span>Birthday:</span>
        <p><?= $birthday ? htmlspecialchars(date("F d, Y", strtotime($birthday))) : 'N/A'; ?></p>
    </div>

    <div class="profile-box full-width">
        <span>Civil Status:</span>
        <p><?= htmlspecialchars($civil_status); ?></p>
    </div>

    <div class="profile-box full-width">
        <span>Gender:</span>
        <p><?= htmlspecialchars($gender); ?></p>
    </div>

</div>

<div class="button-group">
    <button onclick="window.location.href='/edit_Profile'" class="save-btn">
        Edit Profile
    </button>

    <button onclick="window.location.href='/change_password'" class="cancel-btn">
        Change Password
    </button>
</div>

<button onclick="window.location.href='/logout'" class="logout-btn">
    Logout
</button>

    </div>
</div>

</body>
</html>
