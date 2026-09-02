<?php
session_start();

// =====================================================================
// DEVMODE — frontend-only preview, no session/DB required.
// Access via: profile.php?devmode=true
// ⚠️ REMOVE THIS BLOCK (or hardcode DEVMODE_ENABLED = false) before
// deploying to production — it bypasses login entirely.
// =====================================================================
define('DEVMODE_ENABLED', true); // set to false to disable devmode outright
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

<div class="pv-shell">

<?php if (isset($_GET['updated'])): ?>
    <div class="success-message">
        Profile updated successfully!
    </div>
<?php endif; ?>

    <!-- ===== HEADER CARD ===== -->
    <div class="pv-header">
        <div class="pv-header-left">
            <div class="pv-avatar">
                <?php
                    $initials = strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1));
                    echo htmlspecialchars($initials ?: '?');
                ?>
            </div>
            <div class="pv-header-info">
                <h1><?= show(trim("$fname $mname $lname")); ?></h1>
                <div class="pv-header-meta">
                    <span class="pv-pill"><?= show($position); ?></span>
                    <span class="pv-pill pv-pill-muted"><?= show($department); ?></span>
                </div>
                <div class="pv-header-sub">
                    <?= $birthday ? htmlspecialchars(date("F d, Y", strtotime($birthday))) : 'Birthday N/A'; ?>
                    <?= $age !== null ? ' &middot; ' . htmlspecialchars($age) . ' years old' : ''; ?>
                    &middot; <?= show($gender === 'M' ? 'Male' : ($gender === 'F' ? 'Female' : $gender)); ?>
                </div>
            </div>
        </div>
        <div class="pv-header-actions">
            <button onclick="window.location.href='/edit_Profile'" class="pv-btn pv-btn-primary">
                Edit Profile
            </button>
            <button onclick="window.location.href='/change_password'" class="pv-btn pv-btn-ghost">
                Change Password
            </button>
        </div>
    </div>

    <!-- ===== ACCOUNT & CONTACT ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Account &amp; Contact</h2>
        </div>
        <div class="pv-field-grid">
            <div class="pv-field">
                <label>Username</label>
                <div class="pv-value"><?= show($username); ?></div>
            </div>
            <div class="pv-field">
                <label>Email / Facebook Account</label>
                <div class="pv-value"><?= show($email); ?><?= $facebook_account ? ' / ' . show($facebook_account) : ''; ?></div>
            </div>
            <div class="pv-field">
                <label>Contact Number</label>
                <div class="pv-value"><?= show($contact_number); ?></div>
            </div>
            <div class="pv-field pv-field-wide">
                <label>Address</label>
                <div class="pv-value"><?= show($address); ?></div>
            </div>
        </div>
    </div>

    <!-- ===== PERSONAL DETAILS ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Personal Details</h2>
        </div>
        <div class="pv-field-grid">
            <div class="pv-field">
                <label>Civil Status</label>
                <div class="pv-value"><?= show($civil_status); ?></div>
            </div>
            <?php if (strtolower((string) $civil_status) === 'married'): ?>
            <div class="pv-field">
                <label>No. of Dependents</label>
                <div class="pv-value"><?= show($no_of_dependents); ?></div>
            </div>
            <?php endif; ?>
            <div class="pv-field">
                <label>Height / Weight</label>
                <div class="pv-value"><?= show($height_cm); ?> cm / <?= show($weight_kg); ?> kg</div>
            </div>
            <div class="pv-field">
                <label>Blood Type</label>
                <div class="pv-value"><?= show($blood_type); ?></div>
            </div>
            <div class="pv-field">
                <label>Religion</label>
                <div class="pv-value"><?= show($religion); ?></div>
            </div>
        </div>
    </div>

    <!-- ===== GOVERNMENT IDs ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Government IDs</h2>
        </div>
        <div class="pv-field-grid">
            <div class="pv-field">
                <label>TIN No.</label>
                <div class="pv-value"><?= show($tin_no); ?></div>
            </div>
            <div class="pv-field">
                <label>SSS No.</label>
                <div class="pv-value"><?= show($sss_no); ?></div>
            </div>
            <div class="pv-field">
                <label>Pag-IBIG No.</label>
                <div class="pv-value"><?= show($pagibig_no); ?></div>
            </div>
            <div class="pv-field">
                <label>PhilHealth No.</label>
                <div class="pv-value"><?= show($philhealth_no); ?></div>
            </div>
        </div>
    </div>

    <!-- ===== EMPLOYMENT ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Employment</h2>
        </div>
        <div class="pv-field-grid">
            <div class="pv-field">
                <label>Position</label>
                <div class="pv-value"><?= show($position); ?></div>
            </div>
            <div class="pv-field">
                <label>Department</label>
                <div class="pv-value"><?= show($department); ?></div>
            </div>
            <div class="pv-field">
                <label>PMES Orientation Date</label>
                <div class="pv-value"><?= $pmes_orientation_date ? htmlspecialchars(date("F d, Y", strtotime($pmes_orientation_date))) : 'N/A'; ?></div>
            </div>
            <div class="pv-field pv-field-wide">
                <label>Client Assignment</label>
                <div class="pv-value"><?= show($client_assignment); ?></div>
            </div>
        </div>
    </div>

    <!-- ===== EDUCATIONAL ATTAINMENT ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Educational Attainment</h2>
        </div>
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
    </div>

    <!-- ===== EMERGENCY CONTACT ===== -->
    <div class="pv-card pv-card-emergency">
        <div class="pv-card-header">
            <span class="pv-accent pv-accent-gold"></span>
            <h2>In Case of Emergency, Notify</h2>
        </div>
        <div class="pv-field-grid">
            <div class="pv-field">
                <label>Name</label>
                <div class="pv-value"><?= show($emergency_name); ?></div>
            </div>
            <div class="pv-field">
                <label>Relationship</label>
                <div class="pv-value"><?= show($emergency_relationship); ?></div>
            </div>
            <div class="pv-field pv-field-wide">
                <label>Address</label>
                <div class="pv-value"><?= show($emergency_address); ?></div>
            </div>
            <div class="pv-field pv-field-wide">
                <label>Contact No.</label>
                <div class="pv-value"><?= show($emergency_contact_no); ?></div>
            </div>
        </div>
    </div>

    <button onclick="window.location.href='/logout'" class="pv-logout">
        Logout
    </button>

</div>

</body>
</html>
