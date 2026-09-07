<?php
session_start();
include "config.php";

// ---- Access control: department staff and above only ----
// Assumption: encoding capital share is a Staff-level (role 2+) task.
// Bump this to 3 if your Finance/Accounting encoding should be Manager-only.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 0) < 2) {
    header("Location: /login");
    exit();
}

$current_staff_id = $_SESSION['user_id'];
$errors = [];
$success_msg = '';

// ---- Handle: encode / update a member's capital share ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_capital') {

    $target_user_id = (int) ($_POST['user_id'] ?? 0);
    $new_shares     = trim($_POST['total_shares'] ?? '');
    $new_amount     = trim($_POST['total_amount'] ?? '');
    $remarks        = trim($_POST['remarks'] ?? '');

    if ($target_user_id <= 0 || $new_shares === '' || $new_amount === '' || !is_numeric($new_shares) || !is_numeric($new_amount)) {
        $errors[] = "Please select a member and provide valid numeric shares/amount.";
    } else {
        // Fetch existing balance (if any) so we can log the before/after
        $old_shares = null;
        $old_amount = null;
        $checkStmt = $conn->prepare("SELECT total_shares, total_amount FROM capital_shares WHERE user_id = ?");
        $checkStmt->bind_param("i", $target_user_id);
        $checkStmt->execute();
        $checkStmt->bind_result($old_shares, $old_amount);
        $found = $checkStmt->fetch();
        $checkStmt->close();

        // Upsert the current balance
        if ($found) {
            $updateStmt = $conn->prepare("
                UPDATE capital_shares
                SET total_shares = ?, total_amount = ?, remarks = ?, updated_by = ?
                WHERE user_id = ?
            ");
            $updateStmt->bind_param("ddsii", $new_shares, $new_amount, $remarks, $current_staff_id, $target_user_id);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $insertStmt = $conn->prepare("
                INSERT INTO capital_shares (user_id, total_shares, total_amount, remarks, updated_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->bind_param("iddsi", $target_user_id, $new_shares, $new_amount, $remarks, $current_staff_id);
            $insertStmt->execute();
            $insertStmt->close();
        }

        // Always write the audit log entry, regardless of insert vs update
        $logStmt = $conn->prepare("
            INSERT INTO capital_share_logs
                (user_id, old_shares, new_shares, old_amount, new_amount, remarks, changed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $logStmt->bind_param("iddddsi", $target_user_id, $old_shares, $new_shares, $old_amount, $new_amount, $remarks, $current_staff_id);
        $logStmt->execute();
        $logStmt->close();

        $success_msg = "Capital share updated and logged.";
    }
}

// ---- Handle: attach a PDF document to a member's record ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_document') {

    $doc_user_id = (int) ($_POST['doc_user_id'] ?? 0);
    $period_label = trim($_POST['period_label'] ?? '');

    if ($doc_user_id <= 0 || $period_label === '' || !isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Please select a member, a period label, and a valid PDF file.";
    } else {
        $file = $_FILES['document'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mime !== 'application/pdf') {
            $errors[] = "Only PDF files are allowed.";
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = "File must be under 10MB.";
        } else {
            // ---- Upload to Cloudinary as a raw resource (PDFs aren't images) ----
            $cloud_name = $cloudinary_config['cloud_name'];
            $api_key    = $cloudinary_config['api_key'];
            $api_secret = $cloudinary_config['api_secret'];

            if (!$cloud_name || !$api_key || !$api_secret) {
                $errors[] = "Cloudinary is not configured on the server.";
            } else {
                $timestamp = time();
                $folder    = 'bhlmpc/capital_share_docs';
                $public_id = 'member_' . $doc_user_id . '_' . $timestamp;

                $params_to_sign = ['folder' => $folder, 'public_id' => $public_id, 'timestamp' => $timestamp];
                ksort($params_to_sign);
                $signable = '';
                foreach ($params_to_sign as $key => $value) {
                    $signable .= ($signable === '' ? '' : '&') . $key . '=' . $value;
                }
                $signature = sha1($signable . $api_secret);

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://api.cloudinary.com/v1_1/{$cloud_name}/raw/upload",
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => [
                        'file'      => new CURLFile($file['tmp_name'], 'application/pdf', $file['name']),
                        'api_key'   => $api_key,
                        'timestamp' => $timestamp,
                        'signature' => $signature,
                        'folder'    => $folder,
                        'public_id' => $public_id,
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                ]);
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $result = json_decode($response, true);

                if ($http_code !== 200 || !isset($result['secure_url'])) {
                    $errors[] = "Cloudinary upload failed: " . ($result['error']['message'] ?? 'Unknown error.');
                } else {
                    $docStmt = $conn->prepare("
                        INSERT INTO capital_share_documents (user_id, period_label, file_url, public_id, uploaded_by)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $docStmt->bind_param("isssi", $doc_user_id, $period_label, $result['secure_url'], $result['public_id'], $current_staff_id);
                    $docStmt->execute();
                    $docStmt->close();

                    $success_msg = "Document uploaded for member.";
                }
            }
        }
    }
}

// ---- Load members list for the dropdown (adjust the SELECT to your users table) ----
$members = [];
$membersResult = $conn->query("
    SELECT u.id, u.username, p.fname, p.lname
    FROM users u
    LEFT JOIN user_profiles p ON u.id = p.user_id
    ORDER BY p.lname ASC, p.fname ASC
");
if ($membersResult) {
    while ($row = $membersResult->fetch_assoc()) {
        $members[] = $row;
    }
}

// ---- Load current capital share balances + last update info ----
$balances = [];
$balancesResult = $conn->query("
    SELECT cs.user_id, cs.total_shares, cs.total_amount, cs.updated_at,
           p.fname, p.lname, u2.username AS updated_by_username
    FROM capital_shares cs
    LEFT JOIN user_profiles p ON cs.user_id = p.user_id
    LEFT JOIN users u2 ON cs.updated_by = u2.id
    ORDER BY cs.updated_at DESC
");
if ($balancesResult) {
    while ($row = $balancesResult->fetch_assoc()) {
        $balances[] = $row;
    }
}

// ---- Load recent audit log entries ----
$logs = [];
$logsResult = $conn->query("
    SELECT l.*, p.fname, p.lname, u2.username AS changed_by_username
    FROM capital_share_logs l
    LEFT JOIN user_profiles p ON l.user_id = p.user_id
    LEFT JOIN users u2 ON l.changed_by = u2.id
    ORDER BY l.changed_at DESC
    LIMIT 100
");
if ($logsResult) {
    while ($row = $logsResult->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Capital Share</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="pv-shell">

    <div class="pv-header">
        <div class="pv-header-left">
            <div class="pv-header-info">
                <h1 style="color:#fff;">Manage Capital Share</h1>
                <div class="pv-header-sub">Encode member capital share values and attach share capital documents.</div>
            </div>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="success-message"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="error-message"><?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?></div>
    <?php endif; ?>

    <!-- ===== ENCODE / UPDATE CAPITAL SHARE ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Encode Capital Share</h2>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_capital">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Member</label>
                    <select name="user_id" required>
                        <option value="">Select member</option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m['id'] ?>">
                            <?= htmlspecialchars(trim($m['fname'] . ' ' . $m['lname']) ?: $m['username']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Shares</label>
                    <input type="number" step="0.01" name="total_shares" required>
                </div>
                <div class="form-group">
                    <label>Total Amount (₱)</label>
                    <input type="number" step="0.01" name="total_amount" required>
                </div>
                <div class="form-group full-width">
                    <label>Remarks (optional)</label>
                    <input type="text" name="remarks" placeholder="e.g. Annual encoding, correction, etc.">
                </div>
            </div>
            <div class="pv-form-actions">
                <button type="submit" class="pv-btn pv-btn-primary">Save</button>
            </div>
        </form>
    </div>

    <!-- ===== UPLOAD DOCUMENT ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Upload Share Capital Document</h2>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_document">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Member</label>
                    <select name="doc_user_id" required>
                        <option value="">Select member</option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m['id'] ?>">
                            <?= htmlspecialchars(trim($m['fname'] . ' ' . $m['lname']) ?: $m['username']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Period Label</label>
                    <input type="text" name="period_label" placeholder="e.g. 2026" required>
                </div>
                <div class="form-group">
                    <label>PDF File</label>
                    <input type="file" name="document" accept="application/pdf" required>
                </div>
            </div>
            <div class="pv-form-actions">
                <button type="submit" class="pv-btn pv-btn-primary">Upload</button>
            </div>
        </form>
    </div>

    <!-- ===== CURRENT BALANCES ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Current Capital Share Balances</h2>
        </div>
        <div class="education-table">
            <div class="education-row education-header">
                <div>Member</div>
                <div>Shares</div>
                <div>Amount</div>
            </div>
            <?php if (empty($balances)): ?>
            <div class="education-row"><div>No records yet.</div><div></div><div></div></div>
            <?php endif; ?>
            <?php foreach ($balances as $b): ?>
            <div class="education-row">
                <div><?= htmlspecialchars(trim($b['fname'] . ' ' . $b['lname'])) ?></div>
                <div><?= htmlspecialchars(number_format((float) $b['total_shares'], 2)) ?></div>
                <div>₱<?= htmlspecialchars(number_format((float) $b['total_amount'], 2)) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ===== AUDIT LOG ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Recent Changes (Audit Log)</h2>
        </div>
        <div class="education-table">
            <div class="education-row education-header">
                <div>Member / Change</div>
                <div>By</div>
                <div>When</div>
            </div>
            <?php if (empty($logs)): ?>
            <div class="education-row"><div>No changes logged yet.</div><div></div><div></div></div>
            <?php endif; ?>
            <?php foreach ($logs as $l): ?>
            <div class="education-row">
                <div>
                    <?= htmlspecialchars(trim($l['fname'] . ' ' . $l['lname'])) ?>:
                    <?= htmlspecialchars($l['old_shares'] ?? '—') ?> → <?= htmlspecialchars($l['new_shares']) ?> shares,
                    ₱<?= htmlspecialchars($l['old_amount'] ?? '0') ?> → ₱<?= htmlspecialchars($l['new_amount']) ?>
                    <?= $l['remarks'] ? ' (' . htmlspecialchars($l['remarks']) . ')' : '' ?>
                </div>
                <div><?= htmlspecialchars($l['changed_by_username'] ?? '—') ?></div>
                <div><?= htmlspecialchars(date('M d, Y g:i A', strtotime($l['changed_at']))) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

</body>
</html>
