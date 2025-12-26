<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}
include('../../includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Check role
$role_stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if ($role !== 'event_head') {
    die("Access denied.");
}

// Get user email
$user_stmt = $conn->prepare("SELECT email FROM user WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_stmt->bind_result($email);
$user_stmt->fetch();
$user_stmt->close();

// Get organizer ID
$org_stmt = $conn->prepare("SELECT organizer_id FROM organizer WHERE contact_email = ?");
$org_stmt->bind_param("s", $email);
$org_stmt->execute();
$org_stmt->bind_result($organizer_id);
$org_stmt->fetch();
$org_stmt->close();

// Fetch events created by this organizer
$event_query = $conn->prepare("SELECT event_id, title FROM event WHERE organizer_id = ?");
$event_query->bind_param("i", $organizer_id);
$event_query->execute();
$events = $event_query->get_result();

// Handle selected event
$selected_event = $_GET['event_id'] ?? null;
$attendances = [];
$event_title = '';

if ($selected_event) {
    // Get event title
    $title_stmt = $conn->prepare("SELECT title FROM event WHERE event_id = ?");
    $title_stmt->bind_param("i", $selected_event);
    $title_stmt->execute();
    $title_stmt->bind_result($event_title);
    $title_stmt->fetch();
    $title_stmt->close();

    $query = "
        SELECT u.first_name, u.middle_name, u.last_name, u.email,
               a.check_in_time, a.check_out_time, a.status
        FROM registration r
        JOIN user u ON r.user_id = u.user_id
        LEFT JOIN attendance a ON r.registration_id = a.registration_id
        WHERE r.event_id = ?
        ORDER BY u.last_name, u.first_name
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selected_event);
    $stmt->execute();
    $attendances = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - Eventix</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/event_head.css">
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

        <a href="../event/manage_events.php">
            <i data-lucide="settings"></i>
            Manage Events
        </a>

        <a href="../qr/scan_qr.php">
            <i data-lucide="scan"></i>
            QR Scanner
        </a>

        <a href="view_attendance.php" class="active">
            <i data-lucide="eye"></i>
            View Attendance
        </a>

        <a href="../auth/logout.php">
            <i data-lucide="log-out"></i>
            Logout
        </a>
    </nav>
</aside>

<main class="main-content">
    <!-- Event Head Banner -->
    <header class="banner event-head-banner">
        <div>
            <div class="event-head-badge">
                <i data-lucide="briefcase" style="width: 14px; height: 14px;"></i>
                Event Organizer
            </div>
            <h1>Attendance Records</h1>
            <p>View and manage participants' attendance for your events</p>
        </div>
        <img src="../../assets/eventix-logo.png" alt="Eventix logo" />
    </header>

    <div class="attendance-container">
        <div style="display: flex; gap: 12px; margin-bottom: 24px;">
            <a href="manage_events.php" class="back-link">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                Back to Manage Events
            </a>
        </div>

        <h2>
            <i data-lucide="eye"></i>
            View Attendance
        </h2>

        <!-- Event Selection -->
        <div class="event-select-container">
            <form method="GET">
                <label for="event_id">
                    <i data-lucide="calendar" style="width: 18px; height: 18px; vertical-align: middle;"></i>
                    Select Event
                </label>
                <select name="event_id" id="event_id" required>
                    <option value="">-- Choose an Event --</option>
                    <?php
                    $events->data_seek(0);
                    while ($event = $events->fetch_assoc()):
                    ?>
                        <option value="<?= $event['event_id'] ?>"
                                <?= $selected_event == $event['event_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['title']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">
                    <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                    View Attendance
                </button>
            </form>
        </div>

        <?php if ($selected_event && $attendances): ?>
            <?php
            // Calculate statistics
            $total = 0;
            $present = 0;
            $absent = 0;
            $checked_out = 0;

            $attendances->data_seek(0);
            while ($stat = $attendances->fetch_assoc()) {
                $total++;
                if ($stat['status'] === 'present' || $stat['check_in_time']) {
                    $present++;
                    if ($stat['check_out_time']) {
                        $checked_out++;
                    }
                } else {
                    $absent++;
                }
            }
            $attendances->data_seek(0);
            ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $total ?></div>
                    <div class="stat-label">Total Registered</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $present ?></div>
                    <div class="stat-label">Checked In</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $checked_out ?></div>
                    <div class="stat-label">Checked Out</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $absent ?></div>
                    <div class="stat-label">Not Attended</div>
                </div>
            </div>

            <h3 style="color: var(--maroon); margin-bottom: 15px;">
                <i data-lucide="users" style="width: 20px; height: 20px; vertical-align: middle;"></i>
                <?= htmlspecialchars($event_title) ?>
            </h3>

            <div class="search-export-bar">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search by name or email..."
                    onkeyup="filterTable()">
                <button onclick="exportToExcel()">
                    <i data-lucide="download" style="width: 18px; height: 18px;"></i>
                    Export to Excel
                </button>
            </div>

            <table class="attendance-table" id="attendanceTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $attendances->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= $row['check_in_time'] ? date('M j, Y - g:i A', strtotime($row['check_in_time'])) : '—' ?></td>
                            <td><?= $row['check_out_time'] ? date('M j, Y - g:i A', strtotime($row['check_out_time'])) : '—' ?></td>
                            <td>
                                <?php if ($row['status'] === 'present' || $row['check_in_time']): ?>
                                    <span class="status-badge status-present">
                                        <i data-lucide="check-circle" style="width: 14px; height: 14px; vertical-align: middle;"></i>
                                        Present
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-absent">
                                        <i data-lucide="x-circle" style="width: 14px; height: 14px; vertical-align: middle;"></i>
                                        Absent
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php elseif ($selected_event): ?>
            <div class="no-data">
                <i data-lucide="inbox"></i>
                <h3 style="color: #6b6b6b; margin-bottom: 8px;">No Participants Yet</h3>
                <p>No one has registered for this event yet.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    function filterTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('attendanceTable');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const nameCell = rows[i].getElementsByTagName('td')[0];
            const emailCell = rows[i].getElementsByTagName('td')[1];

            if (nameCell && emailCell) {
                const nameText = nameCell.textContent || nameCell.innerText;
                const emailText = emailCell.textContent || emailCell.innerText;

                if (nameText.toLowerCase().indexOf(filter) > -1 ||
                    emailText.toLowerCase().indexOf(filter) > -1) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
    }

    function exportToExcel() {
        const table = document.getElementById('attendanceTable');
        let tableHTML = table.outerHTML.replace(/ /g, '%20');

        const filename = 'attendance_<?= $event_title ? preg_replace('/[^a-zA-Z0-9]/', '_', $event_title) : 'data' ?>_<?= date('Y-m-d') ?>.xls';
        const downloadLink = document.createElement('a');
        document.body.appendChild(downloadLink);

        downloadLink.href = 'data:application/vnd.ms-excel,' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
</script>
</body>
</html>