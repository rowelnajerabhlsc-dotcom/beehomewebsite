<?php
session_start();
include "config.php";
include "cloudinary_helpers.php";
include "share_capital_helpers.php";

// Fixed par value: every share is worth this many pesos. Shares are always
// derived from the peso amount encoded (amount / PAR_VALUE_PER_SHARE) —
// staff never type a share count directly.
define('PAR_VALUE_PER_SHARE', 200);

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
    $month_amount   = trim($_POST['month_amount'] ?? '');
    $staff_note     = trim($_POST['remarks'] ?? '');
    $confirmed      = ($_POST['confirm_correction'] ?? '') === '1';

    $parsed_period = scr_parse_period_input($period_input);

    if ($target_user_id <= 0 || !$parsed_period || $month_amount === '' || !is_numeric($month_amount)) {
        $errors[] = "Please select a member, a valid month/year (MM-YYYY), and a numeric amount.";
    } else {
        // Shares are always derived from the peso amount at the fixed par value.
        $month_shares = (float) $month_amount / PAR_VALUE_PER_SHARE;
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

            // "Amount" is the peso contribution itself; shares were already
            // derived from it above at the fixed par value.
            $month_total_amount = (float) $month_amount;

            if ($confirmed && !empty($existing)) {
                // ---- Correction: REPLACE this period's prior contribution ----
                // Back out whatever was previously logged for this period, then
                // apply the newly entered shares/amount as the period's new total.
                $existing_shares_total = array_sum(array_column($existing, 'delta_shares'));
                $existing_amount_total = array_sum(array_column($existing, 'delta_amount'));

                $new_shares = $old_shares - $existing_shares_total + (float) $month_shares;
                $new_amount = $old_amount - $existing_amount_total + $month_total_amount;
            } else {
                $new_shares = $old_shares + (float) $month_shares;
                $new_amount = $old_amount + $month_total_amount;
            }

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
            // A correction (confirmed duplicate) nets out the period's prior
            // total against the new one above; the log row itself is still an
            // append-only entry (old_shares/old_amount here are the running
            // totals just before this correction, not the prior period entry).
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

// ---- Display-only derived totals for the summary header / ledger panel ----
// Shares are always DERIVED from amount / par value for display, never read
// from the stored total_shares column — this keeps every page's shares
// figure in agreement even where old rows predate the par-value fix.
$grand_total_amount = array_sum(array_column($balances, 'total_amount'));
$grand_total_shares = $grand_total_amount / PAR_VALUE_PER_SHARE;

$ledger_period_amount_total = 0;
foreach ($ledger_grid as $ledger_year_row) {
    foreach ($ledger_year_row as $ledger_month_cell) {
        $ledger_period_amount_total += (float) $ledger_month_cell['amount'];
    }
}
$ledger_member_shares = 0;
$ledger_member_amount = 0;
foreach ($balances as $b) {
    if ((int) $b['user_id'] === $ledger_member_id) {
        $ledger_member_amount = (float) $b['total_amount'];
        $ledger_member_shares = $ledger_member_amount / PAR_VALUE_PER_SHARE;
        break;
    }
}

// Legacy/untagged balance: log rows without a PERIOD: tag predate monthly
// encoding, so scr_build_ledger_grid() can't place them in the grid — but
// they're still part of the member's real total. Surface the gap instead
// of letting the grid total look like it disagrees with the real total.
$ledger_legacy_amount = $ledger_member_amount - $ledger_period_amount_total;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Share Capital</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <style>
        /* ===== Share Capital Management — redesign (content only, navbar untouched) ===== */
        .csm-shell{max-width:1180px;margin:0 auto;padding:24px 20px 60px;font-family:inherit;color:#1e2b22;}

        .csm-header{background:linear-gradient(135deg,#0b7a34,#075423);border-radius:16px;padding:22px 28px;color:#fff;margin-bottom:22px;box-shadow:0 6px 18px rgba(9,109,43,.18);}
        .csm-header h1{margin:0;font-size:22px;font-weight:700;letter-spacing:.2px;}
        .csm-header .csm-header-sub{opacity:.85;font-size:13px;margin-top:2px;}
        .csm-stats{display:flex;gap:36px;margin-top:16px;flex-wrap:wrap;}
        .csm-stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.06em;opacity:.8;margin-bottom:2px;}
        .csm-stat-value{font-size:20px;font-weight:700;}

        .csm-card{background:#fff;border:1px solid #e2ece4;border-radius:14px;padding:20px 22px;margin-bottom:20px;box-shadow:0 2px 10px rgba(30,43,34,.05);}
        .csm-card-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;}
        .csm-card-header h2{margin:0;font-size:16px;font-weight:700;color:#096D2B;display:flex;align-items:center;gap:8px;}
        .csm-card-header h2::before{content:"";width:6px;height:6px;border-radius:50%;background:#F5C233;display:inline-block;}

        .csm-inline-select{display:flex;align-items:center;gap:8px;font-size:13px;color:#5a6b5f;}
        .csm-inline-select select{border:1px solid #cdeed8;border-radius:8px;padding:7px 10px;font-size:13px;background:#f5faf6;color:#1e2b22;}

        .csm-encode-panel{background:#fdf6e3;border:1px solid #f3e3b0;border-radius:14px;padding:18px 20px;margin-bottom:20px;}
        .csm-encode-row{display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;}
        .csm-encode-row .csm-field{flex:1 1 150px;min-width:130px;}
        .csm-encode-row .csm-field.csm-field-wide{flex:2 1 220px;}
        .csm-field label{display:block;font-size:11px;font-weight:700;color:#5a6b5f;text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;}
        .csm-field input,.csm-field select{width:100%;border:1px solid #dfe6e1;border-radius:8px;padding:9px 10px;font-size:13px;background:#fff;color:#1e2b22;box-sizing:border-box;}
        .csm-field input:focus,.csm-field select:focus{outline:none;border-color:#26a753;box-shadow:0 0 0 3px rgba(38,167,83,.12);}
        .csm-btn-gold{background:#F5C233;color:#1e2b22;border:none;border-radius:8px;padding:10px 22px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;transition:filter .15s;}
        .csm-btn-gold:hover{filter:brightness(0.95);}
        .csm-btn-green{background:#096D2B;color:#fff;border:none;border-radius:8px;padding:10px 22px;font-size:13px;font-weight:700;cursor:pointer;transition:background .15s;}
        .csm-btn-green:hover{background:#075423;}

        .csm-ledger-layout{display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;}
        .csm-ledger-main{flex:1 1 560px;min-width:0;}
        .csm-ledger-side{flex:0 0 200px;background:#fdf6e3;border:1px solid #f3e3b0;border-radius:12px;padding:16px 18px;}
        .csm-ledger-side h3{margin:0 0 12px;font-size:13px;color:#096D2B;font-weight:700;}
        .csm-summary-row{margin-bottom:12px;}
        .csm-summary-row:last-child{margin-bottom:0;}
        .csm-summary-label{font-size:11px;color:#7c8a80;text-transform:uppercase;letter-spacing:.03em;}
        .csm-summary-value{font-size:15px;font-weight:700;color:#1e2b22;}

        table.csm-table{width:100%;border-collapse:collapse;font-size:12.5px;}
        table.csm-table th,table.csm-table td{padding:9px 8px;border:1px solid #e7f0e9;text-align:center;}
        table.csm-table th{background:#e7f5ea;color:#096D2B;font-weight:700;text-transform:uppercase;font-size:10.5px;letter-spacing:.03em;}
        table.csm-table td:first-child,table.csm-table th:first-child{text-align:left;font-weight:600;}
        table.csm-table tbody tr:nth-child(even){background:#fbfdfb;}

        .csm-list{display:flex;flex-direction:column;}
        .csm-list-row{display:grid;grid-template-columns:1fr 140px 170px;gap:10px;align-items:center;padding:11px 4px;border-bottom:1px solid #eef3ef;font-size:13px;}
        .csm-list-row:last-child{border-bottom:none;}
        .csm-list-header{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#7c8a80;font-weight:700;border-bottom:2px solid #e2ece4;}
        .csm-list-member{display:flex;align-items:center;gap:10px;}
        .csm-avatar{width:26px;height:26px;border-radius:50%;background:#096D2B;color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .csm-list-sub{color:#5a6b5f;}

        .csm-upload-box{background:#fdf6e3;border:2px dashed #e8d68c;border-radius:12px;padding:26px 16px;text-align:center;color:#8a7a3d;margin-bottom:14px;}
        .csm-upload-box svg{margin-bottom:6px;}
        .csm-upload-box .csm-upload-title{font-weight:700;font-size:13px;color:#6b5c22;}
        .csm-upload-box .csm-upload-hint{font-size:11px;margin-top:4px;opacity:.85;}

        .csm-alert{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:16px;}
        .csm-alert-success{background:#e7f5ea;border:1px solid #b7dcc0;color:#096D2B;}
        .csm-alert-error{background:#fdeaea;border:1px solid #f3b0b0;color:#8a2a2a;}
        .csm-alert-warning{background:#fdf6e3;border:1px solid #f3e3b0;color:#7a5d10;}

        @media (max-width:1024px){
            .csm-ledger-side{flex:1 1 100%;}
        }
        @media (max-width:767px){
            .csm-list-row{grid-template-columns:1fr;gap:2px;}
            .csm-stats{gap:20px;}
        }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="csm-shell">

    <div class="csm-header">
        <h1>Share Capital Management</h1>
        <div class="csm-header-sub">Encode members' monthly share capital and attach share capital documents.</div>
        <div class="csm-stats">
            <div>
                <div class="csm-stat-label">Total Shares</div>
                <div class="csm-stat-value"><?= htmlspecialchars(number_format($grand_total_shares, 0)) ?> Shares</div>
            </div>
            <div>
                <div class="csm-stat-label">Total Amount</div>
                <div class="csm-stat-value">₱<?= htmlspecialchars(number_format($grand_total_amount, 2)) ?></div>
            </div>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="csm-alert csm-alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="csm-alert csm-alert-error"><?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?></div>
    <?php endif; ?>

    <?php if ($duplicate_warning): ?>
    <div class="csm-alert csm-alert-warning">
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
            <input type="hidden" name="month_amount" value="<?= htmlspecialchars($duplicate_warning['resubmit']['month_amount']) ?>">
            <input type="hidden" name="remarks" value="<?= htmlspecialchars($duplicate_warning['resubmit']['remarks']) ?>">
            <button type="submit" class="csm-btn-green">Confirm &amp; Save as Correction</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- ===== ENCODE MONTHLY SHARE CAPITAL ===== -->
    <div class="csm-encode-panel">
        <div class="csm-card-header" style="margin-bottom:14px;">
            <h2>Encode Monthly</h2>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_capital">
            <div class="csm-encode-row">
                <div class="csm-field csm-field-wide">
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
                <div class="csm-field">
                    <label>Month / Year</label>
                    <input type="text" name="period" data-mask="2-4" inputmode="numeric" maxlength="7" placeholder="MM-YYYY" required>
                </div>
                <div class="csm-field">
                    <label>Amount ₱</label>
                    <input type="number" step="0.01" name="month_amount" placeholder="0.00" required>
                    <span class="csm-field-hint">Shares = amount ÷ ₱<?= PAR_VALUE_PER_SHARE ?> par value</span>
                </div>
                <div class="csm-field" style="flex:0 0 auto;">
                    <label>&nbsp;</label>
                    <button type="submit" class="csm-btn-gold">Save</button>
                </div>
            </div>
            <div class="csm-field" style="margin-top:12px;">
                <label>Remarks (optional)</label>
                <input type="text" name="remarks" placeholder="e.g. Payroll deduction, manual payment, etc.">
            </div>
        </form>
    </div>

    <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">
    <div style="flex:1 1 640px; min-width:0;">

    <!-- ===== SHARE CAPITAL LEDGER (year x month grid) ===== -->
    <div class="csm-card">
        <div class="csm-card-header">
            <h2>Share Capital Ledger</h2>
            <form method="GET" class="csm-inline-select">
                <label for="ledger_user_id">Member:</label>
                <select name="ledger_user_id" id="ledger_user_id" onchange="this.form.submit()">
                    <option value="">Select member</option>
                    <?php foreach ($members as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= $ledger_member_id === (int) $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(trim($m['fname'] . ' ' . $m['lname']) ?: $m['username']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($ledger_member_id > 0): ?>
            <?php if (empty($ledger_grid)): ?>
                <p style="color:#5a6b5f;">No monthly entries encoded yet for <?= htmlspecialchars($ledger_member_name) ?>.</p>
            <?php else: ?>
                <div class="csm-ledger-layout">
                    <div class="csm-ledger-main">
                        <div style="overflow-x:auto;">
                        <table class="csm-table">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <?php foreach ($month_labels as $ml): ?>
                                    <th><?= $ml ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ledger_grid as $year => $months): ?>
                                <tr>
                                    <td><?= htmlspecialchars($year) ?></td>
                                    <?php foreach (array_keys($month_labels) as $mkey): ?>
                                    <td>
                                        <?php if (isset($months[$mkey])): ?>
                                            ₱<?= number_format($months[$mkey]['amount'], 2) ?>
                                            <?php if ($months[$mkey]['entries'] > 1): ?>
                                                <br><span style="font-size:10.5px; color:#5a6b5f;">(<?= $months[$mkey]['entries'] ?> entries)</span>
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
                    </div>
                    <div class="csm-ledger-side">
                        <h3>Summary</h3>
                        <div class="csm-summary-row">
                            <div class="csm-summary-label">Amount (₱) — encoded months</div>
                            <div class="csm-summary-value">₱<?= number_format($ledger_period_amount_total, 2) ?></div>
                        </div>
                        <?php if (abs($ledger_legacy_amount) >= 0.01): ?>
                        <div class="csm-summary-row">
                            <div class="csm-summary-label">Legacy / untagged balance</div>
                            <div class="csm-summary-value">₱<?= number_format($ledger_legacy_amount, 2) ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="csm-summary-row">
                            <div class="csm-summary-label">Shares</div>
                            <div class="csm-summary-value"><?= number_format($ledger_member_shares, 2) ?></div>
                        </div>
                        <div class="csm-summary-row">
                            <div class="csm-summary-label">Total Amount</div>
                            <div class="csm-summary-value">₱<?= number_format($ledger_member_amount, 2) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="color:#5a6b5f;">Select a member above to view their share capital ledger.</p>
        <?php endif; ?>
    </div>

    <!-- ===== CURRENT BALANCES ===== -->
    <div class="csm-card">
        <div class="csm-card-header">
            <h2>Current Share Capital Balances</h2>
        </div>
        <div style="overflow-x:auto;">
        <table class="csm-table">
            <thead>
                <tr><th>Member</th><th>Shares</th><th>Amount</th></tr>
            </thead>
            <tbody>
                <?php if (empty($balances)): ?>
                <tr><td colspan="3">No records yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($balances as $b): ?>
                <tr>
                    <td><?= htmlspecialchars(trim($b['fname'] . ' ' . $b['lname'])) ?></td>
                    <td><?= htmlspecialchars(number_format((float) $b['total_amount'] / PAR_VALUE_PER_SHARE, 2)) ?></td>
                    <td>₱<?= htmlspecialchars(number_format((float) $b['total_amount'], 2)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- ===== AUDIT LOG ===== -->
    <div class="csm-card">
        <div class="csm-card-header">
            <h2>Recent Changes</h2>
        </div>
        <div class="csm-list">
            <div class="csm-list-row csm-list-header">
                <div>Member / Change</div>
                <div>By</div>
                <div>When</div>
            </div>
            <?php if (empty($logs)): ?>
            <div class="csm-list-row"><div>No changes logged yet.</div><div></div><div></div></div>
            <?php endif; ?>
            <?php foreach ($logs as $l): ?>
            <?php
                $parsed = scr_parse_remarks($l['remarks']);
                $period_display = $parsed ? $parsed[0] : 'Legacy entry';
                $delta_shares = (float) $l['new_shares'] - (float) $l['old_shares'];
                $delta_amount = (float) $l['new_amount'] - (float) $l['old_amount'];
                $full_name = trim($l['fname'] . ' ' . $l['lname']);
                $initials = strtoupper(mb_substr($l['fname'] ?? '', 0, 1) . mb_substr($l['lname'] ?? '', 0, 1)) ?: '—';
            ?>
            <div class="csm-list-row">
                <div class="csm-list-member">
                    <span class="csm-avatar"><?= htmlspecialchars($initials) ?></span>
                    <span>
                        <?= htmlspecialchars($full_name) ?> —
                        <strong><?= htmlspecialchars($period_display) ?></strong>
                        <br><span class="csm-list-sub"><?= $delta_shares >= 0 ? '+' : '' ?><?= htmlspecialchars(number_format($delta_shares, 2)) ?> shares, <?= $delta_amount >= 0 ? '+' : '' ?>₱<?= htmlspecialchars(number_format($delta_amount, 2)) ?><?= ($parsed && $parsed[1]) ? ' (' . htmlspecialchars($parsed[1]) . ')' : '' ?></span>
                    </span>
                </div>
                <div class="csm-list-sub">By <?= htmlspecialchars($l['changed_by_username'] ?? '—') ?></div>
                <div class="csm-list-sub"><?= htmlspecialchars(date('M d, Y g:i A', strtotime($l['changed_at']))) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    </div>

    <!-- ===== UPLOAD DOCUMENT (sidebar) ===== -->
    <div style="flex:0 0 280px; min-width:240px;">
        <div class="csm-card">
            <div class="csm-card-header">
                <h2>Upload Document</h2>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_document">
                <div class="csm-upload-box">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 16V4M12 4L7 9M12 4L17 9" stroke="#c9a63a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 16V18C4 19.1046 4.89543 20 6 20H18C19.1046 20 20 19.1046 20 18V16" stroke="#c9a63a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div class="csm-upload-title">PDF</div>
                    <div class="csm-upload-hint">Accept application/pdf, max 10MB<br>Cloudinary integrated</div>
                </div>
                <div class="csm-field" style="margin-bottom:10px;">
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
                <div class="csm-field" style="margin-bottom:10px;">
                    <label>Month / Year</label>
                    <input type="text" name="period_label" data-mask="2-4" inputmode="numeric" maxlength="7" placeholder="MM-YYYY" required>
                </div>
                <div class="csm-field" style="margin-bottom:14px;">
                    <label>PDF File</label>
                    <input type="file" name="document" accept="application/pdf" required>
                </div>
                <button type="submit" class="csm-btn-green" style="width:100%;">Upload</button>
            </form>
        </div>
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
