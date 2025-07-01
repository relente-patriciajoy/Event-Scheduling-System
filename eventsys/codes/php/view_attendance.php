<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include('../includes/db.php');

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

if ($selected_event) {
    $query = "
        SELECT u.first_name, u.middle_name, u.last_name, u.email, a.check_in_time, a.check_out_time, a.status
        FROM registration r
        JOIN user u ON r.user_id = u.user_id
        LEFT JOIN attendance a ON r.registration_id = a.registration_id
        WHERE r.event_id = ?
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
    <title>View Attendance</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="dashboard-layout">
<aside class="sidebar">
    <h2 class="logo">Eventix</h2>
    <nav>
        <a href="home.php" class="<?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">
            <i data-lucide="home"></i> Home
        </a>
        <a href="events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>">
            <i data-lucide="calendar"></i> Browse Events
        </a>
        <a href="my_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'my_events.php' ? 'active' : '' ?>">
            <i data-lucide="user-check"></i> My Events
        </a>
        <a href="manage_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_events.php' ? 'active' : '' ?>">
            <i data-lucide="settings"></i> Manage Events
        </a>
        <a href="view_attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'view_attendance.php' ? 'active' : '' ?>">
            <i data-lucide="eye"></i> View Attendance
        </a>
        <a href="logout.php"><i data-lucide="log-out"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <header class="banner">
        <div>
            <h1>Attendance Records</h1>
            <p>Select an event to view participants’ attendance.</p>
        </div>
        <img src="../assets/eventix-logo.png" alt="Eventix logo" />
    </header>

    <section>
        <form method="GET">
            <label for="event_id"><strong>Select Event:</strong></label>
            <select name="event_id" id="event_id" required>
                <option value="">-- Choose --</option>
                <?php while ($event = $events->fetch_assoc()): ?>
                    <option value="<?= $event['event_id'] ?>" <?= $selected_event == $event['event_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($event['title']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit">View</button>
        </form>
    </section>

    <?php if ($selected_event && $attendances): ?>
        <section class="grid-section" style="margin-top: 30px;">
            <div class="card" style="grid-column: span 2;">
                <h3>Attendance List</h3>

                <input type="text" id="searchInput" placeholder="Search by name or email..." style="width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #ccc;">

               <div style="margin-bottom: 10px;">
                    <button onclick="exportToExcel()" style="margin-right: 10px;">Export to Excel</button>
                    <button onclick="exportToPDF()">Export to PDF</button>
                </div>

                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Name</th>
                            <th>Email</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $attendances->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . $row['last_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= $row['check_in_time'] ?? '—' ?></td>
                                <td><?= $row['check_out_time'] ?? '—' ?></td>
                                <td><?= $row['status'] ?? 'absent' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php elseif ($selected_event): ?>
        <p>No participants yet for this event.</p>
    <?php endif; ?>
</main>

<script src="../js/script.js"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>