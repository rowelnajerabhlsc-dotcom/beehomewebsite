<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];

// ---- Handle Save ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {

    $fname               = trim($_POST['fname'] ?? '');
    $mname               = trim($_POST['mname'] ?? '');
    $lname               = trim($_POST['lname'] ?? '');
    $address             = trim($_POST['address'] ?? '');
    $contact_number      = trim($_POST['contact_number'] ?? '');
    $birthday            = trim($_POST['birthday'] ?? '');
    $civil_status        = trim($_POST['civil_status'] ?? '');
    $no_of_dependents    = $civil_status === 'Married' ? (int) ($_POST['no_of_dependents'] ?? 0) : null;
    $gender              = trim($_POST['gender'] ?? '');
    $height_cm           = trim($_POST['height_cm'] ?? '');
    $weight_kg           = trim($_POST['weight_kg'] ?? '');
    $tin_no              = trim($_POST['tin_no'] ?? '');
    $sss_no              = trim($_POST['sss_no'] ?? '');
    $blood_type          = trim($_POST['blood_type'] ?? '');
    $pagibig_no          = trim($_POST['pagibig_no'] ?? '');
    $philhealth_no       = trim($_POST['philhealth_no'] ?? '');
    $religion            = trim($_POST['religion'] ?? '');
    $pmes_orientation_date = trim($_POST['pmes_orientation_date'] ?? '');
    $position            = trim($_POST['position'] ?? '');
    $client_id           = (int) ($_POST['client_id'] ?? 0);
    $client_id           = $client_id > 0 ? $client_id : null;
    $facebook_account    = trim($_POST['facebook_account'] ?? '');
    $emergency_name         = trim($_POST['emergency_name'] ?? '');
    $emergency_address      = trim($_POST['emergency_address'] ?? '');
    $emergency_relationship = trim($_POST['emergency_relationship'] ?? '');
    $emergency_contact_no   = trim($_POST['emergency_contact_no'] ?? '');

    $education_rows = [];
    for ($i = 0; $i < 4; $i++) {
        $education_rows[] = [
            'school'         => trim($_POST['edu_school'][$i] ?? ''),
            'year_graduated' => trim($_POST['edu_year'][$i] ?? ''),
            'course'         => trim($_POST['edu_course'][$i] ?? ''),
        ];
    }
    $education_json = json_encode($education_rows);

    // ---- Required-field validation: allow save but show warnings for important fields ----
    // Critical fields: if empty, save proceeds but warnings are shown
    $critical = [
        'fname', 'lname', 'address', 'contact_number', 'birthday',
        'gender', 'height_cm', 'weight_kg',
        'tin_no', 'sss_no', 'blood_type',
        'pagibig_no', 'philhealth_no', 'religion',
        'emergency_name', 'emergency_address', 'emergency_relationship', 'emergency_contact_no'
    ];
    $warnings = [];
    $hasCriticalMissing = false;

    foreach ($critical as $label) {
        $val = $$label ?? '';
        if ($val === '') {
            $hasCriticalMissing = true;
            $warnings[] = ucwords(str_replace('_', ' ', $label)) . " is important and should be filled in.";
        }
    }

    // Check client assignment
    if ($client_id === null) {
        $hasCriticalMissing = true;
        $warnings[] = "Client Assignment is important and should be selected.";
    }

    // Check dependents if married
    if ($civil_status === 'Married' && ($no_of_dependents ?? '') === '') {
        $hasCriticalMissing = true;
        $warnings[] = "No. of Dependents is required when Civil Status is Married.";
    }

    // Additional format validation (must match the masked pattern exactly) ----
    $id_formats = [
        'tin_no'        => ['pattern' => '/^\d{3}-\d{3}-\d{3}-\d{3}$/', 'label' => 'TIN No.', 'example' => '123-456-789-101'],
        'sss_no'        => ['pattern' => '/^\d{2}-\d{7}-\d{1}$/',       'label' => 'SSS No.', 'example' => '12-1234567-1'],
        'pagibig_no'    => ['pattern' => '/^\d{4}-\d{4}-\d{4}$/',       'label' => 'Pag-IBIG No.', 'example' => '1234-1234-1234'],
        'philhealth_no' => ['pattern' => '/^\d{2}-\d{9}-\d{1}$/',       'label' => 'PhilHealth No.', 'example' => '12-123456789-1'],
    ];
    foreach ($id_formats as $field => $rule) {
        if ($$field !== '' && !preg_match($rule['pattern'], $$field)) {
            $errors[] = $rule['label'] . " must be in the format " . $rule['example'] . ".";
        }
    }

    foreach ($education_rows as $i => $row) {
        if ($row['school'] === '' || $row['year_graduated'] === '' || $row['course'] === '') {
            $errors[] = "Educational Attainment row " . ($i + 1) . " is incomplete.";
        }
    }

    // Proceed with save if there are no format/education errors.
    // Warnings (important fields empty) allow save but show messages to user.
    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE user_profiles SET
                fname = ?, mname = ?, lname = ?,
                address = ?, contact_number = ?,
                birthday = ?, civil_status = ?, no_of_dependents = ?, gender = ?,
                height_cm = ?, weight_kg = ?,
                tin_no = ?, sss_no = ?, blood_type = ?, pagibig_no = ?, philhealth_no = ?,
                religion = ?, pmes_orientation_date = ?,
                position = ?, client_id = ?, facebook_account = ?,
                education_json = ?,
                emergency_name = ?, emergency_address = ?, emergency_relationship = ?, emergency_contact_no = ?
            WHERE user_id = ?
        ");

        $stmt->bind_param(
            "sssssssisssssssssssissssssi",
            $fname, $mname, $lname,
            $address, $contact_number,
            $birthday, $civil_status, $no_of_dependents, $gender,
            $height_cm, $weight_kg,
            $tin_no, $sss_no, $blood_type, $pagibig_no, $philhealth_no,
            $religion, $pmes_orientation_date,
            $position, $client_id, $facebook_account,
            $education_json,
            $emergency_name, $emergency_address, $emergency_relationship, $emergency_contact_no,
            $user_id
        );

        $stmt->execute();
        $stmt->close();

        header("Location: /profile?updated=1");
        exit();
    }
}

// ---- Load existing values for prefill (draft cache takes over client-side via JS) ----
$stmt = $conn->prepare("
    SELECT
        fname, mname, lname, address, contact_number,
        birthday, civil_status, no_of_dependents, gender,
        height_cm, weight_kg,
        tin_no, sss_no, blood_type, pagibig_no, philhealth_no,
        religion, pmes_orientation_date,
        position, client_id, facebook_account,
        education_json,
        emergency_name, emergency_address, emergency_relationship, emergency_contact_no,
        profile_photo_url
    FROM user_profiles WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result(
    $fname, $mname, $lname, $address, $contact_number,
    $birthday, $civil_status, $no_of_dependents, $gender,
    $height_cm, $weight_kg,
    $tin_no, $sss_no, $blood_type, $pagibig_no, $philhealth_no,
    $religion, $pmes_orientation_date,
    $position, $client_id, $facebook_account,
    $education_json,
    $emergency_name, $emergency_address, $emergency_relationship, $emergency_contact_no,
    $profile_photo_url
);
$stmt->fetch();
$stmt->close();

// ---- Active clients for the Client Assignment dropdown ----
$clients_list = [];
$clientsResult = $conn->query("SELECT id, client_name FROM clients WHERE is_active = 1 ORDER BY client_name ASC");
if ($clientsResult) {
    while ($row = $clientsResult->fetch_assoc()) {
        $clients_list[] = $row;
    }
}

$education_rows = [];
if (!empty($education_json)) {
    $decoded = json_decode($education_json, true);
    if (is_array($decoded)) $education_rows = $decoded;
}
for ($i = count($education_rows); $i < 4; $i++) {
    $education_rows[] = ['school' => '', 'year_graduated' => '', 'course' => ''];
}

function val($v) { return htmlspecialchars((string) $v); }
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

<div class="pv-shell">

    <div class="pv-header pv-header-form">
        <div class="pv-header-left">
            <div class="pv-avatar-upload">
                <div class="pv-avatar" id="avatarPreview">
                    <?php if (!empty($profile_photo_url)): ?>
                        <img src="<?= htmlspecialchars($profile_photo_url) ?>" alt="Profile photo" id="avatarImg">
                    <?php else: ?>
                        <span id="avatarInitials"><?php
                            $initials = strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1));
                            echo htmlspecialchars($initials ?: '?');
                        ?></span>
                        <img src="" alt="Profile photo" id="avatarImg" style="display:none;">
                    <?php endif; ?>
                </div>
                <label for="photoInput" class="pv-avatar-edit-btn" title="Change photo">✎</label>
                <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
            </div>
            <div class="pv-header-info">
                <h1>Edit Profile</h1>
                <div class="pv-header-sub" id="photoStatus">Update your information below. All fields are required.</div>
            </div>
        </div>
        <div class="pv-header-actions">
            <button onclick="window.location.href='/profile'" class="pv-btn pv-btn-ghost">
                Back to Profile
            </button>
        </div>
    </div>

    <div class="warning-box" id="draftNotice" style="display:none;">
        Restored your unsaved changes from your last visit.
    </div>

    <?php if (!empty($warnings)): ?>
    <div class="warning-message">
        <?php foreach ($warnings as $w) echo htmlspecialchars($w) . "<br>"; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="error-message">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="editProfileForm">
        <input type="hidden" name="action" value="save">

        <div class="pv-card">
            <div class="pv-card-header">
                <span class="pv-accent"></span>
                <h2>Account &amp; Contact</h2>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="fname" id="fname" value="<?= val($fname) ?>">
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="mname" id="mname" value="<?= val($mname) ?>">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lname" id="lname" value="<?= val($lname) ?>">
                </div>

                <div class="form-group full-width">
                    <label>Address</label>
                    <input type="text" name="address" id="address" value="<?= val($address) ?>">
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" value="<?= val($contact_number) ?>">
                </div>

                <div class="form-group">
                    <label>Birthday</label>
                    <input type="date" name="birthday" id="birthday" value="<?= val($birthday) ?>">
                </div>
            </div>
        </div>

        <div class="pv-card">
            <div class="pv-card-header">
                <span class="pv-accent"></span>
                <h2>Personal Details</h2>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Civil Status</label>
                    <select name="civil_status" id="civil_status">
                        <option value="">Select</option>
                        <?php foreach (['Single', 'Married', 'Widow'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $civil_status === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="dependentsGroup" style="display:none;">
                    <label>No. of Dependents</label>
                    <input type="number" min="0" name="no_of_dependents" id="no_of_dependents" value="<?= val($no_of_dependents) ?>">
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" id="gender">
                        <option value="">Select</option>
                        <option value="M" <?= $gender === 'M' ? 'selected' : '' ?>>Male</option>
                        <option value="F" <?= $gender === 'F' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Height (cm)</label>
                    <input type="number" step="0.1" name="height_cm" id="height_cm" value="<?= val($height_cm) ?>">
                </div>

                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" step="0.1" name="weight_kg" id="weight_kg" value="<?= val($weight_kg) ?>">
                </div>

                <div class="form-group">
                    <label>Blood Type</label>
                    <select name="blood_type" id="blood_type">
                        <option value="">Select</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                        <option value="<?= $bt ?>" <?= $blood_type === $bt ? 'selected' : '' ?>><?= $bt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Religion</label>
                    <input type="text" name="religion" id="religion" value="<?= val($religion) ?>">
                </div>
            </div>
        </div>

        <div class="pv-card">
            <div class="pv-card-header">
                <span class="pv-accent"></span>
                <h2>Government IDs</h2>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>TIN No.</label>
                    <input type="text" name="tin_no" id="tin_no" value="<?= val($tin_no) ?>"
                           data-mask="3-3-3-3" inputmode="numeric" maxlength="15"
                           placeholder="123-456-789-101">
                </div>

                <div class="form-group">
                    <label>SSS No.</label>
                    <input type="text" name="sss_no" id="sss_no" value="<?= val($sss_no) ?>"
                           data-mask="2-7-1" inputmode="numeric" maxlength="12"
                           placeholder="12-1234567-1" >
                </div>

                <div class="form-group">
                    <label>Pag-IBIG No.</label>
                    <input type="text" name="pagibig_no" id="pagibig_no" value="<?= val($pagibig_no) ?>"
                           data-mask="4-4-4" inputmode="numeric" maxlength="14"
                           placeholder="1234-1234-1234" >
                </div>

                <div class="form-group">
                    <label>PhilHealth No.</label>
                    <input type="text" name="philhealth_no" id="philhealth_no" value="<?= val($philhealth_no) ?>"
                           data-mask="2-9-1" inputmode="numeric" maxlength="14"
                           placeholder="12-123456789-1" >
                </div>
            </div>
        </div>

        <div class="pv-card">
            <div class="pv-card-header">
                <span class="pv-accent"></span>
                <h2>Employment</h2>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>PMES Orientation Date</label>
                    <input type="date" name="pmes_orientation_date" id="pmes_orientation_date" value="<?= val($pmes_orientation_date) ?>" >
                </div>

                <div class="form-group">
                    <label>Position</label>
                    <input type="text" name="position" id="position" value="<?= val($position) ?>" >
                </div>

                <div class="form-group full-width">
                    <label>Client Assignment</label>
                    <select name="client_id" id="client_id" >
                        <option value="">Select client</option>
                        <?php foreach ($clients_list as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ((int) $client_id === (int) $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['client_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Facebook Account</label>
                    <input type="text" name="facebook_account" id="facebook_account" value="<?= val($facebook_account) ?>" >
                </div>
            </div>
        </div>

        <div class="pv-card">
            <div class="pv-card-header">
                <span class="pv-accent"></span>
                <h2>Educational Attainment</h2>
            </div>
            <div class="education-table education-table-form">
                <div class="education-row education-header">
                    <div>School</div>
                    <div>Year Graduated</div>
                    <div>Course</div>
                </div>
                <?php foreach ($education_rows as $i => $row): ?>
                <div class="education-row">
                    <input type="text" name="edu_school[]" id="edu_school_<?= $i ?>" value="<?= val($row['school'] ?? '') ?>" placeholder="School" >
                    <input type="text" name="edu_year[]" id="edu_year_<?= $i ?>" value="<?= val($row['year_graduated'] ?? '') ?>" placeholder="Year Graduated" >
                    <input type="text" name="edu_course[]" id="edu_course_<?= $i ?>" value="<?= val($row['course'] ?? '') ?>" placeholder="Course" >
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pv-card pv-card-emergency">
            <div class="pv-card-header">
                <span class="pv-accent pv-accent-gold"></span>
                <h2>To Notify in Case of Emergency</h2>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="emergency_name" id="emergency_name" value="<?= val($emergency_name) ?>" >
                </div>
                <div class="form-group">
                    <label>Relationship</label>
                    <input type="text" name="emergency_relationship" id="emergency_relationship" value="<?= val($emergency_relationship) ?>" >
                </div>
                <div class="form-group full-width">
                    <label>Address</label>
                    <input type="text" name="emergency_address" id="emergency_address" value="<?= val($emergency_address) ?>" >
                </div>
                <div class="form-group full-width">
                    <label>Contact No.</label>
                    <input type="text" name="emergency_contact_no" id="emergency_contact_no" value="<?= val($emergency_contact_no) ?>" >
                </div>
            </div>
        </div>

        <div class="pv-form-actions">
            <button type="submit" class="pv-btn pv-btn-primary">Save</button>
            <button type="button" class="pv-btn pv-btn-ghost-dark" id="leaveBtn">Save Draft &amp; Leave</button>
        </div>
    </form>

</div>

<script>
(function () {
    var USER_ID = <?= json_encode($user_id) ?>;
    var DRAFT_KEY = 'bhlmpc_profile_draft_' + USER_ID;

    var form = document.getElementById('editProfileForm');
    var civilStatus = document.getElementById('civil_status');
    var dependentsGroup = document.getElementById('dependentsGroup');
    var dependentsInput = document.getElementById('no_of_dependents');

    function toggleDependents() {
        if (civilStatus.value === 'Married') {
            dependentsGroup.style.display = '';
            dependentsInput.setAttribute('required', 'required');
        } else {
            dependentsGroup.style.display = 'none';
            dependentsInput.removeAttribute('required');
        }
    }
    civilStatus.addEventListener('change', toggleDependents);
    toggleDependents();

    // ---- Formatted ID inputs (TIN, SSS, Pag-IBIG, PhilHealth) ----
    // data-mask="3-3-3-3" means: group sizes of 3 digits each, dash-separated.
    function applyMask(el) {
        var groups = el.getAttribute('data-mask').split('-').map(Number);
        var digits = el.value.replace(/\D/g, '').slice(0, groups.reduce(function (a, b) { return a + b; }, 0));

        var parts = [];
        var pos = 0;
        groups.forEach(function (size) {
            if (pos < digits.length) {
                parts.push(digits.slice(pos, pos + size));
                pos += size;
            }
        });
        el.value = parts.join('-');
    }

    document.querySelectorAll('input[data-mask]').forEach(function (el) {
        applyMask(el); // format any prefilled/restored value on load
        el.addEventListener('input', function () { applyMask(el); });
    });

    // ---- Profile picture upload (Cloudinary via upload_profile_picture.php) ----
    var photoInput = document.getElementById('photoInput');
    var avatarImg = document.getElementById('avatarImg');
    var avatarInitials = document.getElementById('avatarInitials');
    var photoStatus = document.getElementById('photoStatus');

    photoInput.addEventListener('change', function () {
        var file = photoInput.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            photoStatus.textContent = 'Image must be under 5MB.';
            photoStatus.style.color = '#b3261e';
            return;
        }

        // Instant local preview while the real upload happens in the background
        var localPreviewUrl = URL.createObjectURL(file);
        avatarImg.src = localPreviewUrl;
        avatarImg.style.display = '';
        if (avatarInitials) avatarInitials.style.display = 'none';

        photoStatus.textContent = 'Uploading photo...';
        photoStatus.style.color = '';

        var formData = new FormData();
        formData.append('photo', file);

        fetch('/upload_profile_picture', {
            method: 'POST',
            body: formData
        })
            .then(function (res) {
                return res.text().then(function (raw) {
                    var parsed;
                    try {
                        parsed = JSON.parse(raw);
                    } catch (e) {
                        // Server didn't return valid JSON — surface the raw response
                        // (often a PHP warning/fatal error) so it's actually debuggable.
                        console.error('Non-JSON response from upload_profile_picture.php:', raw);
                        throw new Error('Server error — see browser console for details.');
                    }
                    return parsed;
                });
            })
            .then(function (data) {
                if (data.success) {
                    avatarImg.src = data.url; // swap to the real Cloudinary URL
                    photoStatus.textContent = 'Photo updated.';
                    photoStatus.style.color = '#096D2B';
                } else {
                    photoStatus.textContent = 'Upload failed: ' + (data.error || 'Unknown error.');
                    photoStatus.style.color = '#b3261e';
                }
            })
            .catch(function (err) {
                photoStatus.textContent = 'Upload failed: ' + (err.message || 'Check your connection and try again.');
                photoStatus.style.color = '#b3261e';
            });
    });

    // ---- Serialize current form fields (excluding action) into a plain object ----
    function collectFormData() {
        var data = {};
        var elements = form.querySelectorAll('input, select');
        elements.forEach(function (el) {
            if (el.name === 'action') return;
            if (el.name.endsWith('[]')) {
                var key = el.name;
                data[key] = data[key] || [];
                data[key].push(el.value);
            } else {
                data[el.name] = el.value;
            }
        });
        return data;
    }

    function applyFormData(data) {
        var arrayCursors = {};
        var elements = form.querySelectorAll('input, select');
        elements.forEach(function (el) {
            if (el.name === 'action') return;
            if (el.name.endsWith('[]')) {
                var key = el.name;
                arrayCursors[key] = arrayCursors[key] || 0;
                if (data[key] && data[key][arrayCursors[key]] !== undefined) {
                    el.value = data[key][arrayCursors[key]];
                }
                arrayCursors[key]++;
            } else if (data[el.name] !== undefined) {
                el.value = data[el.name];
            }
        });
        toggleDependents();
    }

    // ---- Load draft cache on page load (only if a draft exists) ----
    try {
        var saved = localStorage.getItem(DRAFT_KEY);
        if (saved) {
            applyFormData(JSON.parse(saved));
            document.getElementById('draftNotice').style.display = 'block';
        }
    } catch (e) {
        console.warn('Could not read profile draft cache', e);
    }

    // ---- "Save Draft & Leave": cache to localStorage, then navigate away without submitting ----
    document.getElementById('leaveBtn').addEventListener('click', function () {
        try {
            localStorage.setItem(DRAFT_KEY, JSON.stringify(collectFormData()));
        } catch (e) {
            console.warn('Could not write profile draft cache', e);
        }
        window.location.href = '/profile';
    });

    // ---- On successful submit, clear the draft cache ----
    form.addEventListener('submit', function () {
        try {
            localStorage.removeItem(DRAFT_KEY);
        } catch (e) { /* no-op */ }
    });
})();
</script>

</body>
</html>
