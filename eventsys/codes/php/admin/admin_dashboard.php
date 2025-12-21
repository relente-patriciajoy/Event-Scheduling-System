<?php
/**
 * ADMIN DASHBOARD - Exclusive Admin Panel
 * Only accessible to verified administrators
 */
session_start();

// Strict admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}

include('../../includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Get admin statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM user");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total events
$result = $conn->query("SELECT COUNT(*) as count FROM event");
$stats['total_events'] = $result->fetch_assoc()['count'];

// Total registrations
$result = $conn->query("SELECT COUNT(*) as count FROM registration");
$stats['total_registrations'] = $result->fetch_assoc()['count'];

// Recent registrations (last 7 days)
$result = $conn->query("SELECT COUNT(*) as count FROM registration WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['recent_registrations'] = $result->fetch_assoc()['count'];

// Get recent users
$recent_users = $conn->query("SELECT user_id, CONCAT(first_name, ' ', last_name) as name, email, role, created_at FROM user ORDER BY created_at DESC LIMIT 5");

// Get upcoming events
$upcoming_events = $conn->query("SELECT e.event_id, e.title, e.start_time, v.name as venue, COUNT(r.registration_id) as registrations FROM event e LEFT JOIN venue v ON e.venue_id = v.venue_id LEFT JOIN registration r ON e.event_id = r.event_id WHERE e.start_time >= NOW() GROUP BY e.event_id ORDER BY e.start_time ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Eventix</title>
    <link rel="icon" type="image/png" href="../../assets/eventix-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/management.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .admin-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 32px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        }
        
        .admin-badge {
            background: #e63946;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .action-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #e63946;
            text-decoration: none;
            color: #1a1a1a;
            transition: all 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(230, 57, 70, 0.15);
        }
        
        .action-card h3 {
            font-size: 1.1rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-card p {
            font-size: 0.9rem;
            color: #6b6b6b;
            margin: 0;
        }
    </style>
</head>
<body class="dashboard-layout">
    <!-- Admin Sidebar -->
    <aside class="sidebar">
        <h2 class="logo">Eventix Admin</h2>
        <nav>
            <a href="admin_dashboard.php" class="active">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            
            <div class="dropdown-nav">
                <div class="dropdown-toggle" onclick="toggleDropdown(this)">
                    <i data-lucide="database"></i>
                    <span>Maintenance</span>
                    <span style="margin-left:auto;">▾</span>
                </div>
                <div class="dropdown-menu">
                    <a href="../admin/manage_user.php">Users</a>
                    <a href="../admin/manage_venue.php">Venues</a>
                    <a href="../admin/manage_organizer.php">Organizers</a>
                    <a href="../admin/manage_categories.php">Categories</a>
                </div>
            </div>
            
            <a href="../event/manage_events.php">
                <i data-lucide="calendar"></i> All Events
            </a>
            
            <a href="../event/view_attendance.php">
                <i data-lucide="users"></i> Attendance
            </a>
            
            <a href="user_promotions.php">
                <i data-lucide="user-plus"></i> Promote Users
            </a>
            
            <a href="../auth/logout.php">
                <i data-lucide="log-out"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="management-content">
        <!-- Admin Header -->
        <div class="admin-header">
            <div class="admin-badge">
                <i data-lucide="shield" style="width: 14px; height: 14px;"></i>
                Administrator
            </div>
            <h1 style="margin: 0 0 8px 0; font-size: 2.5rem;">Admin Dashboard</h1>
            <p style="margin: 0; opacity: 0.9;">Welcome back, <?= htmlspecialchars($full_name) ?></p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-card-header">
                    <h3>Total Users</h3>
                    <div class="stat-card-icon">
                        <i data-lucide="users" size="24"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?= $stats['total_users'] ?></div>
                <div class="stat-card-change">Registered users</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <h3>Total Events</h3>
                    <div class="stat-card-icon">
                        <i data-lucide="calendar" size="24"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?= $stats['total_events'] ?></div>
                <div class="stat-card-change">All time events</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <h3>Total Registrations</h3>
                    <div class="stat-card-icon">
                        <i data-lucide="ticket" size="24"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?= $stats['total_registrations'] ?></div>
                <div class="stat-card-change">Event registrations</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <h3>This Week</h3>
                    <div class="stat-card-icon">
                        <i data-lucide="trending-up" size="24"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?= $stats['recent_registrations'] ?></div>
                <div class="stat-card-change positive">New registrations</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 style="margin-bottom: 20px; color: #1a1a1a;">Quick Actions</h2>
        <div class="quick-actions">
            <a href="../admin/manage_user.php" class="action-card">
                <h3>
                    <i data-lucide="users" style="width: 20px; height: 20px;"></i>
                    Manage Users
                </h3>
                <p>Add, edit, or remove users</p>
            </a>

            <a href="../admin/manage_venue.php" class="action-card">
                <h3>
                    <i data-lucide="map-pin" style="width: 20px; height: 20px;"></i>
                    Manage Venues
                </h3>
                <p>Configure event locations</p>
            </a>

            <a href="../admin/manage_organizer.php" class="action-card">
                <h3>
                    <i data-lucide="briefcase" style="width: 20px; height: 20px;"></i>
                    Manage Organizers
                </h3>
                <p>Event organizer management</p>
            </a>

            <a href="../admin/manage_categories.php" class="action-card">
                <h3>
                    <i data-lucide="folder" style="width: 20px; height: 20px;"></i>
                    Event Categories
                </h3>
                <p>Organize events by category</p>
            </a>

            <a href="user_promotions.php" class="action-card">
                <h3>
                    <i data-lucide="user-plus" style="width: 20px; height: 20px;"></i>
                    Promote Users
                </h3>
                <p>Upgrade user roles</p>
            </a>
        </div>

        <!-- Recent Activity -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 32px;">
            <!-- Recent Users -->
            <div class="management-card">
                <h2>Recent Users</h2>
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $recent_users->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'event_head' ? 'warning' : 'info') ?>">
                                        <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Upcoming Events -->
            <div class="management-card">
                <h2>Upcoming Events</h2>
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Registrations</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $upcoming_events->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['title']) ?></td>
                                <td><?= $event['registrations'] ?></td>
                                <td><?= date('M j', strtotime($event['start_time'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        
        function toggleDropdown(toggle) {
            const container = toggle.closest(".dropdown-nav");
            container.classList.toggle("open");
        }
    </script>
</body>
</html>