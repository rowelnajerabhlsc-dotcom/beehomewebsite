<?php
session_start();
include "config.php";
include "permissions.php";

/* ACCESS CONTROL - Require at least Manager role */
require_role(3);

/* Additional check for deletion permissions:
   - Admin (4) can delete anyone except themselves
   - Manager (3) can only delete Users (1) and Staff (2), not other Managers or Admins
   - Neither can delete themselves */

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

/* CHECK ID */
if (!isset($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$user_id = (int) $_GET['id'];

/* Prevent self-delete */
if ($user_id === $current_user_id) {
    header("Location: records.php");
    exit();
}

/* Get target user's role to check if current user is allowed to delete them */
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // User not found
    header("Location: records.php");
    exit();
}

$stmt->bind_result($target_role);
$stmt->fetch();
$stmt->close();

/* Permission check based on role hierarchy */
$can_delete = false;

// Admin can delete anyone except themselves (already checked above)
if ($current_user_role >= 4) {
    $can_delete = true;
}
// Manager can only delete Users (1) and Staff (2)
elseif ($current_user_role == 3 && $target_role < 3) {
    $can_delete = true;
}

if (!$can_delete) {
    // Not authorized to delete this user
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