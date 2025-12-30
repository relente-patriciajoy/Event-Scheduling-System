<?php
/**
 * Role Protection System
 * Prevents role from being changed after login
 * Include this at the TOP of every protected page
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}

// Lock the original role on first load after login
if (!isset($_SESSION['original_role'])) {
    $_SESSION['original_role'] = $_SESSION['role'];
}

// CRITICAL: Prevent role tampering
// If someone tries to change the role, restore it
if (isset($_SESSION['role']) && $_SESSION['role'] !== $_SESSION['original_role']) {
    // Log security warning
    error_log("WARNING: Role change attempt detected for user ID: " . $_SESSION['user_id']);
    
    // Restore original role
    $_SESSION['role'] = $_SESSION['original_role'];
}

// Make role available globally
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';
$email = $_SESSION['email'] ?? '';

/**
 * Check if user has required role
 */
function requireRole($required_roles) {
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }
    
    if (!in_array($_SESSION['role'], $required_roles)) {
        header("Location: ../dashboard/home.php");
        exit();
    }
}

/**
 * Get the correct sidebar based on user role
 */
function getSidebarPath() {
    $role = $_SESSION['role'];
    
    if ($role === 'admin') {
        return '../admin/admin_sidebar.php';
    } else {
        return '../components/sidebar.php';
    }
}
?>