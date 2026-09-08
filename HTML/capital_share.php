<?php
session_start();
include "config.php";
include "share_capital_helpers.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

$user_id = $_SESSION['user_id'];

// ---- Current balance ----
$total_shares = 0;
$total_amount = 0;
$last_updated = null;

$stmt = $conn->prepare("SELECT total_shares, total_amount, updated_at FROM capital_shares WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_shares, $total_amount, $last_updated);
$has_record = $stmt->fetch();
$stmt->close();

// ---- Documents, with a flag for whether this member has ever viewed/downloaded each one ----
$documents = [];
$docStmt = $conn->prepare("
    SELECT d.id, d.period_label, d.uploaded_at, d.file_deleted_at,
        EXISTS(
            SELECT 1 FROM capital_share_document_views v
            WHERE v.document_id = d.id AND v.user_id = d.user_id
        ) AS has_been_viewed
    FROM capital_share_documents d
    WHERE d.user_id = ?
    ORDER BY d.uploaded_at DESC
");
$docStmt->bind_param("i", $user_id);
$docStmt->execute();
$docResult = $docStmt->get_result();
while ($row = $docResult->fetch_assoc()) {
    $documents[] = $row;
}
$docStmt->close();

// ---- Own ledger: year x month grid ----
$ledger_grid = scr_build_ledger_grid($conn, $user_id);
$month_labels = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Share Capital</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="pv-shell">

    <div class="pv-header">
        <div class="pv-header-left">
            <div class="pv-header-info">
                <h1 style="color:#fff;">My Share Capital</h1>
                <div class="pv-header-sub">
                    <?php if ($has_record && $last_updated): ?>
                        Last updated <?= htmlspecialchars(date('F d, Y', strtotime($last_updated))) ?>
                    <?php else: ?>
                        Not yet encoded
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Current Balance</h2>
        </div>
        <div class="pv-field-grid">
            <div class="pv-field">
                <label>Total Shares</label>
                <div class="pv-value"><?= $has_record ? htmlspecialchars(number_format((float) $total_shares, 2)) : 'N/A' ?></div>
            </div>
            <div class="pv-field">
                <label>Total Amount</label>
                <div class="pv-value">₱<?= $has_record ? htmlspecialchars(number_format((float) $total_amount, 2)) : '0.00' ?></div>
            </div>
        </div>
    </div>

    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Share Capital Ledger</h2>
        </div>
        <?php if (empty($ledger_grid)): ?>
            <p style="color:#5a6b5f;">No monthly entries encoded yet.</p>
        <?php else: ?>
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
    </div>

    <div class="pv-card">
        <div class="pv-card-header">
            <span class="pv-accent"></span>
            <h2>Share Capital Documents</h2>
        </div>

        <?php if (empty($documents)): ?>
            <p style="color:#5a6b5f;">No documents have been uploaded for your account yet.</p>
        <?php else: ?>
            <div class="education-table">
                <div class="education-row education-header">
                    <div>Period</div>
                    <div>Status</div>
                    <div>Action</div>
                </div>
                <?php foreach ($documents as $doc): ?>
                <div class="education-row">
                    <div><?= htmlspecialchars($doc['period_label']) ?></div>
                    <div>
                        <?php if ($doc['file_deleted_at']): ?>
                            Downloaded &amp; removed
                        <?php elseif ($doc['has_been_viewed']): ?>
                            Opened
                        <?php else: ?>
                            Not yet opened
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($doc['file_deleted_at']): ?>
                            <span style="color:#5a6b5f;">No longer available</span>
                        <?php else: ?>
                            <a href="/capital_share_doc_view?id=<?= (int) $doc['id'] ?>&action=view" target="_blank">View</a>
                            &nbsp;|&nbsp;
                            <a href="/capital_share_doc_view?id=<?= (int) $doc['id'] ?>&action=download">Download</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
