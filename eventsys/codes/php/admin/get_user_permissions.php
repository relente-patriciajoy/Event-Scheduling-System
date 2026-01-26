<?php
require_once('../../includes/session.php');
require_once('../../includes/role_protection.php');
requireRole('admin');

require_once('../../includes/db.php');
require_once('../../includes/permission_functions.php');

header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit();
}

// Get ALL permissions for this user (role-based + custom overrides)
$all_permissions_query = $conn->query("SELECT permission_name FROM permission");
$effective_permissions = [];

while ($perm = $all_permissions_query->fetch_assoc()) {
    $perm_name = $perm['permission_name'];
    // Use hasPermission to check effective permission (considers role + overrides)
    if (hasPermission($conn, $user_id, $perm_name)) {
        $effective_permissions[] = $perm_name;
    }
}

echo json_encode([
    'success' => true,
    'permissions' => $effective_permissions
]);
?>