<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}

include('../../includes/db.php');

$user_id = $_SESSION['user_id'];

// Check role
$role_stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if ($role !== 'event_head') {
    die("Access denied. This feature is only available for Event Organizers.");
}

// Get organizer's events
$email_stmt = $conn->prepare("SELECT email FROM user WHERE user_id = ?");
$email_stmt->bind_param("i", $user_id);
$email_stmt->execute();
$email_stmt->bind_result($email);
$email_stmt->fetch();
$email_stmt->close();

$org_stmt = $conn->prepare("SELECT organizer_id FROM organizer WHERE contact_email = ?");
$org_stmt->bind_param("s", $email);
$org_stmt->execute();
$org_stmt->bind_result($organizer_id);
$org_stmt->fetch();
$org_stmt->close();

// Find inactive members (registered but didn't attend)
$inactive_query = $conn->prepare("
    SELECT DISTINCT
        u.user_id,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.email,
        u.phone,
        COUNT(r.registration_id) as total_registrations,
        SUM(CASE WHEN a.attendance_id IS NULL THEN 1 ELSE 0 END) as missed_events,
        MAX(e.start_time) as last_registered_event
    FROM registration r
    JOIN user u ON r.user_id = u.user_id
    JOIN event e ON r.event_id = e.event_id
    LEFT JOIN attendance a ON r.registration_id = a.registration_id
    WHERE e.organizer_id = ?
        AND e.end_time < NOW()
        AND a.attendance_id IS NULL
    GROUP BY u.user_id
    HAVING missed_events > 0
    ORDER BY missed_events DESC, last_registered_event DESC
");

$inactive_query->bind_param("i", $organizer_id);
$inactive_query->execute();
$inactive_members = $inactive_query->get_result();

// Calculate statistics
$total_inactive = $inactive_members->num_rows;
$high_risk = 0;
$medium_risk = 0;
$low_risk = 0;

$inactive_members->data_seek(0);
while ($member = $inactive_members->fetch_assoc()) {
    if ($member['missed_events'] >= 3) {
        $high_risk++;
    } elseif ($member['missed_events'] == 2) {
        $medium_risk++;
    } else {
        $low_risk++;
    }
}
$inactive_members->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inactive Members Tracking - Eventix</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/event_head.css">
    <link rel="stylesheet" href="../../css/inactive_tracking.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="dashboard-layout event-head-page">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">Eventix</div>
        <nav>
            <a href="../dashboard/home.php">
                <i data-lucide="home"></i>
                Home
            </a>
            <a href="../dashboard/events.php">
                <i data-lucide="calendar"></i>
                Browse Events
            </a>
            <a href="../dashboard/my_events.php">
                <i data-lucide="user-check"></i>
                My Events
            </a>
            <a href="../dashboard/attendance.php">
                <i data-lucide="clipboard-check"></i>
                Attendance
            </a>
            <a href="../calendar/calendar.php">
                <i data-lucide="calendar-days"></i>
                Event Calendar
            </a>
            <a href="manage_events.php">
                <i data-lucide="settings"></i>
                Manage Events
            </a>
            <a href="../qr/scan_qr.php">
                <i data-lucide="scan"></i>
                QR Scanner
            </a>
            <a href="view_attendance.php">
                <i data-lucide="eye"></i>
                View Attendance
            </a>
            <a href="reports.php">
                <i data-lucide="file-text"></i>
                Reports
            </a>
            <a href="inactive_tracking.php" class="active">
                <i data-lucide="user-x"></i>
                Inactive Members
            </a>
            <a href="../auth/logout.php">
                <i data-lucide="log-out"></i>
                Logout
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="banner event-head-banner">
            <div>
                <div class="event-head-badge">
                    <i data-lucide="briefcase"></i>
                    Event Organizer
                </div>
                <h1>Inactive Members Tracking</h1>
                <p>Track members who registered but didn't attend</p>
            </div>
            <img src="../../assets/eventix-logo.png" alt="Eventix logo" />
        </header>

        <div class="inactive-container">
            <!-- Statistics Overview -->
            <div class="inactive-stats-grid">
                <div class="inactive-stat-card">
                    <div class="inactive-stat-label">Total Inactive</div>
                    <div class="inactive-stat-value"><?= $total_inactive ?></div>
                </div>
                <div class="inactive-stat-card">
                    <div class="inactive-stat-label">High Risk (3+)</div>
                    <div class="inactive-stat-value"><?= $high_risk ?></div>
                </div>
                <div class="inactive-stat-card">
                    <div class="inactive-stat-label">Medium Risk (2)</div>
                    <div class="inactive-stat-value"><?= $medium_risk ?></div>
                </div>
                <div class="inactive-stat-card">
                    <div class="inactive-stat-label">Low Risk (1)</div>
                    <div class="inactive-stat-value"><?= $low_risk ?></div>
                </div>
            </div>

            <div class="inactive-table-section">
                <h3>
                    <i data-lucide="user-x"></i>
                    Inactive Participants
                </h3>
                
                <?php if ($inactive_members->num_rows > 0): ?>
                    <!-- Filter Actions -->
                    <div class="filter-actions">
                        <button class="filter-btn active" data-filter="all">All Members</button>
                        <button class="filter-btn" data-filter="high">High Risk (3+)</button>
                        <button class="filter-btn" data-filter="medium">Medium Risk (2)</button>
                        <button class="filter-btn" data-filter="low">Low Risk (1)</button>
                        <button class="action-btn action-btn-secondary" onclick="exportInactiveToExcel()">
                            <i data-lucide="download"></i>
                            Export to Excel
                        </button>
                    </div>

                    <!-- Search Bar -->
                    <div class="search-bar">
                        <input
                            type="text"
                            id="searchInput"
                            class="search-input"
                            placeholder="Search by name, email, or phone...">
                    </div>

                    <!-- Inactive Members Table -->
                    <table class="inactive-table" id="inactiveTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Total Registrations</th>
                                <th>Missed Events</th>
                                <th>Last Registered</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($member = $inactive_members->fetch_assoc()): ?>
                                <?php
                                $full_name = trim($member['first_name'] . ' ' . ($member['middle_name'] ?: '') . ' ' . $member['last_name']);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($full_name) ?></td>
                                    <td><?= htmlspecialchars($member['email']) ?></td>
                                    <td><?= htmlspecialchars($member['phone'] ?: 'N/A') ?></td>
                                    <td><?= $member['total_registrations'] ?></td>
                                    <td>
                                        <span class="missed-badge"><?= $member['missed_events'] ?></span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($member['last_registered_event'])) ?></td>
                                    <td>
                                        <button
                                            onclick="sendReminder('<?= htmlspecialchars($member['email']) ?>', '<?= htmlspecialchars($full_name) ?>')"
                                            class="action-btn action-btn-primary">
                                            <i data-lucide="mail"></i>
                                            Send Reminder
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i data-lucide="check-circle" class="empty-state-icon"></i>
                        <h3>Great News!</h3>
                        <p>All registered participants have attended your events.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../../js/inactive_tracking.js"></script>
</body>
</html>