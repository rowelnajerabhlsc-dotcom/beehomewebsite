<?php
session_start();
include "config.php";
include "cloudinary_helpers.php";
include "share_capital_helpers.php";

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
$duplicate_warning = null; // set if a period was already encoded, awaiting staff confirmation

// ---- Handle: encode a member's monthly share capital ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_capital') {

    $target_user_id = (int) ($_POST['user_id'] ?? 0);
    $period_input   = trim($_POST['period'] ?? '');
    $month_shares   = trim($_POST['month_shares'] ?? '');
    $month_amount   = trim($_POST['month_amount'] ?? '');
    $staff_note     = trim($_POST['remarks'] ?? '');
    $confirmed      = ($_POST['confirm_correction'] ?? '') === '1';

    $parsed_period = scr_parse_period_input($period_input);

    if ($target_user_id <= 0 || !$parsed_period || $month_shares === '' || $month_amount === '' || !is_numeric($month_shares) || !is_numeric($month_amount)) {
        $errors[] = "Please select a member, a valid month/year (MM-YYYY), and numeric shares/amount.";
    } else {
        $period = scr_format_period($parsed_period[0], $parsed_period[1]);

        // ---- Duplicate-period check: warn instead of silently stacking ----
        $existing = scr_find_existing_period_entries($conn, $target_user_id, $period);

        if (!empty($existing) && !$confirmed) {
            $existing_shares_total = array_sum(array_column($existing, 'delta_shares'));
            $existing_amount_total = array_sum(array_column($existing, 'delta_amount'));

            $duplicate_warning = [
                'period' => $period,
                'existing_entries' => count($existing),
                'existing_shares' => $existing_shares_total,
                'existing_amount' => $existing_amount_total,
                // preserve what staff just entered so the form can be re-shown filled in
                'resubmit' => [
                    'user_id' => $target_user_id,
                    'period' => $period_input,
                    'month_shares' => $month_shares,
                    'month_amount' => $month_amount,
                    'remarks' => $staff_note,
                ],
            ];
        } else {
            // ---- Proceed: fetch current running total, add this month's delta ----
            $old_shares = 0;
            $old_amount = 0;
            $checkStmt = $conn->prepare("SELECT total_shares, total_amount FROM capital_shares WHERE user_id = ?");
            $checkStmt->bind_param("i", $target_user_id);
            $checkStmt->execute();
            $checkStmt->bind_result($old_shares, $old_amount);
            $found = $checkStmt->fetch();
            $checkStmt->close();
            $old_shares = (float) $old_shares;
            $old_amount = (float) $old_amount;

            $new_shares = $old_shares + (float) $month_shares;
            $new_amount = $old_amount + (float) $month_amount;

            if ($found) {
                $updateStmt = $conn->prepare("UPDATE capital_shares SET total_shares = ?, total_amount = ?, updated_by = ? WHERE user_id = ?");
                $updateStmt->bind_param("ddii", $new_shares, $new_amount, $current_staff_id, $target_user_id);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                $insertStmt = $conn->prepare("INSERT INTO capital_shares (user_id, total_shares, total_amount, updated_by) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param("iddi", $target_user_id, $new_shares, $new_amount, $current_staff_id);
                $insertStmt->execute();
                $insertStmt->close();
            }

            // Log this entry — remarks carries the period tag (+ optional note).
            // A correction (confirmed duplicate) is just another entry for the
            // same period; nothing here ever overwrites a prior log row.
            $log_note = $confirmed && !empty($existing) ? ($staff_note !== '' ? $staff_note : 'Correction') : $staff_note;
            $log_remarks = scr_build_remarks($period, $log_note);

            $logStmt = $conn->prepare("
                INSERT INTO capital_share_logs
                    (user_id, old_shares, new_shares, old_amount, new_amount, remarks, changed_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $logStmt->bind_param("iddddsi", $target_user_id, $old_shares, $new_shares, $old_amount, $new_amount, $log_remarks, $current_staff_id);
            $logStmt->execute();
            $logStmt->close();

            $success_msg = ($confirmed && !empty($existing))
                ? "Correction saved for {$period}."
                : "Share capital encoded for {$period}.";
        }
    }
}

// ---- Handle: attach a PDF document to a member's record ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_document') {

    $doc_user_id = (int) ($_POST['doc_user_id'] ?? 0);
    $period_label = trim($_POST['period_label'] ?? '');
    $doc_period_valid = scr_parse_period_input($period_label);

    if ($doc_user_id <= 0 || !$doc_period_valid || !isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Please select a member, a valid period (MM-YYYY), and a valid PDF file.";
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

                $params_to_sign = ['folder' => $folder, 'public_id' => $public_id, 'timestamp' => $timestamp, 'type' => 'private'];
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
                        'type'      => 'private',
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
// ---- Ledger grid: if a member is selected for viewing, build their year x month grid ----
$ledger_member_id = (int) ($_GET['ledger_user_id'] ?? 0);
$ledger_grid = [];
$ledger_member_name = '';
if ($ledger_member_id > 0) {
    $ledger_grid = scr_build_ledger_grid($conn, $ledger_member_id);
    foreach ($members as $m) {
        if ((int) $m['id'] === $ledger_member_id) {
            $ledger_member_name = trim($m['fname'] . ' ' . $m['lname']) ?: $m['username'];
            break;
        }
    }
}

$month_labels = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Share Capital</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="pv-shell">

    <div class="pv-header">
        <div class="pv-header-left">
            <div class="pv-header-info">
                <h1 style="color:#fff;">Manage Share Capital</h1>
                <div class="pv-header-sub">Encode members' monthly share capital and attach share capital documents.</div>
            </div>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="success-message"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="error-message"><?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?></div>
    <?php endif; ?>

    <?php if ($duplicate_warning): ?>
    <div class="warning-box">
        <strong><?= htmlspecialchars($duplicate_warning['period']) ?></strong> already has
        <?= (int) $duplicate_warning['existing_entries'] ?> entr<?= $duplicate_warning['existing_entries'] === 1 ? 'y' : 'ies' ?>
        totaling <?= htmlspecialchars(number_format($duplicate_warning['existing_shares'], 2)) ?> shares /
        ₱<?= htmlspecialchars(number_format($duplicate_warning['existing_amount'], 2)) ?> for this member.
        <br>If this is a correction (topping up or fixing that month), confirm below to add this as an additional entry for the same month.
        <form method="POST" style="margin-top:10px;">
            <input type="hidden" name="action" value="update_capital">
            <input type="hidden" name="confirm_correction" value="1">
            <input type="hidden" name="user_id" value="<?= (int) $duplicate_warning['resubmit']['user_id'] ?>">
            <input type="hidden" name="period" value="<?= htmlspecialchars($duplicate_warning['resubmit']['period']) ?>">
            <input type="hidden" name="month_shares" value="<?= htmlspecialchars($duplicate_warning['resubmit']['month_shares']) ?>">
            <input type="hidden" name="month_amount" value="<?= htmlspecialchars($duplicate_warning['resubmit']['month_amount']) ?>">
            <input type="hidden" name="remarks" value="<?= htmlspecialchars($duplicate_warning['resubmit']['remarks']) ?>">
            <button type="submit" class="pv-btn pv-btn-primary">Confirm &amp; Save as Correction</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- ===== ENCODE MONTHLY SHARE CAPITAL ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Encode Monthly Share Capital</h2>
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
                    <label>Month / Year</label>
                    <input type="text" name="period" data-mask="2-4" inputmode="numeric" maxlength="7" placeholder="03-2026" required>
                </div>
                <div class="form-group">
                    <label>Shares This Month</label>
                    <input type="number" step="0.01" name="month_shares" required>
                </div>
                <div class="form-group">
                    <label>Amount This Month (₱)</label>
                    <input type="number" step="0.01" name="month_amount" required>
                </div>
                <div class="form-group full-width">
                    <label>Remarks (optional)</label>
                    <input type="text" name="remarks" placeholder="e.g. Payroll deduction, manual payment, etc.">
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
                    <label>Month / Year</label>
                    <input type="text" name="period_label" data-mask="2-4" inputmode="numeric" maxlength="7" placeholder="03-2026" required>
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

    <!-- ===== SHARE CAPITAL LEDGER (year x month grid) ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Share Capital Ledger</h2>
        </div>
        <form method="GET" class="form-grid" style="margin-bottom:16px;">
            <div class="form-group full-width">
                <label>View member's ledger</label>
                <select name="ledger_user_id" onchange="this.form.submit()">
                    <option value="">Select member</option>
                    <?php foreach ($members as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= $ledger_member_id === (int) $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(trim($m['fname'] . ' ' . $m['lname']) ?: $m['username']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($ledger_member_id > 0): ?>
            <?php if (empty($ledger_grid)): ?>
                <p style="color:#5a6b5f;">No monthly entries encoded yet for <?= htmlspecialchars($ledger_member_name) ?>.</p>
            <?php else: ?>
                <p style="color:#5a6b5f; margin-bottom:10px;">Showing ledger for <strong><?= htmlspecialchars($ledger_member_name) ?></strong> — amount (₱) per month.</p>
                <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="background:#f1f3f5;">
                            <th style="padding:8px; text-align:left; border:1px solid #e2e8e4;">Year</th>
                            <?php foreach ($month_labels as $ml): ?>
                            <th style="padding:8px; text-align:center; border:1px solid #e2e8e4;"><?= $ml ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ledger_grid as $year => $months): ?>
                        <tr>
                            <td style="padding:8px; font-weight:700; border:1px solid #e2e8e4;"><?= htmlspecialchars($year) ?></td>
                            <?php foreach (array_keys($month_labels) as $mkey): ?>
                            <td style="padding:8px; text-align:center; border:1px solid #e2e8e4;">
                                <?php if (isset($months[$mkey])): ?>
                                    ₱<?= number_format($months[$mkey]['amount'], 2) ?>
                                    <?php if ($months[$mkey]['entries'] > 1): ?>
                                        <br><span style="font-size:11px; color:#5a6b5f;">(<?= $months[$mkey]['entries'] ?> entries)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#c3cfc7;">—</span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ===== CURRENT BALANCES ===== -->
    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Current Share Capital Balances</h2>
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
            <?php
                $parsed = scr_parse_remarks($l['remarks']);
                $period_display = $parsed ? $parsed[0] : 'Legacy entry';
                $delta_shares = (float) $l['new_shares'] - (float) $l['old_shares'];
                $delta_amount = (float) $l['new_amount'] - (float) $l['old_amount'];
            ?>
            <div class="education-row">
                <div>
                    <?= htmlspecialchars(trim($l['fname'] . ' ' . $l['lname'])) ?> —
                    <strong><?= htmlspecialchars($period_display) ?></strong>:
                    +<?= htmlspecialchars(number_format($delta_shares, 2)) ?> shares,
                    +₱<?= htmlspecialchars(number_format($delta_amount, 2)) ?>
                    <?= ($parsed && $parsed[1]) ? ' (' . htmlspecialchars($parsed[1]) . ')' : '' ?>
                </div>
                <div><?= htmlspecialchars($l['changed_by_username'] ?? '—') ?></div>
                <div><?= htmlspecialchars(date('M d, Y g:i A', strtotime($l['changed_at']))) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
// Reuses the same digit-group masking pattern as edit_profile.php's
// TIN/SSS/Pag-IBIG masks — data-mask="2-4" here means "2 digits, dash,
// 4 digits" (MM-YYYY).
(function () {
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
        applyMask(el);
        el.addEventListener('input', function () { applyMask(el); });
    });
})();
</script>

</body>
</html>
