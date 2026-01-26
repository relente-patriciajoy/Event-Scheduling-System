<?php
require_once('../../includes/session.php');
require_once('../../includes/role_protection.php');
requireRole('admin');

include('../../includes/db.php');
require_once('../../includes/permission_functions.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$message = "";
$error = "";

// Handle role change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_role'])) {
    $target_user_id = $_POST['user_id'];
    $new_role_id = $_POST['new_role_id'];
    $reason = $_POST['reason'] ?? 'Role change by admin';
    
    // Update BOTH role_id and role string for backward compatibility
    $stmt = $conn->prepare("
        UPDATE user u
        JOIN role r ON r.role_id = ?
        SET u.role_id = ?, u.role = r.role_name
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("iii", $new_role_id, $new_role_id, $target_user_id);
    
    if ($stmt->execute()) {
        logPermissionChange($conn, $target_user_id, null, 'role_change', $user_id, $reason);
        $message = "User role updated successfully!";
    } else {
        $error = "Error updating user role.";
    }
    $stmt->close();
}

// Handle batch permission update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['toggle_permissions'])) {
    $target_user_id = $_POST['user_id'];
    $selected_permissions = $_POST['permissions'] ?? [];
    $reason = $_POST['reason'] ?? 'Batch permission update';
    
    // Get all permissions
    $all_perms_query = $conn->query("SELECT permission_name FROM permission");
    $all_permission_names = [];
    while ($p = $all_perms_query->fetch_assoc()) {
        $all_permission_names[] = $p['permission_name'];
    }
    
    $success_count = 0;
    $error_count = 0;

    // Grant selected permissions
    foreach ($selected_permissions as $perm_name) {
        if (grantPermission($conn, $target_user_id, $perm_name, $user_id, $reason)) {
            $success_count++;
        } else {
            $error_count++;
        }
    }

    // Revoke unselected permissions
    $permissions_to_revoke = array_diff($all_permission_names, $selected_permissions);
    foreach ($permissions_to_revoke as $perm_name) {
        if (revokePermission($conn, $target_user_id, $perm_name, $user_id, $reason)) {
            $success_count++;
        }
    }

    if ($error_count === 0) {
        $message = "Permissions updated successfully! ($success_count changes)";
    } else {
        $error = "Some permissions failed to update. Successful: $success_count, Failed: $error_count";
    }
}

// Handle event access control
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['set_event_access'])) {
    $target_user_id = $_POST['user_id'];
    $event_id = $_POST['event_id'];
    $reason = $_POST['reason'] ?? 'Event access granted by admin';
    
    $permissions = [
        'view' => isset($_POST['can_view']),
        'edit' => isset($_POST['can_edit']),
        'delete' => isset($_POST['can_delete']),
        'manage_attendance' => isset($_POST['can_manage_attendance']),
        'export_data' => isset($_POST['can_export_data'])
    ];
    
    if (grantEventAccess($conn, $event_id, $target_user_id, $permissions, $user_id, $reason)) {
        $message = "Event access permissions updated successfully!";
    } else {
        $error = "Failed to update event access.";
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$search_param = "%$search%";

if (!empty($search)) {
    $stmt = $conn->prepare("
        SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) as full_name, 
               u.email, u.phone, u.created_at, r.role_name, r.role_id
        FROM user u
        JOIN role r ON u.role_id = r.role_id
        WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?) 
        ORDER BY u.created_at DESC
    ");
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare("
        SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) as full_name, 
               u.email, u.phone, u.created_at, r.role_name, r.role_id
        FROM user u
        JOIN role r ON u.role_id = r.role_id
        ORDER BY u.created_at DESC
    ");
}

$stmt->execute();
$users = $stmt->get_result();

// Get all permissions grouped by category
$all_permissions = getAllPermissions($conn);

// Get all events
$all_events = $conn->query("
    SELECT event_id, title, start_time
    FROM event
    ORDER BY start_time DESC
    LIMIT 50
");

// Get all roles
$all_roles = getAllRoles($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Permissions Management - Admin Panel</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/management.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
            margin: 20px 0;
        }
        
        .permission-item {
            background: #f9f9f9;
            padding: 14px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .permission-item:hover {
            border-color: #e63946;
            background: #fff5f5;
        }
        
        .permission-item label {
            display: flex;
            align-items: start;
            gap: 10px;
            cursor: pointer;
        }
        
        .permission-item input[type="checkbox"] {
            margin-top: 3px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .permission-category-title {
            background: linear-gradient(135deg, #e63946 0%, #c72c3a 100%);
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            margin: 25px 0 15px 0;
            display: inline-block;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .user-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
        }
        
        .modal-content {
            background: white;
            max-width: 900px;
            margin: 50px auto;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #1a1a1a;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #6b6b6b;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .close-modal:hover {
            background: #f0f0f0;
            color: #e63946;
        }
        
        .tab-navigation {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab-btn {
            padding: 14px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #6b6b6b;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .tab-btn:hover {
            color: #e63946;
        }
        
        .tab-btn.active {
            color: #e63946;
            border-bottom-color: #e63946;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }

        .permission-summary {
            background: #f0f9ff;
            border: 2px solid #0ea5e9;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .permission-summary h4 {
            color: #0369a1;
            margin: 0 0 8px 0;
            font-size: 0.95rem;
        }

        .permission-summary p {
            color: #075985;
            margin: 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="dashboard-layout">
    <?php include('admin_sidebar.php'); ?>

    <main class="management-content">
        <!-- Page Header -->
        <div class="admin-header">
          <div class="admin-badge">
              <i data-lucide="shield" style="width: 14px; height: 14px;"></i>
              Administrator
          </div>
          <h1>User Permissions Management</h1>
          <p>Manage roles, permissions, and event-specific access</p>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="management-alert success">
                <i data-lucide="check-circle"></i>
                <?= htmlspecialchars($message) ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none';">×</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="management-alert error">
                <i data-lucide="alert-circle"></i>
                <?= htmlspecialchars($error) ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none';">×</span>
            </div>
        <?php endif; ?>

        <!-- Role Information -->
        <div class="management-card">
            <h2>Permission System Overview</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-top: 16px;">
                <div style="padding: 20px; background: #dbeafe; border-radius: 10px; border-left: 4px solid #1e40af;">
                    <h3 style="color: #1e40af; margin-bottom: 8px;">👤 Participant</h3>
                    <p style="font-size: 0.9rem; color: #1e40af; margin: 0;">View and register for events, track own attendance</p>
                </div>
                
                <div style="padding: 20px; background: #fef3c7; border-radius: 10px; border-left: 4px solid #f59e0b;">
                    <h3 style="color: #92400e; margin-bottom: 8px;">🎯 Event Head</h3>
                    <p style="font-size: 0.9rem; color: #92400e; margin: 0;">Create/manage own events, scan QR, view attendance</p>
                </div>
                
                <div style="padding: 20px; background: #fee2e2; border-radius: 10px; border-left: 4px solid #e63946;">
                    <h3 style="color: #b91c1c; margin-bottom: 8px;">🛡️ Administrator</h3>
                    <p style="font-size: 0.9rem; color: #b91c1c; margin: 0;">Full system access, manage all users and permissions</p>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="management-card">
            <form method="GET" class="management-search">
                <input
                    type="text"
                    name="search"
                    placeholder="Search by name or email..."
                    value="<?= htmlspecialchars($search) ?>"
                >
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="search"></i>
                    Search
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="management-card">
            <h2>All Users (<?= $users->num_rows ?>)</h2>
            <?php if ($users->num_rows > 0): ?>
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Current Role</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $user['role_name'] === 'admin' ? 'danger' : ($user['role_name'] === 'event_head' ? 'warning' : 'info') ?>">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role_name']))) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td class="actions">
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button onclick="openPermissionModal(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['full_name'], ENT_QUOTES) ?>', <?= $user['role_id'] ?>)" class="btn btn-primary btn-sm">
                                            <i data-lucide="settings"></i>
                                            Manage
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #6b6b6b; font-size: 0.85rem;">You (current admin)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i data-lucide="users"></i>
                    <h3>No Users Found</h3>
                    <p>No users match your search criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Permission Management Modal -->
    <div id="permissionModal" class="user-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalUserName">Manage User Permissions</h2>
                <button class="close-modal" onclick="closePermissionModal()">&times;</button>
            </div>

            <div class="tab-navigation">
                <button class="tab-btn active" onclick="switchTab(event, 'role')">
                    Change Role
                </button>
                <button class="tab-btn" onclick="switchTab(event, 'permissions')">
                    Custom Permissions
                </button>
                <button class="tab-btn" onclick="switchTab(event, 'events')">
                    Event Access
                </button>
            </div>

            <!-- Tab 1: Change Role -->
            <div id="roleTab" class="tab-content active">
                <form method="POST" class="management-form">
                    <input type="hidden" name="user_id" id="roleUserId">
                    
                    <div class="form-group">
                        <label for="new_role_id">Select New Role</label>
                        <select name="new_role_id" id="new_role_id" required>
                            <?php foreach ($all_roles as $role_option): ?>
                                <option value="<?= $role_option['role_id'] ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role_option['role_name']))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="role_reason">Reason for Change</label>
                        <textarea name="reason" id="role_reason" rows="3" placeholder="Optional: Explain why this role change is needed"></textarea>
                    </div>

                    <button type="submit" name="change_role" class="btn btn-primary">
                        <i data-lucide="user-check"></i>
                        Update Role
                    </button>
                </form>
            </div>

            <!-- Tab 2: Custom Permissions -->
            <div id="permissionsTab" class="tab-content">
                <div class="permission-summary">
                    <h4><i data-lucide="info" style="width: 16px; height: 16px; vertical-align: middle;"></i> How Custom Permissions Work</h4>
                    <p><strong>✓ Checked permissions</strong> will be granted to the user (overrides role defaults).<br>
                    <strong>☐ Unchecked permissions</strong> will be revoked from the user (even if their role normally has them).</p>
                </div>
                
                <form method="POST" id="permissionsForm" class="management-form">
                    <input type="hidden" name="toggle_permissions" value="1">
                    <input type="hidden" name="user_id" id="permissionsUserId">

                    <div id="permissionsContainer">
                        <?php foreach ($all_permissions as $category => $permissions): ?>
                            <div class="permission-category-title"><?= strtoupper($category) ?> Permissions</div>
                            <div class="permissions-grid">
                                <?php foreach ($permissions as $perm): ?>
                                    <div class="permission-item">
                                        <label>
                                            <input type="checkbox"
                                                   class="permission-checkbox"
                                                   name="permissions[]"
                                                   value="<?= $perm['permission_name'] ?>"
                                                   data-permission="<?= $perm['permission_name'] ?>">
                                            <div>
                                                <strong><?= htmlspecialchars(str_replace(['_', '.'], [' ', ' › '], $perm['permission_name'])) ?></strong>
                                                <br>
                                                <small style="color: #6b6b6b;"><?= htmlspecialchars($perm['description']) ?></small>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group" style="margin-top: 24px;">
                        <label for="permissions_reason">Reason for Permission Changes</label>
                        <textarea name="reason" id="permissions_reason" rows="3"
                                  placeholder="Explain why you're granting or revoking these permissions"
                                  required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="check"></i>
                        Update Permissions
                    </button>
                </form>
            </div>

            <!-- Tab 3: Event Access -->
            <div id="eventsTab" class="tab-content">
                <p style="color: #6b6b6b; margin-bottom: 20px;">
                    Control which events this user can access and what actions they can perform.
                </p>
                
                <form method="POST" class="management-form">
                    <input type="hidden" name="user_id" id="eventUserId">
                    
                    <div class="form-group">
                        <label for="event_id">Select Event</label>
                        <select name="event_id" id="event_id" required>
                            <option value="">-- Choose an Event --</option>
                            <?php
                            $all_events->data_seek(0);
                            while ($event = $all_events->fetch_assoc()):
                            ?>
                                <option value="<?= $event['event_id'] ?>">
                                    <?= htmlspecialchars($event['title']) ?> - 
                                    <?= date('M j, Y', strtotime($event['start_time'])) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Event Permissions</label>
                        <div style="display: flex; flex-direction: column; gap: 12px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="can_view" style="width: 18px; height: 18px;" checked>
                                <span>Can View Event</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="can_edit" style="width: 18px; height: 18px;">
                                <span>Can Edit Event</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="can_delete" style="width: 18px; height: 18px;">
                                <span>Can Delete Event</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="can_manage_attendance" style="width: 18px; height: 18px;">
                                <span>Can Manage Attendance</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="can_export_data" style="width: 18px; height: 18px;">
                                <span>Can Export Data</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="event_reason">Reason for Access Grant</label>
                        <textarea name="reason" id="event_reason" rows="3" placeholder="Optional: Explain why this access is being granted"></textarea>
                    </div>

                    <button type="submit" name="set_event_access" class="btn btn-primary">
                        <i data-lucide="key"></i>
                        Set Event Access
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        let currentUserId = null;

        function openPermissionModal(userId, userName, currentRoleId) {
            currentUserId = userId;
            document.getElementById('modalUserName').textContent = 'Manage: ' + userName;
            document.getElementById('roleUserId').value = userId;
            document.getElementById('permissionsUserId').value = userId;
            document.getElementById('eventUserId').value = userId;
            document.getElementById('new_role_id').value = currentRoleId;
            
            // Reset event access form
            document.getElementById('event_id').value = '';
            document.querySelector('input[name="can_view"]').checked = true;
            document.querySelector('input[name="can_edit"]').checked = false;
            document.querySelector('input[name="can_delete"]').checked = false;
            document.querySelector('input[name="can_manage_attendance"]').checked = false;
            document.querySelector('input[name="can_export_data"]').checked = false;
            
            // Load user's current permissions
            loadUserPermissions(userId);

            document.getElementById('permissionModal').style.display = 'block';
        }

        function closePermissionModal() {
            document.getElementById('permissionModal').style.display = 'none';
        }

        function switchTab(event, tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(tabName + 'Tab').classList.add('active');
            event.target.classList.add('active');
            
            // Load event access when switching to Events tab
            if (tabName === 'events' && currentUserId) {
                const eventSelect = document.getElementById('event_id');
                if (eventSelect.value) {
                    loadEventAccess(currentUserId, eventSelect.value);
                }
            }

            lucide.createIcons();
        }

        function loadUserPermissions(userId) {
            fetch(`get_user_permissions.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // First, uncheck all permissions
                        document.querySelectorAll('.permission-checkbox').forEach(cb => {
                            cb.checked = false;
                        });

                        // Then check the ones user has
                        data.permissions.forEach(permName => {
                            const checkbox = document.querySelector(`input[data-permission="${permName}"]`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    }
                })
                .catch(error => console.error('Error loading permissions:', error));
        }

        function loadEventAccess(userId, eventId) {
            if (!eventId) return;

            fetch(`get_event_access.php?user_id=${userId}&event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const perms = data.permissions;
                        document.querySelector('input[name="can_view"]').checked = perms.can_view;
                        document.querySelector('input[name="can_edit"]').checked = perms.can_edit;
                        document.querySelector('input[name="can_delete"]').checked = perms.can_delete;
                        document.querySelector('input[name="can_manage_attendance"]').checked = perms.can_manage_attendance;
                        document.querySelector('input[name="can_export_data"]').checked = perms.can_export_data;
                    }
                })
                .catch(error => console.error('Error loading event access:', error));
        }

        // Event listener for event dropdown
        document.getElementById('event_id').addEventListener('change', function() {
            if (currentUserId && this.value) {
                loadEventAccess(currentUserId, this.value);
            }
        });

        // Batch permission form validation
        document.getElementById('permissionsForm').addEventListener('submit', function(e) {
            const reason = document.getElementById('permissions_reason').value.trim();

            if (!reason) {
                e.preventDefault();
                alert('Please provide a reason for these permission changes.');
                return false;
            }
        });

        // Auto-dismiss alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.management-alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('permissionModal');
            if (event.target === modal) {
                closePermissionModal();
            }
        }
    </script>
</body>
</html>