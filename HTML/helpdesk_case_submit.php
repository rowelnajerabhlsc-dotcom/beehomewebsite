<?php
require "config.php";
require_once "permissions.php";
require_once "helpdesk_mailer.php";
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
$userId = (int)$_SESSION['user_id'];
$role   = (int)$_SESSION['role'];

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

$staffName = $_SESSION['username'] ?? '';

$fields = [
    'classification' => $classification,
    'sla_days'       => $slaDays,
    'contact_person' => HELPDESK_FIXED_CONTACT_PERSON,
    'contact_info'   => HELPDESK_FIXED_CONTACT_INFO,
    'staff_name'     => $staffName,
];

$stmt = $conn->prepare("SELECT * FROM helpdesk_cases WHERE id = ?");
$stmt->bind_param("i", $caseId);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();

if (!$case) {
    echo json_encode(['ok' => false, 'error' => 'Ticket not found.']);
    exit();
}

if ($case['status'] === 'sent') {
    echo json_encode(['ok' => false, 'error' => 'This email has already been sent.']);
    exit();
}

if (empty($case['member_email']) || !filter_var($case['member_email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Member email on file is missing or invalid.']);
    exit();
}

/* Merge admin-edited fields into the template to produce the final body. */
$finalBody = renderCaseEmailBody($case, $fields);

if (empty($staffName)) {
    echo json_encode(['ok' => false, 'error' => 'Could not identify the logged-in staff member. Please log in again.']);
    exit();
}

$subject = $case['email_subject'] ?: "Update on your request — {$case['reference_number']}";

/* Save this as a version + snapshot before attempting to send, so the
   attempt is preserved even if delivery fails. */
$vstmt = $conn->prepare("SELECT COALESCE(MAX(version_number), 0) + 1 AS next_v FROM helpdesk_draft_versions WHERE case_id = ?");
$vstmt->bind_param("i", $caseId);
$vstmt->execute();
$nextVersion = (int)$vstmt->get_result()->fetch_assoc()['next_v'];
$fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE);

$ins = $conn->prepare(
    "INSERT INTO helpdesk_draft_versions (case_id, version_number, editable_fields, full_body_snapshot, saved_by_user_id, saved_by_role, created_at)
     VALUES (?, ?, ?, ?, ?, ?, NOW())"
);
$ins->bind_param("iissii", $caseId, $nextVersion, $fieldsJson, $finalBody, $userId, $role);
$ins->execute();

$sendOk = sendCaseEmail($case['member_email'], $subject, nl2br(htmlspecialchars($finalBody)), $case['reference_number']);

if ($sendOk) {
    $upd = $conn->prepare(
        "UPDATE helpdesk_cases
         SET status = 'sent', email_body_final = ?, email_sent_at = NOW(), email_send_error = NULL
         WHERE id = ?"
    );
    $upd->bind_param("si", $finalBody, $caseId);
    $upd->execute();

    $log = $conn->prepare(
        "INSERT INTO helpdesk_audit_log (case_id, action, user_id, user_role, detail, created_at)
         VALUES (?, 'draft_submitted', ?, ?, ?, NOW())"
    );
    $detail = "Sent to {$case['member_email']}";
    $log->bind_param("iiis", $caseId, $userId, $role, $detail);
    $log->execute();

    echo json_encode(['ok' => true]);
} else {
    $errMsg = 'Delivery failed. Check mail configuration.';

    $upd = $conn->prepare("UPDATE helpdesk_cases SET email_send_error = ? WHERE id = ?");
    $upd->bind_param("si", $errMsg, $caseId);
    $upd->execute();

    $log = $conn->prepare(
        "INSERT INTO helpdesk_audit_log (case_id, action, user_id, user_role, detail, created_at)
         VALUES (?, 'submit_failed', ?, ?, ?, NOW())"
    );
    $log->bind_param("iiis", $caseId, $userId, $role, $errMsg);
    $log->execute();

    /* Status is deliberately NOT changed on failure — draft is preserved. */
    echo json_encode(['ok' => false, 'error' => 'Could not send the email. The draft has been saved — try again or check mail configuration.']);
}
