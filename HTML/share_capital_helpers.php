<?php
/**
 * share_capital_helpers.php
 *
 * The capital_share_logs table has no dedicated "period" column, and per
 * this project's schema-freeze constraint, we're not adding one. Instead,
 * each monthly entry's period (MM-YYYY) is embedded as a parseable tag at
 * the start of that log row's `remarks` field:
 *
 *     "PERIOD:03-2026|Optional staff note here"
 *
 * scr_* functions build and parse that tag consistently so no calling
 * code has to hand-roll the format. A log row's "this month's
 * contribution" is derived as (new_shares - old_shares) and
 * (new_amount - old_amount) — i.e. the delta that entry added to the
 * running total — rather than a separate stored column.
 */

/** Format a 2-digit month + 4-digit year into "MM-YYYY". */
function scr_format_period(int $month, int $year): string {
    return str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '-' . $year;
}

/** Validate a "MM-YYYY" string. Returns [month, year] ints or null if invalid. */
function scr_parse_period_input(string $period): ?array {
    if (!preg_match('/^(\d{2})-(\d{4})$/', $period, $m)) {
        return null;
    }
    $month = (int) $m[1];
    $year = (int) $m[2];
    if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
        return null;
    }
    return [$month, $year];
}

/** Build the remarks string storing the period tag + optional staff note. */
function scr_build_remarks(string $period, string $note = ''): string {
    $tag = 'PERIOD:' . $period;
    return $note !== '' ? $tag . '|' . $note : $tag;
}

/** Extract [period, note] from a remarks string, or null if untagged (legacy/non-monthly entries). */
function scr_parse_remarks(?string $remarks): ?array {
    if ($remarks === null || !preg_match('/^PERIOD:(\d{2}-\d{4})(?:\|(.*))?$/s', $remarks, $m)) {
        return null;
    }
    return [$m[1], $m[2] ?? ''];
}

/**
 * Fetch all monthly log entries for a member and check whether any
 * existing entries already cover the given period. Returns the matching
 * rows (each with its delta shares/amount) so the caller can show a
 * "this month already has N shares / ₱Y encoded" warning.
 */
function scr_find_existing_period_entries(mysqli $conn, int $user_id, string $period): array {
    $matches = [];
    $stmt = $conn->prepare("
        SELECT old_shares, new_shares, old_amount, new_amount, remarks, changed_at
        FROM capital_share_logs
        WHERE user_id = ?
        ORDER BY changed_at ASC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $parsed = scr_parse_remarks($row['remarks']);
        if ($parsed && $parsed[0] === $period) {
            $matches[] = [
                'delta_shares' => (float) $row['new_shares'] - (float) $row['old_shares'],
                'delta_amount' => (float) $row['new_amount'] - (float) $row['old_amount'],
                'note'         => $parsed[1],
                'changed_at'   => $row['changed_at'],
            ];
        }
    }
    $stmt->close();
    return $matches;
}

/**
 * Build the full year x month ledger grid for a member: 
 * ['2026' => ['01' => ['shares'=>.., 'amount'=>.., 'entries'=>N], ...], ...]
 * Only months/years with at least one entry appear.
 */
function scr_build_ledger_grid(mysqli $conn, int $user_id): array {
    $grid = [];
    $stmt = $conn->prepare("
        SELECT old_shares, new_shares, old_amount, new_amount, remarks
        FROM capital_share_logs
        WHERE user_id = ?
        ORDER BY changed_at ASC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $parsed = scr_parse_remarks($row['remarks']);
        if (!$parsed) {
            continue; // skip legacy/untagged entries — they predate monthly encoding
        }
        [$period] = $parsed;
        [$month, $year] = explode('-', $period);

        if (!isset($grid[$year])) $grid[$year] = [];
        if (!isset($grid[$year][$month])) $grid[$year][$month] = ['shares' => 0.0, 'amount' => 0.0, 'entries' => 0];

        $grid[$year][$month]['shares']  += (float) $row['new_shares'] - (float) $row['old_shares'];
        $grid[$year][$month]['amount']  += (float) $row['new_amount'] - (float) $row['old_amount'];
        $grid[$year][$month]['entries'] += 1;
    }
    $stmt->close();

    krsort($grid); // most recent year first
    return $grid;
}
