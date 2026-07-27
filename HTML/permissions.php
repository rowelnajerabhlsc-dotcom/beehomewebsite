<?php
/**
 * permissions.php
 * Centralized role-based access control helpers.
 * Roles: 1 = Regular User, 2 = Staff, 3 = Manager, 4 = Administrator
 * Requires: session already started, $conn (mysqli) already available.
 */

function require_role($min_role) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < $min_role) {
        header("Location: home.php");
        exit();
    }
}

function can_manage_target($actor_role, $target_role) {
    if ($actor_role == 4) {
        return true; // Admin can manage anyone
    }
    if ($actor_role == 3) {
        return $target_role < 3; // Manager can only manage roles below Manager
    }
    return false;
}

function get_user_role_by_id($conn, $user_id) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();
    return $role !== null ? (int)$role : null;
}