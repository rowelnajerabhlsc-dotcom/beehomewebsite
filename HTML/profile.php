<?php
session_start();

// =====================================================================
// DEVMODE — frontend-only preview, no session/DB required.
// Access via: profile.php?devmode=true
// ⚠️ REMOVE THIS BLOCK (or hardcode DEVMODE_ENABLED = false) before
// deploying to production — it bypasses login entirely.
// =====================================================================
define('DEVMODE_ENABLED', false); // set to false to disable devmode outright
$devmode = DEVMODE_ENABLED && isset($_GET['devmode']) && $_GET['devmode'] === 'true';

if ($devmode) {
    // ---- Dummy data standing in for the DB row ----
    $username            = 'juan.delacruz';
    $email                = 'juan.delacruz@example.com';
    $fname                = 'Juan';
    $mname                = 'Santos';
    $lname                = 'Dela Cruz';
    $address              = '123 Sampaguita St., Barangay San Isidro, Quezon City';
    $contact_number       = '0917-123-4567';
    $department           = 'Labor & Manpower';
    $position             = 'Field Coordinator';
    $client_assignment    = 'ABC Manufacturing Corp.';
    $birthday             = '1995-06-14';
    $civil_status         = 'Married';
    $no_of_dependents     = 2;
    $gender               = 'M';
    $height_cm            = 170.5;
    $weight_kg            = 68.2;
    $tin_no               = '123-456-789-000';
    $sss_no               = '34-1234567-8';
    $blood_type           = 'O+';
    $pagibig_no           = '1234-5678-9012';
    $philhealth_no        = '12-345678901-2';
    $religion             = 'Roman Catholic';
    $pmes_orientation_date = '2023-03-10';
    $facebook_account     = 'fb.com/juan.delacruz';
    $education_json       = json_encode([
        ['school' => 'Sampaguita Elementary School', 'year_graduated' => '2007', 'course' => ''],
        ['school' => 'Quezon City High School', 'year_graduated' => '2011', 'course' => ''],
        ['school' => 'Polytechnic University of the Philippines', 'year_graduated' => '2015', 'course' => 'BS Business Administration'],
        ['school' => '', 'year_graduated' => '', 'course' => ''],
    ]);
    $emergency_name         = 'Maria Dela Cruz';
    $emergency_address      = '123 Sampaguita St., Barangay San Isidro, Quezon City';
    $emergency_relationship = 'Spouse';
    $emergency_contact_no   = '0917-765-4321';
} else {
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
            p.department, p.position, p.client_assignment,
            p.birthday, p.civil_status, p.no_of_dependents, p.gender,
            p.height_cm, p.weight_kg,
            p.tin_no, p.sss_no, p.blood_type, p.pagibig_no, p.philhealth_no,
            p.religion, p.pmes_orientation_date,
            p.facebook_account,
            p.education_json,
            p.emergency_name, p.emergency_address, p.emergency_relationship, p.emergency_contact_no
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $stmt->bind_result(
        $username, $email, $fname, $mname, $lname,
        $address, $contact_number,
        $department, $position, $client_assignment,
        $birthday, $civil_status, $no_of_dependents, $gender,
        $height_cm, $weight_kg,
        $tin_no, $sss_no, $blood_type, $pagibig_no, $philhealth_no,
        $religion, $pmes_orientation_date,
        $facebook_account,
        $education_json,
        $emergency_name, $emergency_address, $emergency_relationship, $emergency_contact_no
    );

    $stmt->fetch();
    $stmt->close();
}

// Auto-calculate age from birthday
$age = null;
if (!empty($birthday)) {
    $bday = new DateTime($birthday);
    $today = new DateTime('today');
    $age = $bday->diff($today)->y;
}

// Decode education rows (always render 4 rows, even if empty)
$education_rows = [];
if (!empty($education_json)) {
    $decoded = json_decode($education_json, true);
    if (is_array($decoded)) {
        $education_rows = $decoded;
    }
}
for ($i = count($education_rows); $i < 4; $i++) {
    $education_rows[] = ['school' => '', 'year_graduated' => '', 'course' => ''];
}

function show($val) {
    $val = trim((string) $val);
    return $val !== '' ? htmlspecialchars($val) : 'N/A';
}
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

<?php if (!$devmode) include "navbar.php"; ?>
<?php if ($devmode): ?>
    <div style="background:#F5C233; color:#1e2b22; text-align:center; padding:8px; font-weight:700; font-size:13px;">
        ⚠ DEVMODE PREVIEW — dummy data, no backend/session/navbar. Remove ?devmode=true for the real page.
    </div>
<?php endif; ?>

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
        <p><?= show($username); ?></p>
    </div>

    <div class="profile-box full-width">
        <span>Full Name:</span>
        <p><?= show(trim("$fname $mname $lname")); ?></p>
    </div>

    <div class="profile-box full-width">
        <span>Email / Facebook Account:</span>
        <p><?= show($email); ?><?= $facebook_account ? ' / ' . show($facebook_account) : ''; ?></p>
    </div>

    <div class="profile-box">
        <span>Contact Number:</span>
        <p><?= show($contact_number); ?></p>
    </div>

    <div class="profile-box">
        <span>Address:</span>
        <p><?= show($address); ?></p>
    </div>

    <div class="profile-box">
        <span>Birthday:</span>
        <p><?= $birthday ? htmlspecialchars(date("F d, Y", strtotime($birthday))) : 'N/A'; ?></p>
    </div>

    <div class="profile-box">
        <span>Age:</span>
        <p><?= $age !== null ? htmlspecialchars($age) : 'N/A'; ?></p>
    </div>

    <div class="profile-box">
        <span>Civil Status:</span>
        <p><?= show($civil_status); ?></p>
    </div>

    <?php if (strtolower((string) $civil_status) === 'married'): ?>
    <div class="profile-box">
        <span>No. of Dependents:</span>
        <p><?= show($no_of_dependents); ?></p>
    </div>
    <?php endif; ?>

    <div class="profile-box">
        <span>Gender:</span>
        <p><?= show($gender); ?></p>
    </div>

    <div class="profile-box">
        <span>Height / Weight:</span>
        <p><?= show($height_cm); ?> cm / <?= show($weight_kg); ?> kg</p>
    </div>

    <div class="profile-box">
        <span>Blood Type:</span>
        <p><?= show($blood_type); ?></p>
    </div>

    <div class="profile-box">
        <span>Religion:</span>
        <p><?= show($religion); ?></p>
    </div>

    <div class="profile-box">
        <span>TIN No.:</span>
        <p><?= show($tin_no); ?></p>
    </div>

    <div class="profile-box">
        <span>SSS No.:</span>
        <p><?= show($sss_no); ?></p>
    </div>

    <div class="profile-box">
        <span>Pag-IBIG No.:</span>
        <p><?= show($pagibig_no); ?></p>
    </div>

    <div class="profile-box">
        <span>PhilHealth No.:</span>
        <p><?= show($philhealth_no); ?></p>
    </div>

    <div class="profile-box">
        <span>PMES Orientation Date:</span>
        <p><?= $pmes_orientation_date ? htmlspecialchars(date("F d, Y", strtotime($pmes_orientation_date))) : 'N/A'; ?></p>
    </div>

    <div class="profile-box">
        <span>Position:</span>
        <p><?= show($position); ?></p>
    </div>

    <div class="profile-box">
        <span>Department:</span>
        <p><?= show($department); ?></p>
    </div>

    <div class="profile-box full-width">
        <span>Client Assignment:</span>
        <p><?= show($client_assignment); ?></p>
    </div>

</div>

<h2 class="section-title">Educational Attainment</h2>
<div class="education-table">
    <div class="education-row education-header">
        <div>School</div>
        <div>Year Graduated</div>
        <div>Course</div>
    </div>
    <?php foreach ($education_rows as $row): ?>
    <div class="education-row">
        <div><?= show($row['school'] ?? ''); ?></div>
        <div><?= show($row['year_graduated'] ?? ''); ?></div>
        <div><?= show($row['course'] ?? ''); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="emergency-box">
    <h2 class="section-title">In Case of Emergency, Notify:</h2>
    <div class="profile-grid">
        <div class="profile-box">
            <span>Name:</span>
            <p><?= show($emergency_name); ?></p>
        </div>
        <div class="profile-box">
            <span>Relationship:</span>
            <p><?= show($emergency_relationship); ?></p>
        </div>
        <div class="profile-box full-width">
            <span>Address:</span>
            <p><?= show($emergency_address); ?></p>
        </div>
        <div class="profile-box full-width">
            <span>Contact No.:</span>
            <p><?= show($emergency_contact_no); ?></p>
        </div>
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
