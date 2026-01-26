<?php
/**
 * Permission Functions - RBAC System
 */

/**
 * Check if user has a specific permission
 */
function hasPermission($conn, $user_id, $permission_name) {
    // Check user-specific override first
    $stmt = $conn->prepare("
        SELECT granted 
        FROM user_permission up
        JOIN permission p ON up.permission_id = p.permission_id
        WHERE up.user_id = ? AND p.permission_name = ?
    ");
    $stmt->bind_param("is", $user_id, $permission_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return (bool)$row['granted'];
    }
    $stmt->close();
    
    // Check role-based permissions
    $stmt = $conn->prepare("
        SELECT COUNT(*) as has_permission
        FROM user u
        JOIN role_permission rp ON u.role_id = rp.role_id
        JOIN permission p ON rp.permission_id = p.permission_id
        WHERE u.user_id = ? AND p.permission_name = ?
    ");
    $stmt->bind_param("is", $user_id, $permission_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['has_permission'] > 0;
}

/**
 * Get user's role name
 */
function getUserRole($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT r.role_name 
        FROM user u
        JOIN role r ON u.role_id = r.role_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['role_name'];
    }
    $stmt->close();
    return null;
}

/**
 * Check if user can access a specific event
 */
function canAccessEvent($conn, $user_id, $event_id, $action = 'view') {
    // Admin can access everything
    if (hasPermission($conn, $user_id, 'system.settings')) {
        return true;
    }
    
    // Check event-specific access
    $column_map = [
        'view' => 'can_view',
        'edit' => 'can_edit',
        'delete' => 'can_delete',
        'manage_attendance' => 'can_manage_attendance',
        'export_data' => 'can_export_data'
    ];
    
    if (!isset($column_map[$action])) {
        return false;
    }
    
    $column = $column_map[$action];
    
    $stmt = $conn->prepare("
        SELECT $column 
        FROM event_access 
        WHERE user_id = ? AND event_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return (bool)$row[$column];
    }
    $stmt->close();
    
    // Check if user owns the event
    $stmt = $conn->prepare("
        SELECT COUNT(*) as owns_event
        FROM event e
        JOIN organizer o ON e.organizer_id = o.organizer_id
        JOIN user u ON o.contact_email = u.email
        WHERE u.user_id = ? AND e.event_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['owns_event'] > 0) {
        // Check general permission for own events
        $permission_map = [
            'view' => 'event.view.own',
            'edit' => 'event.edit.own',
            'delete' => 'event.delete.own',
            'manage_attendance' => 'attendance.mark',
            'export_data' => 'attendance.export'
        ];
        
        return hasPermission($conn, $user_id, $permission_map[$action]);
    }
    
    // Check general permission for all events
    $general_permission_map = [
        'view' => 'event.view.all',
        'edit' => 'event.edit.all',
        'delete' => 'event.delete.all',
        'manage_attendance' => 'attendance.view.all',
        'export_data' => 'attendance.export'
    ];
    
    return hasPermission($conn, $user_id, $general_permission_map[$action]);
}

/**
 * Grant permission to a user
 */
function grantPermission($conn, $user_id, $permission_name, $granted_by, $reason = '') {
    // Get permission ID
    $stmt = $conn->prepare("SELECT permission_id FROM permission WHERE permission_name = ?");
    $stmt->bind_param("s", $permission_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }
    
    $row = $result->fetch_assoc();
    $permission_id = $row['permission_id'];
    $stmt->close();
    
    // Insert or update
    $granted = 1;
    $stmt = $conn->prepare("
        INSERT INTO user_permission (user_id, permission_id, granted, granted_by, reason)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            granted = VALUES(granted), 
            granted_by = VALUES(granted_by), 
            granted_at = NOW(), 
            reason = VALUES(reason)
    ");
    $stmt->bind_param("iiiis", $user_id, $permission_id, $granted, $granted_by, $reason);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        logPermissionChange($conn, $user_id, $permission_id, 'grant', $granted_by, $reason);
    }
    
    return $result;
}

/**
 * Revoke permission from a user
 */
function revokePermission($conn, $user_id, $permission_name, $revoked_by, $reason = '') {
    $stmt = $conn->prepare("SELECT permission_id FROM permission WHERE permission_name = ?");
    $stmt->bind_param("s", $permission_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }
    
    $row = $result->fetch_assoc();
    $permission_id = $row['permission_id'];
    $stmt->close();
    
    $granted = 0;
    $stmt = $conn->prepare("
        INSERT INTO user_permission (user_id, permission_id, granted, granted_by, reason)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            granted = VALUES(granted), 
            granted_by = VALUES(granted_by), 
            granted_at = NOW(), 
            reason = VALUES(reason)
    ");
    $stmt->bind_param("iiiis", $user_id, $permission_id, $granted, $revoked_by, $reason);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        logPermissionChange($conn, $user_id, $permission_id, 'revoke', $revoked_by, $reason);
    }
    
    return $result;
}

/**
 * Grant event-specific access
 */
function grantEventAccess($conn, $event_id, $user_id, $permissions, $granted_by, $reason = '') {
    $can_view = isset($permissions['view']) && $permissions['view'] ? 1 : 0;
    $can_edit = isset($permissions['edit']) && $permissions['edit'] ? 1 : 0;
    $can_delete = isset($permissions['delete']) && $permissions['delete'] ? 1 : 0;
    $can_manage_attendance = isset($permissions['manage_attendance']) && $permissions['manage_attendance'] ? 1 : 0;
    $can_export_data = isset($permissions['export_data']) && $permissions['export_data'] ? 1 : 0;
    
    $stmt = $conn->prepare("
        INSERT INTO event_access (
            event_id, user_id, can_view, can_edit, can_delete, 
            can_manage_attendance, can_export_data, granted_by, reason
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            can_view = VALUES(can_view), 
            can_edit = VALUES(can_edit), 
            can_delete = VALUES(can_delete), 
            can_manage_attendance = VALUES(can_manage_attendance),
            can_export_data = VALUES(can_export_data),
            granted_by = VALUES(granted_by), 
            granted_at = NOW(), 
            reason = VALUES(reason)
    ");
    
    $stmt->bind_param("iiiiiiiis",
        $event_id, $user_id, $can_view, $can_edit, $can_delete, 
        $can_manage_attendance, $can_export_data, $granted_by, $reason
    );
    
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        logPermissionChange($conn, $user_id, null, 'event_access', $granted_by, $reason, $event_id);
    }
    
    return $result;
}

/**
 * Log permission changes
 */
function logPermissionChange($conn, $user_id, $permission_id, $action, $changed_by, $reason, $event_id = null) {
    $stmt = $conn->prepare("
        INSERT INTO permission_audit_log (user_id, permission_id, event_id, action, changed_by, reason)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisis", $user_id, $permission_id, $event_id, $action, $changed_by, $reason);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get all permissions grouped by category
 */
function getAllPermissions($conn) {
    $permissions = [];
    
    $result = $conn->query("
        SELECT permission_id, permission_name, permission_category, description
        FROM permission
        ORDER BY permission_category, permission_name
    ");
    
    while ($row = $result->fetch_assoc()) {
        if (!isset($permissions[$row['permission_category']])) {
            $permissions[$row['permission_category']] = [];
        }
        $permissions[$row['permission_category']][] = $row;
    }
    
    return $permissions;
}

/**
 * Get all roles
 */
function getAllRoles($conn) {
    $result = $conn->query("SELECT role_id, role_name, description FROM role ORDER BY role_id");
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>