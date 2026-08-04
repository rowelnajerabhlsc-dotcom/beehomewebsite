<?php
/**
 * JSON endpoint powering the "Recent User Login Activity" (search + pagination)
 * and "Recent Helpdesk Audit Activity" (pagination) tables on dashboard.php.
 *
 * GET params:
 *   type   = 'logins' | 'audit'   (required)
 *   page   = 1-based page number  (default 1)
 *   search = free text, 'logins' only — matches email, username, IP, event type
 */

session_start();
include "config.php";
include "permissions.php";

require_role(3);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] == 4;

header('Content-Type: application/json');

// Same data both tables show is admin-only on the dashboard itself,
// so enforce that here too rather than trusting the caller.
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$perPage = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $perPage;

$type = $_GET['type'] ?? '';

if ($type === 'logins') {

    $search = trim($_GET['search'] ?? '');

    $where = '';
    $params = [];
    $types = '';

    if ($search !== '') {
        $where = "WHERE ul.email LIKE ? OR u.username LIKE ? OR ul.ip_address LIKE ? OR ul.event_type LIKE ?";
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like];
        $types = 'ssss';
    }

    $countSql = "SELECT COUNT(*) c
                 FROM user_logs ul
                 LEFT JOIN users u ON ul.user_id = u.id
                 $where";
    $countStmt = $conn->prepare($countSql);
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int) $countStmt->get_result()->fetch_assoc()['c'];
    $countStmt->close();

    $sql = "SELECT ul.event_type, ul.email, ul.ip_address, ul.created_at, u.username
            FROM user_logs ul
            LEFT JOIN users u ON ul.user_id = u.id
            $where
            ORDER BY ul.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $bindTypes = $types . 'ii';
    $bindParams = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'rows' => $rows,
        'page' => $page,
        'totalPages' => max(1, (int) ceil($total / $perPage)),
        'total' => $total,
    ]);
    exit;
}

if ($type === 'audit') {

    $total = (int) $conn->query("SELECT COUNT(*) c FROM helpdesk_audit_log")->fetch_assoc()['c'];

    $sql = "SELECT a.action, a.user_role, a.created_at, c.reference_number
            FROM helpdesk_audit_log a
            LEFT JOIN helpdesk_cases c ON a.case_id = c.id
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'rows' => $rows,
        'page' => $page,
        'totalPages' => max(1, (int) ceil($total / $perPage)),
        'total' => $total,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid type']);
