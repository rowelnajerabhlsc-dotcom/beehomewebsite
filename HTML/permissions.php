<?php
/**
 * Permissions helper functions for role-based access control
 *
 * Role hierarchy: 1 = User, 2 = Staff, 3 = Manager, 4 = Admin
 * Higher numbers indicate higher privilege levels
 */

/**
 * Check if user has at least the minimum required role
 * @param int $min_role Minimum role required (higher number = more privileged)
 * @return bool True if user has sufficient role, false otherwise
 */
function require_role($min_role) {
    session_start(); // Ensure session is started

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        $_SESSION['must_login'] = true;
        header("Location: /login");
        exit();
    }

    if ((int)$_SESSION['role'] < $min_role) {
        header("Location: /");
        exit();
    }

    return true;
}

/**
 * Check if current user can manage a target user based on role hierarchy
 * Admin (4) can manage anyone
 * Manager (3) can only manage users with role < 3 (Users and Staff)
 * Staff/User cannot manage any users
 *
 * @param int|null $target_role Role of the target user (null for current user)
 * @return bool True if current user can manage the target user
 */
function can_manage_target($target_role = null) {
    session_start(); // Ensure session is started

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return false;
    }

    $user_role = (int)$_SESSION['role'];

    // Admin can manage anyone
    if ($user_role >= 4) {
        return true;
    }

    // Manager can only manage Users and Staff (roles < 3)
    if ($user_role == 3) {
        // If no target role specified, assume they're trying to manage themselves or get list
        // Managers can view their own profile and lists, but not edit/delete others above Staff
        if ($target_role === null) {
            return true; // Allow viewing own profile/list
        }
        return $target_role < 3; // Can only manage Users(1) and Staff(2)
    }

    // Staff and Users cannot manage other users
    return false;
}

/**
 * Check if current user is an Admin (role 4)
 * @return bool True if user is admin
 */
function is_admin() {
    session_start(); // Ensure session is started
    return isset($_SESSION['role']) && (int)$_SESSION['role'] >= 4;
}

/**
 * Check if current user is a Manager or Admin (role 3 or 4)
 * @return bool True if user is manager or admin
 */
function is_manager_or_admin() {
    session_start(); // Ensure session is started
    return isset($_SESSION['role']) && (int)$_SESSION['role'] >= 3;
}

/**
 * Get user role name for display
 * @param int $role Role ID
 * @return string Role name
 */
function get_role_name($role) {
    switch ((int)$role) {
        case 1: return 'User';
        case 2: return 'Staff';
        case 3: return 'Manager';
        case 4: return 'Administrator';
        default: return 'Unknown';
    }
}
?>