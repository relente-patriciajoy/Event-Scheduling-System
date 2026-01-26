<?php
/**
 * Role Protection - Updated for RBAC
 */

// Include session management
require_once(__DIR__ . '/session.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}

require_once(__DIR__ . '/permission_functions.php');
require_once(__DIR__ . '/db.php');

$user_id = $_SESSION['user_id'];

// Get user's role name if not in session
if (!isset($_SESSION['role_name'])) {
    $user_role = getUserRole($conn, $user_id);
    $_SESSION['role_name'] = $user_role;
    $_SESSION['role'] = $user_role; // Backward compatibility
}

$user_role = $_SESSION['role_name'];

/**
 * Require specific role(s) to access page
 */
function requireRole($allowed_roles) {
    global $user_role, $conn, $user_id;

    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    if (!in_array($user_role, $allowed_roles)) {
        // Check if user has admin permission (admin can access anything)
        if (!hasPermission($conn, $user_id, 'system.settings')) {
            header("Location: ../dashboard/home.php");
            exit();
        }
    }
}

/**
 * Require specific permission to access page
 */
function requirePermission($permission_name) {
    global $conn, $user_id;
    
    if (!hasPermission($conn, $user_id, $permission_name)) {
        header("Location: ../dashboard/home.php");
        exit();
    }
}
?>