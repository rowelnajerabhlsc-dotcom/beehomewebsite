<?php
/**
 * inspect_untagged_entries.php
 *
 * READ-ONLY. Lists a member's capital_share_logs rows whose `remarks`
 * does NOT match the "PERIOD:MM-YYYY" tag — i.e. the legacy entries that
 * scr_build_ledger_grid() silently skips. Nothing here writes to the DB.
 *
 * Usage: upload next to your other capital-share pages, then visit:
 *   /inspect_untagged_entries.php?user_id=123
 *
 * Delete this file once you're done — it's a one-off diagnostic tool,
 * not meant to stay on the live site.
 */
session_start();
include "config.php";
include "share_capital_helpers.php";

// Same access gate as manage_capital_share.php — staff and above only.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 0) < 2) {
    header("Location: /login");
    exit();
}

$user_id = (int) ($_GET['user_id'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Untagged Capital Share Entries</title>
    <style>
        body { font-family: sans-serif; padding: 24px; color: #1e2b22; }
        h1 { color: #096D2B; font-size: 18px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; font-size: 13px; }
        th, td { border: 1px solid #e2e8e4; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f1f3f5; }
        .warn { background: #fdf6e3; border: 1px solid #f3e3b0; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
        input, button { font-size: 14px; padding: 6px 10px; }
    </style>
</head>
<body>

<h1>Untagged Capital Share Log Entries (read-only)</h1>

<form method="GET" style="margin-bottom:16px;">
    <label>User ID: <input type="number" name="user_id" value="<?= (int) $user_id ?>" required></label>
    <button type="submit">Look up</button>
</form>

<?php if ($user_id > 0): ?>
    <?php
    $rows = [];
    $stmt = $conn->prepare("
        SELECT id, old_shares, new_shares, old_amount, new_amount, remarks, changed_at, changed_by
        FROM capital_share_logs
        WHERE user_id = ?
        ORDER BY changed_at ASC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Same check scr_build_ledger_grid() uses to decide what to skip.
        if (scr_parse_remarks($row['remarks']) === null) {
            $rows[] = $row;
        }
    }
    $stmt->close();
    ?>

    <div class="warn">
        Found <strong><?= count($rows) ?></strong> untagged row(s) for user_id
        <strong><?= (int) $user_id ?></strong>. These are excluded from the
        Share Capital Ledger grid but are still counted in the running
        total_shares / total_amount balance.
    </div>

    <?php if (!empty($rows)): ?>
    <table>
        <thead>
            <tr>
                <th>Log ID</th>
                <th>Changed At</th>
                <th>Changed By (staff user_id)</th>
                <th>Old → New Shares</th>
                <th>Old → New Amount</th>
                <th>Delta Shares</th>
                <th>Delta Amount</th>
                <th>Raw remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= (int) $r['id'] ?></td>
                <td><?= htmlspecialchars($r['changed_at']) ?></td>
                <td><?= (int) $r['changed_by'] ?></td>
                <td><?= number_format((float) $r['old_shares'], 2) ?> → <?= number_format((float) $r['new_shares'], 2) ?></td>
                <td>₱<?= number_format((float) $r['old_amount'], 2) ?> → ₱<?= number_format((float) $r['new_amount'], 2) ?></td>
                <td><?= number_format((float) $r['new_shares'] - (float) $r['old_shares'], 2) ?></td>
                <td>₱<?= number_format((float) $r['new_amount'] - (float) $r['old_amount'], 2) ?></td>
                <td><code><?= $r['remarks'] === null ? '<em>NULL</em>' : htmlspecialchars($r['remarks']) ?></code></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
