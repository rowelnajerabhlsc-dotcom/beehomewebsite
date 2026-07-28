<?php
require "config.php";
require_once "permissions.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not authorized.']);
    exit();
}

$caseId = (int)($_GET['id'] ?? 0);
if ($caseId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid ticket.']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM helpdesk_cases WHERE id = ?");
$stmt->bind_param("i", $caseId);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();

if (!$case) {
    echo json_encode(['ok' => false, 'error' => 'Ticket not found.']);
    exit();
}

/* Latest draft version, if any */
$dstmt = $conn->prepare(
    "SELECT editable_fields, full_body_snapshot, version_number
     FROM helpdesk_draft_versions
     WHERE case_id = ?
     ORDER BY version_number DESC
     LIMIT 1"
);
$dstmt->bind_param("i", $caseId);
$dstmt->execute();
$draftRow = $dstmt->get_result()->fetch_assoc();

$draft = null;
if ($draftRow) {
    $draft = [
        'editable_fields'    => json_decode($draftRow['editable_fields'], true) ?: new stdClass(),
        'full_body_snapshot' => $draftRow['full_body_snapshot'],
        'version_number'     => (int)$draftRow['version_number'],
    ];
}

echo json_encode(['ok' => true, 'case' => $case, 'draft' => $draft]);
