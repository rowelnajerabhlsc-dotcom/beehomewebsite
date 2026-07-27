<?php
session_start();
include "config.php";
include "permissions.php";

/* ACCESS CONTROL */
require_role(3); // must be at least Manager to reach this page
/* CHECK ID */
if (!isset($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$user_id = $_GET['id'];

/* PREVENT SELF DELETE */
if ($user_id == $_SESSION['user_id']) {
    header("Location: records.php");
    exit();
}

/* ROLE-BASED TARGET CHECK */
$target_role = get_user_role_by_id($conn, $user_id);

if ($target_role === null) {
    header("Location: records.php");
    exit();
}

if (!can_manage_target($_SESSION['role'], $target_role)) {
    header("Location: records.php");
    exit();
}

/* DELETE USER (CASCADE handles profile) */
$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

header("Location: records.php");
exit();
?>