<?php
require "config.php";
require_once "permissions.php";
require_once "helpdesk_draft_generator.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not authorized.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$caseId = (int)($input['case_id'] ?? 0);
$clientFields = $input['editable_fields'] ?? [];

if ($caseId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid ticket.']);
    exit();
}

/* Only classification and sla_days are ever taken from the client.
   staff_name and contact details are set server-side, never trusted
   from the request body. */
$classification = ($clientFields['classification'] ?? '') === 'Complex' ? 'Complex' : 'Simple';
$slaDays = (int)($clientFields['sla_days'] ?? 15);
if ($slaDays < 1) { $slaDays = 1; }

$fields = [
    'classification' => $classification,
    'sla_days'       => $slaDays,
    'contact_person' => HELPDESK_FIXED_CONTACT_PERSON,
    'contact_info'   => HELPDESK_FIXED_CONTACT_INFO,
    'staff_name'     => $_SESSION['username'] ?? '',
];

/* Confirm the case exists */
$check = $conn->prepare("SELECT * FROM helpdesk_cases WHERE id = ?");
$check->bind_param("i", $caseId);
$check->execute();
$case = $check->get_result()->fetch_assoc();
if (!$case) {
    echo json_encode(['ok' => false, 'error' => 'Ticket not found.']);
    exit();
}

/* Next version number for this case */
$vstmt = $conn->prepare("SELECT COALESCE(MAX(version_number), 0) + 1 AS next_v FROM helpdesk_draft_versions WHERE case_id = ?");
$vstmt->bind_param("i", $caseId);
$vstmt->execute();
$nextVersion = (int)$vstmt->get_result()->fetch_assoc()['next_v'];

$bodySnapshot = renderCaseEmailBody($case, $fields);
$fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE);
$userId = (int)$_SESSION['user_id'];
$role   = (int)$_SESSION['role'];

$conn->begin_transaction();
try {
    $ins = $conn->prepare(
        "INSERT INTO helpdesk_draft_versions (case_id, version_number, editable_fields, full_body_snapshot, saved_by_user_id, saved_by_role, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())"
    );
    $ins->bind_param("iissii", $caseId, $nextVersion, $fieldsJson, $bodySnapshot, $userId, $role);
    $ins->execute();

    $upd = $conn->prepare("UPDATE helpdesk_cases SET status = 'pending_review', email_body_draft = ? WHERE id = ? AND status NOT IN ('sent','closed')");
    $upd->bind_param("si", $bodySnapshot, $caseId);
    $upd->execute();

    $log = $conn->prepare(
        "INSERT INTO helpdesk_audit_log (case_id, action, user_id, user_role, detail, created_at)
         VALUES (?, 'draft_saved', ?, ?, ?, NOW())"
    );
    $detail = "Saved draft version {$nextVersion}";
    $log->bind_param("iiis", $caseId, $userId, $role, $detail);
    $log->execute();

    $conn->commit();
    echo json_encode(['ok' => true, 'version' => $nextVersion]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("helpdesk_case_save failed for case {$caseId}: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Could not save the draft. Please try again.']);
}
