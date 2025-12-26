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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .attendance-container {
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
        }

        .attendance-container h2 {
            color: var(--maroon);
            font-size: 1.8rem;
            margin-bottom: 24px;
            font-weight: 700;
            border-bottom: 3px solid var(--maroon);
            padding-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-export-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-export-bar input[type="text"] {
            flex: 1;
            min-width: 250px;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-export-bar input[type="text"]:focus {
            outline: none;
            border-color: var(--maroon);
            box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.1);
        }

        .search-export-bar button {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--maroon) 0%, var(--dark-maroon) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .search-export-bar button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(128, 0, 32, 0.3);
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .attendance-table thead {
            background: linear-gradient(135deg, var(--maroon) 0%, var(--dark-maroon) 100%);
            color: white;
        }

        .attendance-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        .attendance-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }

        .attendance-table tbody tr:hover {
            background: #f9f9f9;
        }

        .attendance-table td {
            padding: 14px 16px;
            color: #2d2d2d;
            font-size: 0.95rem;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-present {
            background: #d1fae5;
            color: #065f46;
        }

        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .event-select-container {
            margin-bottom: 25px;
        }

        .event-select-container label {
            display: block;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .event-select-container select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .event-select-container select:focus {
            outline: none;
            border-color: var(--maroon);
            box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.1);
        }

        .event-select-container button {
            margin-top: 12px;
            width: 100%;
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--maroon) 0%, var(--dark-maroon) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .event-select-container button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(128, 0, 32, 0.3);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b6b6b;
        }

        .no-data i {
            width: 64px;
            height: 64px;
            color: #d1d1d1;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--maroon) 0%, var(--dark-maroon) 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="dashboard-layout event-head-page">

<?php include('../components/sidebar.php'); ?>

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