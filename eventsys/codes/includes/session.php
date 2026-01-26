<?php
/**
 * Session Verification & Timeout Management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    $_SESSION = array();
    session_destroy();
    header("Location: ../auth/index.php");
    exit();
}

// Verify essential session variables exist
$required_session_vars = ['user_id', 'email', 'full_name', 'role'];
foreach ($required_session_vars as $var) {
    if (!isset($_SESSION[$var])) {
        session_destroy();
        header("Location: ../auth/index.php?error=session_invalid");
        exit();
    }
}

// Session timeout check (1 hour)
$timeout_duration = 3600;
$current_time = time();

if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = $current_time;
}

$last_activity = $_SESSION['login_time'];

if (($current_time - $last_activity) > $timeout_duration) {
    $_SESSION = array();
    session_destroy();
    header("Location: ../auth/index.php?error=session_expired");
    exit();
}

$_SESSION['login_time'] = $current_time;

// Verify role is valid
$valid_roles = ['user', 'event_head', 'admin'];
if (!in_array($_SESSION['role'], $valid_roles)) {
    error_log("SECURITY: Invalid role detected for user ID: " . $_SESSION['user_id']);
    session_destroy();
    header("Location: ../auth/index.php?error=invalid_role");
    exit();
}

$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];

?>