<?php
require_once('../../includes/session.php');
require_once('../../includes/role_protection.php');
requireRole('admin');

/**
 * ADMIN - View Event Details
 * View detailed information about a specific event
 */

// Admin access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin/admin-login.php");
    exit();
}

include('../../includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Get event ID from URL
$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    header("Location: admin_all_events.php");
    exit();
}

// Fetch event details
$stmt = $conn->prepare("
    SELECT e.*, 
           v.name as venue_name, v.address as venue_address, v.city as venue_city, v.capacity as venue_capacity,
           o.name as organizer_name, o.contact_email as organizer_email, o.phone as organizer_phone,
           c.category_name,
           COUNT(DISTINCT r.registration_id) as total_registrations,
           COUNT(DISTINCT CASE WHEN r.status = 'confirmed' THEN r.registration_id END) as confirmed_registrations,
           COUNT(DISTINCT a.attendance_id) as total_attendance
    FROM event e
    LEFT JOIN venue v ON e.venue_id = v.venue_id
    LEFT JOIN organizer o ON e.organizer_id = o.organizer_id
    LEFT JOIN event_category c ON e.category_id = c.category_id
    LEFT JOIN registration r ON e.event_id = r.event_id
    LEFT JOIN attendance a ON r.registration_id = a.registration_id
    WHERE e.event_id = ?
    GROUP BY e.event_id
");

$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_all_events.php");
    exit();
}

$event = $result->fetch_assoc();
$stmt->close();

// Get list of registered users
$registrations_stmt = $conn->prepare("
    SELECT u.first_name, u.middle_name, u.last_name, u.email, u.phone,
           r.registration_date, r.status, r.table_number,
           a.check_in_time, a.check_out_time, a.status as attendance_status
    FROM registration r
    JOIN user u ON r.user_id = u.user_id
    LEFT JOIN attendance a ON r.registration_id = a.registration_id
    WHERE r.event_id = ?
    ORDER BY r.registration_date DESC
");
$registrations_stmt->bind_param("i", $event_id);
$registrations_stmt->execute();
$registrations = $registrations_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Event - Admin Panel</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/management.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .event-detail-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: #6b6b6b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-size: 1.1rem;
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card-mini {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #e63946;
        }
        
        .stat-card-mini h3 {
            font-size: 0.85rem;
            color: #6b6b6b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card-mini .value {
            font-size: 2rem;
            font-weight: 700;
            color: #e63946;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #f0f0f0;
            color: #1a1a1a;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background: #e0e0e0;
            transform: translateX(-4px);
        }
    </style>
</head>
<body class="dashboard-layout">
    <?php include('admin_sidebar.php'); ?>

    <main class="management-content">
        <!-- Admin Header -->
        <div class="admin-header">
            <div class="admin-badge">
                <i data-lucide="shield" style="width: 14px; height: 14px;"></i>
                Administrator
            </div>
            <h1>Event Details</h1>
            <p>View comprehensive event information</p>
        </div>

        <a href="admin_all_events.php" class="back-button">
            <i data-lucide="arrow-left"></i>
            Back to All Events
        </a>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card-mini">
                <h3>Total Registrations</h3>
                <div class="value"><?= $event['total_registrations'] ?></div>
                <small>Out of <?= $event['capacity'] ?> capacity</small>
            </div>

            <div class="stat-card-mini">
                <h3>Confirmed</h3>
                <div class="value"><?= $event['confirmed_registrations'] ?></div>
                <small>Confirmed registrations</small>
            </div>

            <div class="stat-card-mini">
                <h3>Attendance</h3>
                <div class="value"><?= $event['total_attendance'] ?></div>
                <small>Checked in</small>
            </div>

            <div class="stat-card-mini">
                <h3>Available Slots</h3>
                <div class="value"><?= max(0, $event['capacity'] - $event['total_registrations']) ?></div>
                <small>Remaining capacity</small>
            </div>
        </div>

        <!-- Event Information -->
        <div class="event-detail-section">
            <h2 style="margin-bottom: 20px;">
                <i data-lucide="info" style="width: 24px; height: 24px; vertical-align: middle;"></i>
                Event Information
            </h2>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Event Title</span>
                    <span class="detail-value"><?= htmlspecialchars($event['title']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Category</span>
                    <span class="detail-value"><?= htmlspecialchars($event['category_name'] ?? 'Uncategorized') ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Start Date & Time</span>
                    <span class="detail-value"><?= date('F j, Y - g:i A', strtotime($event['start_time'])) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">End Date & Time</span>
                    <span class="detail-value"><?= date('F j, Y - g:i A', strtotime($event['end_time'])) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Price</span>
                    <span class="detail-value">
                        <?= $event['price'] > 0 ? '$' . number_format($event['price'], 2) : 'FREE' ?>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Capacity</span>
                    <span class="detail-value"><?= $event['capacity'] ?> people</span>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <span class="detail-label">Description</span>
                <p style="margin-top: 8px; line-height: 1.6; color: #1a1a1a;">
                    <?= nl2br(htmlspecialchars($event['description'])) ?>
                </p>
            </div>
        </div>

        <!-- Venue Information -->
        <div class="event-detail-section">
            <h2 style="margin-bottom: 20px;">
                <i data-lucide="map-pin" style="width: 24px; height: 24px; vertical-align: middle;"></i>
                Venue Details
            </h2>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Venue Name</span>
                    <span class="detail-value"><?= htmlspecialchars($event['venue_name']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Address</span>
                    <span class="detail-value"><?= htmlspecialchars($event['venue_address']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">City</span>
                    <span class="detail-value"><?= htmlspecialchars($event['venue_city']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Venue Capacity</span>
                    <span class="detail-value"><?= $event['venue_capacity'] ?> people</span>
                </div>
            </div>
        </div>

        <!-- Organizer Information -->
        <div class="event-detail-section">
            <h2 style="margin-bottom: 20px;">
                <i data-lucide="user-circle" style="width: 24px; height: 24px; vertical-align: middle;"></i>
                Organizer Details
            </h2>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Organizer Name</span>
                    <span class="detail-value"><?= htmlspecialchars($event['organizer_name']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Contact Email</span>
                    <span class="detail-value"><?= htmlspecialchars($event['organizer_email']) ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Contact Phone</span>
                    <span class="detail-value"><?= htmlspecialchars($event['organizer_phone']) ?></span>
                </div>
            </div>
        </div>

        <!-- Registered Participants -->
        <div class="management-card">
            <h2>Registered Participants (<?= $registrations->num_rows ?>)</h2>
            <?php if ($registrations->num_rows > 0): ?>
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                            <th>Table</th>
                            <th>Status</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($reg = $registrations->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($reg['first_name'] . ' ' . $reg['middle_name'] . ' ' . $reg['last_name']) ?></td>
                                <td><?= htmlspecialchars($reg['email']) ?></td>
                                <td><?= date('M j, Y', strtotime($reg['registration_date'])) ?></td>
                                <td>Table <?= $reg['table_number'] ?></td>
                                <td>
                                    <span class="badge badge-<?= $reg['status'] === 'confirmed' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($reg['status']) ?>
                                    </span>
                                </td>
                                <td><?= $reg['check_in_time'] ? date('g:i A', strtotime($reg['check_in_time'])) : '—' ?></td>
                                <td><?= $reg['check_out_time'] ? date('g:i A', strtotime($reg['check_out_time'])) : '—' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i data-lucide="users"></i>
                    <h3>No Registrations Yet</h3>
                    <p>No one has registered for this event yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>