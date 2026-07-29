<?php
require "config.php";
require_once "permissions.php";
require_once "helpdesk_sheets_import.php";

/* Role 3 (Manager) and Role 4 (Administrator) only */
require_role(3);

/* ------------------------------------------------------------
   Check the Sheet for new submissions on every page load.
   Silent on success; import errors are shown so an admin notices
   if the API/service account access breaks.
   ------------------------------------------------------------ */
$importResult = importNewSubmissionsFromSheet($conn);

/* ------------------------------------------------------------
   Filters (GET params, all optional)
   ------------------------------------------------------------ */
$statusFilter = $_GET['status'] ?? 'open';   // 'open' = default view, or a specific status, or 'all'
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to'] ?? '';
$search       = trim($_GET['search'] ?? '');

$where  = [];
$params = [];
$types  = '';

if ($statusFilter === 'open') {
    $where[] = "status NOT IN ('sent', 'closed')";
} elseif ($statusFilter !== 'all') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}

if ($dateFrom !== '') {
    $where[] = "created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
    $types   .= 's';
}
if ($dateTo !== '') {
    $where[] = "created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
    $types   .= 's';
}
if ($search !== '') {
    $where[] = "(member_name LIKE ? OR reference_number LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

$sql = "SELECT id, reference_number, member_name, project_location, request_type, status, created_at
        FROM helpdesk_cases";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$cases = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Consumer Assistance Dashboard</title>
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" href="IMAGES/logo.png">
    <style>
        :root {
            --primary: #096D2B;
            --secondary: #26a753;
            --accent: #2cab4a;
            --bg-light: #f9f9f9;
            --border-soft: rgba(9, 109, 43, 0.3);
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --ease: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: var(--bg-light);
            color: #1c2b21;
            margin: 0;
        }

        .hd-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px 60px;
        }

        h1 {
            font-family: "Fredoka", "Trebuchet MS", sans-serif;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.8em;
            margin-bottom: 4px;
        }

        .hd-subtitle {
            color: #4a5c50;
            margin-bottom: 24px;
            font-weight: 500;
        }

        /* ---------- Filter bar ---------- */
        .hd-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            background: #fff;
            border: 1px solid var(--border-soft);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
        }

        .hd-filters label {
            display: block;
            font-size: 0.78em;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .hd-filters input,
        .hd-filters select {
            padding: 9px 12px;
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 0.9em;
        }

        .hd-filters input:focus,
        .hd-filters select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .hd-filters button {
            padding: 10px 22px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.25s var(--ease);
        }
        .hd-filters button:hover { background: var(--secondary); }

        /* ---------- Ticket grid ---------- */
        .hd-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .hd-card {
            background: #fff;
            border: 1px solid var(--border-soft);
            border-radius: 10px;
            padding: 18px;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: transform 0.25s var(--ease), box-shadow 0.25s var(--ease);
        }
        .hd-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(9, 109, 43, 0.18);
        }

        .hd-card-ref {
            font-family: "Fredoka", "Trebuchet MS", sans-serif;
            font-weight: 700;
            color: var(--primary);
            font-size: 0.95em;
            margin-bottom: 6px;
        }

        .hd-card-name {
            font-weight: 700;
            font-size: 1.05em;
            margin-bottom: 4px;
        }

        .hd-card-meta {
            font-size: 0.85em;
            color: #5a6b60;
            margin-bottom: 3px;
        }

        .hd-badges {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .hd-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75em;
            font-weight: 600;
        }

        .hd-badge.type-Complaint { background: #fdeaea; color: #a12626; }
        .hd-badge.type-Request   { background: #eaf3fd; color: #1c5a99; }
        .hd-badge.type-Inquiry   { background: #eef7ee; color: var(--primary); }

        .hd-badge.status-new              { background: #f0f0f0; color: #555; }
        .hd-badge.status-draft_generated  { background: #fff6e0; color: #8a6d00; }
        .hd-badge.status-pending_review   { background: #fff0e0; color: #a15c00; }
        .hd-badge.status-approved         { background: #e6f7ec; color: var(--primary); }
        .hd-badge.status-sent             { background: #e0f5e6; color: #0d6b2f; }
        .hd-badge.status-failed           { background: #fdeaea; color: #a12626; }
        .hd-badge.status-closed           { background: #eee; color: #777; }

        .hd-empty {
            text-align: center;
            padding: 60px 20px;
            color: #6b7c70;
        }

        /* ---------- Modal ---------- */
        .hd-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(9, 109, 43, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .hd-modal-overlay.active { display: flex; }

        .hd-modal {
            background: #fff;
            border-radius: 10px;
            max-width: 720px;
            width: 100%;
            max-height: 88vh;
            overflow-y: auto;
            padding: 28px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.25);
        }

        .hd-modal h2 {
            font-family: "Fredoka", "Trebuchet MS", sans-serif;
            color: var(--primary);
            margin-top: 0;
        }

        .hd-modal-section {
            margin-bottom: 20px;
        }

        .hd-modal-section h3 {
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--secondary);
            margin-bottom: 8px;
        }

        .hd-readonly-block {
            background: var(--bg-light);
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            padding: 14px;
            font-size: 0.9em;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .hd-editable-fields {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .hd-editable-fields label {
            font-size: 0.8em;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 4px;
            display: block;
        }

        .hd-editable-fields input,
        .hd-editable-fields textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 0.9em;
        }

        .hd-field-group {
            margin-bottom: 4px;
        }

        .hd-field-group label {
            font-size: 0.8em;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 6px;
            display: block;
        }

        .hd-radio-row {
            display: flex;
            gap: 20px;
        }

        .hd-radio-option {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9em;
            font-weight: 500;
            color: #333;
            cursor: pointer;
        }

        .hd-radio-option input[type="radio"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .hd-editable-fields input[type="number"] {
            max-width: 140px;
        }

        .hd-fixed-value {
            background: var(--bg-light);
            border: 1px dashed var(--border-soft);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.9em;
            color: #5a6b60;
        }

        .hd-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 24px;
            border-top: 1px solid var(--border-soft);
            padding-top: 18px;
        }

        .hd-btn {
            padding: 10px 22px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.25s var(--ease), opacity 0.25s var(--ease);
        }
        .hd-btn-cancel  { background: #eee; color: #444; }
        .hd-btn-cancel:hover { background: #e0e0e0; }
        .hd-btn-save    { background: var(--secondary); color: #fff; }
        .hd-btn-save:hover { background: var(--accent); }
        .hd-btn-submit  { background: var(--primary); color: #fff; }
        .hd-btn-submit:hover { background: var(--secondary); }
        .hd-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .hd-modal-msg {
            font-size: 0.85em;
            margin-top: 10px;
            padding: 8px 12px;
            border-radius: 6px;
            display: none;
        }
        .hd-modal-msg.error   { background: #fdeaea; color: #a12626; display: block; }
        .hd-modal-msg.success { background: #eaf7ec; color: var(--primary); display: block; }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="hd-wrap">
    <h1>Consumer Assistance Dashboard</h1>
    <p class="hd-subtitle">Member requests imported from Google Forms, ready for review.</p>

    <?php if (!empty($importResult['errors'])): ?>
        <div class="hd-empty" style="text-align:left; background:#fdeaea; color:#a12626; border-radius:8px; padding:14px; margin-bottom:20px;">
            <strong>Sheet import issue:</strong>
            <ul style="margin:6px 0 0 18px;">
                <?php foreach ($importResult['errors'] as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif (!empty($importResult['imported'])): ?>
        <div class="hd-empty" style="text-align:left; background:#eaf7ec; color:#096D2B; border-radius:8px; padding:14px; margin-bottom:20px;">
            Imported <?php echo (int)$importResult['imported']; ?> new submission(s) from the Sheet.
        </div>
    <?php endif; ?>

    <form class="hd-filters" method="GET">
        <div>
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="open" <?php echo $statusFilter === 'open' ? 'selected' : ''; ?>>Open / Unresolved</option>
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>New</option>
                <option value="draft_generated" <?php echo $statusFilter === 'draft_generated' ? 'selected' : ''; ?>>Draft Generated</option>
                <option value="pending_review" <?php echo $statusFilter === 'pending_review' ? 'selected' : ''; ?>>Pending Review</option>
                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="sent" <?php echo $statusFilter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        <div>
            <label for="date_from">From</label>
            <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
        </div>
        <div>
            <label for="date_to">To</label>
            <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
        </div>
        <div>
            <label for="search">Search (name or reference #)</label>
            <input type="text" name="search" id="search" placeholder="e.g. Dela Cruz or BH-2026..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit">Apply</button>
    </form>

    <?php if (empty($cases)): ?>
        <div class="hd-empty">No tickets match this view. Try adjusting the filters above.</div>
    <?php else: ?>
        <div class="hd-grid">
            <?php foreach ($cases as $c): ?>
                <div class="hd-card" data-case-id="<?php echo (int)$c['id']; ?>">
                    <div class="hd-card-ref"><?php echo htmlspecialchars($c['reference_number']); ?></div>
                    <div class="hd-card-name"><?php echo htmlspecialchars($c['member_name']); ?></div>
                    <div class="hd-card-meta">Submitted: <?php echo date('M j, Y g:i A', strtotime($c['created_at'])); ?></div>
                    <div class="hd-card-meta">Location: <?php echo htmlspecialchars($c['project_location'] ?: '—'); ?></div>
                    <div class="hd-badges">
                        <span class="hd-badge type-<?php echo htmlspecialchars($c['request_type']); ?>"><?php echo htmlspecialchars($c['request_type']); ?></span>
                        <span class="hd-badge status-<?php echo htmlspecialchars($c['status']); ?>"><?php echo ucwords(str_replace('_', ' ', $c['status'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ================= Modal ================= -->
<div class="hd-modal-overlay" id="hdModalOverlay">
    <div class="hd-modal" id="hdModal" data-lenis-prevent>
        <div id="hdModalBody">Loading…</div>
    </div>
</div>

<script>
const overlay = document.getElementById('hdModalOverlay');
const modalBody = document.getElementById('hdModalBody');
let currentCaseId = null;

document.querySelectorAll('.hd-card').forEach(card => {
    card.addEventListener('click', () => openCase(card.dataset.caseId));
});

function closeModal() {
    overlay.classList.remove('active');
    modalBody.innerHTML = '';
    currentCaseId = null;
    document.body.style.overflow = '';
}

overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal();
});

async function openCase(caseId) {
    currentCaseId = caseId;
    modalBody.innerHTML = 'Loading…';
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    try {
        const res = await fetch(`/HTML/helpdesk_case_detail.php?id=${caseId}`);
        const data = await res.json();
        if (!data.ok) {
            modalBody.innerHTML = `<div class="hd-modal-msg error">${data.error || 'Could not load this ticket.'}</div>`;
            return;
        }
        renderModal(data.case, data.draft, {
            current_user_name: data.current_user_name,
            fixed_contact_person: data.fixed_contact_person,
            fixed_contact_info: data.fixed_contact_info
        });
    } catch (err) {
        modalBody.innerHTML = '<div class="hd-modal-msg error">Connection error. Please try again.</div>';
    }
}

function renderModal(c, draft, meta) {
    const fields = (draft && draft.editable_fields) ? draft.editable_fields : {};
    const classification = fields.classification === 'Complex' ? 'Complex' : 'Simple';
    const slaDays = fields.sla_days || 15;

    modalBody.innerHTML = `
        <h2>${escapeHtml(c.reference_number)}</h2>

        <div class="hd-modal-section">
            <h3>Consumer Information</h3>
            <div class="hd-readonly-block">
Name: ${escapeHtml(c.member_name)}
Email: ${escapeHtml(c.member_email)}
Contact: ${escapeHtml(c.member_contact || '—')}
Location: ${escapeHtml(c.project_location || '—')}
Type: ${escapeHtml(c.request_type)}
            </div>
        </div>

        <div class="hd-modal-section">
            <h3>Original Concern</h3>
            <div class="hd-readonly-block">${escapeHtml(c.request_details || '—')}</div>
        </div>

        <div class="hd-modal-section">
            <h3>Email Draft (read-only)</h3>
            <div class="hd-readonly-block">${escapeHtml(draft && draft.full_body_snapshot ? draft.full_body_snapshot : (c.email_body_draft || 'No draft generated yet.'))}</div>
        </div>

        <div class="hd-modal-section">
            <h3>Editable Fields</h3>
            <div class="hd-editable-fields">

                <div class="hd-field-group">
                    <label>Classification</label>
                    <div class="hd-radio-row">
                        <label class="hd-radio-option">
                            <input type="radio" name="ef_classification" value="Simple" ${classification === 'Simple' ? 'checked' : ''}>
                            Simple
                        </label>
                        <label class="hd-radio-option">
                            <input type="radio" name="ef_classification" value="Complex" ${classification === 'Complex' ? 'checked' : ''}>
                            Complex
                        </label>
                    </div>
                </div>

                <div class="hd-field-group">
                    <label for="ef_sla_days">Timeline (SLA) — days</label>
                    <input type="number" id="ef_sla_days" min="1" step="1" value="${escapeHtml(String(slaDays))}"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>

                <div class="hd-field-group">
                    <label>Staff Name (auto-filled)</label>
                    <div class="hd-fixed-value">${escapeHtml(meta.current_user_name || 'Unknown user')}</div>
                </div>

                <div class="hd-field-group">
                    <label>Contact Person (fixed)</label>
                    <div class="hd-fixed-value">${escapeHtml(meta.fixed_contact_person)}</div>
                </div>

                <div class="hd-field-group">
                    <label>Contact Number (fixed)</label>
                    <div class="hd-fixed-value">${escapeHtml(meta.fixed_contact_info)}</div>
                </div>

            </div>
        </div>

        <div id="hdModalMsg" class="hd-modal-msg"></div>

        <div class="hd-modal-actions">
            <button class="hd-btn hd-btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="hd-btn hd-btn-save" onclick="saveDraft()">Save</button>
            <button class="hd-btn hd-btn-submit" onclick="submitCase()">Submit</button>
        </div>
    `;
}

function collectEditableFields() {
    const classification = document.querySelector('input[name="ef_classification"]:checked')?.value || 'Simple';
    const slaDays = parseInt(document.getElementById('ef_sla_days')?.value, 10) || 1;
    return { classification, sla_days: slaDays };
}

function showMsg(text, isError) {
    const msg = document.getElementById('hdModalMsg');
    msg.textContent = text;
    msg.className = 'hd-modal-msg ' + (isError ? 'error' : 'success');
}

async function saveDraft() {
    const fields = collectEditableFields();
    try {
        const res = await fetch('/HTML/helpdesk_case_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ case_id: currentCaseId, editable_fields: fields })
        });
        const data = await res.json();
        showMsg(data.ok ? 'Draft saved.' : (data.error || 'Could not save draft.'), !data.ok);
    } catch (err) {
        showMsg('Connection error while saving.', true);
    }
}

async function submitCase() {
    const fields = collectEditableFields();
    if (!confirm('Send this email to the member now?')) return;

    try {
        const res = await fetch('/HTML/helpdesk_case_submit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ case_id: currentCaseId, editable_fields: fields })
        });
        const data = await res.json();
        if (data.ok) {
            showMsg('Email sent to member.', false);
            setTimeout(() => { closeModal(); window.location.reload(); }, 1200);
        } else {
            showMsg(data.error || 'Could not send this email. Draft has been kept.', true);
        }
    } catch (err) {
        showMsg('Connection error while sending.', true);
    }
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}
</script>

</body>
</html>
