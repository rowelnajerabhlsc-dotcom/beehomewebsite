<?php
require "config.php";
require_once "permissions.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not authorized.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$caseId = (int)($input['case_id'] ?? 0);
$fields = $input['editable_fields'] ?? [];

if ($caseId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid ticket.']);
    exit();
}

/* Confirm the case exists */
$check = $conn->prepare("SELECT id FROM helpdesk_cases WHERE id = ?");
$check->bind_param("i", $caseId);
$check->execute();
if (!$check->get_result()->fetch_assoc()) {
    echo json_encode(['ok' => false, 'error' => 'Ticket not found.']);
    exit();
}

/* Next version number for this case */
$vstmt = $conn->prepare("SELECT COALESCE(MAX(version_number), 0) + 1 AS next_v FROM helpdesk_draft_versions WHERE case_id = ?");
$vstmt->bind_param("i", $caseId);
$vstmt->execute();
$nextVersion = (int)$vstmt->get_result()->fetch_assoc()['next_v'];

$fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE);
$userId = (int)$_SESSION['user_id'];
$role   = (int)$_SESSION['role'];

$conn->begin_transaction();
try {
    $ins = $conn->prepare(
        "INSERT INTO helpdesk_draft_versions (case_id, version_number, editable_fields, saved_by_user_id, saved_by_role, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $ins->bind_param("iisii", $caseId, $nextVersion, $fieldsJson, $userId, $role);
    $ins->execute();

    $upd = $conn->prepare("UPDATE helpdesk_cases SET status = 'pending_review' WHERE id = ? AND status NOT IN ('sent','closed')");
    $upd->bind_param("i", $caseId);
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
