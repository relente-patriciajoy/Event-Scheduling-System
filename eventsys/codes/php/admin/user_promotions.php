<?php
/**
 * USER PROMOTIONS PAGE
 * Admin can promote users to Event Head or Admin
 */
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}

include('../../includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];
$message = "";
$error = "";

// Handle promotion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['promote_user'])) {
    $target_user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    // Validate role
    if (!in_array($new_role, ['user', 'event_head', 'admin'])) {
        $error = "Invalid role selected.";
    } else {
        $stmt = $conn->prepare("UPDATE user SET role = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_role, $target_user_id);
        
        if ($stmt->execute()) {
            $message = "User role updated successfully!";
        } else {
            $error = "Error updating user role.";
        }
        $stmt->close();
    }
}

// Handle demotion
if (isset($_GET['demote'])) {
    $target_user_id = $_GET['demote'];
    
    $stmt = $conn->prepare("UPDATE user SET role = 'user' WHERE user_id = ?");
    $stmt->bind_param("i", $target_user_id);
    
    if ($stmt->execute()) {
        $message = "User demoted to regular user.";
    } else {
        $error = "Error demoting user.";
    }
    $stmt->close();
    
    header("Location: user_promotions.php?msg=" . urlencode($message));
    exit();
}

// Search functionality
$search = $_GET['search'] ?? '';
$search_param = "%$search%";

if (!empty($search)) {
    $stmt = $conn->prepare("SELECT user_id, CONCAT(first_name, ' ', last_name) as full_name, email, phone, role, created_at FROM user WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?) ORDER BY created_at DESC");
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare("SELECT user_id, CONCAT(first_name, ' ', last_name) as full_name, email, phone, role, created_at FROM user ORDER BY created_at DESC");
}

$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Promotions - Admin Panel</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/management.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .role-user {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .role-event_head {
            background: #fef3c7;
            color: #92400e;
        }
        
        .role-admin {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        .promote-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .promote-form select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
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
          <h1>User Role Management</h1>
          <p>Promote or demote users to different roles</p>
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

        <?php if (isset($_GET['msg'])): ?>
            <div class="management-alert success">
                <i data-lucide="check-circle"></i>
                <?= htmlspecialchars($_GET['msg']) ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none';">×</span>
            </div>
        <?php endif; ?>

        <!-- Role Information -->
        <div class="management-card">
            <h2>Role Hierarchy</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-top: 16px;">
                <div style="padding: 16px; background: #dbeafe; border-radius: 10px; border-left: 4px solid #1e40af;">
                    <h3 style="color: #1e40af; margin-bottom: 8px;">👤 Regular User</h3>
                    <p style="font-size: 0.9rem; color: #1e40af; margin: 0;">Can browse and register for events</p>
                </div>
                
                <div style="padding: 16px; background: #fef3c7; border-radius: 10px; border-left: 4px solid #f59e0b;">
                    <h3 style="color: #92400e; margin-bottom: 8px;">🎯 Event Head</h3>
                    <p style="font-size: 0.9rem; color: #92400e; margin: 0;">Can create and manage events + view attendance</p>
                </div>
                
                <div style="padding: 16px; background: #fee2e2; border-radius: 10px; border-left: 4px solid #e63946;">
                    <h3 style="color: #b91c1c; margin-bottom: 8px;">🛡️ Administrator</h3>
                    <p style="font-size: 0.9rem; color: #b91c1c; margin: 0;">Full system access + user management</p>
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
                                    <span class="role-badge role-<?= $user['role'] ?>">
                                        <i data-lucide="<?= $user['role'] === 'admin' ? 'shield' : ($user['role'] === 'event_head' ? 'star' : 'user') ?>" style="width: 14px; height: 14px;"></i>
                                        <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="promote-form" style="display: inline-flex;">
                                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                            <select name="new_role" required>
                                                <option value="">Change role...</option>
                                                <option value="user" <?= $user['role'] === 'user' ? 'disabled' : '' ?>>User</option>
                                                <option value="event_head" <?= $user['role'] === 'event_head' ? 'disabled' : '' ?>>Event Head</option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'disabled' : '' ?>>Admin</option>
                                            </select>
                                            <button type="submit" name="promote_user" class="btn btn-primary btn-sm">
                                                Update
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #6b6b6b; font-size: 0.85rem; font-style: italic;">You (cannot change own role)</span>
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

    <script>
        lucide.createIcons();

        // Auto-dismiss alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.management-alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>