<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include('includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Verify role
$stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'event_head') {
    die("Access denied.");
}

// Fetch events organized by this user
$event_query = $conn->prepare("SELECT event_id, title FROM event WHERE organizer_id = ?");
$event_query->bind_param("i", $user_id);
$event_query->execute();
$events = $event_query->get_result();

// Handle event selection
$selected_event = $_GET['event_id'] ?? null;
$attendances = [];

if ($selected_event) {
    $query = "
        SELECT u.full_name, u.email, a.check_in_time, a.check_out_time, a.status
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
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-layout">
<aside class="sidebar">
    <h2 class="logo">EventSys</h2>
    <nav>
        <a href="home.php">Home</a>
        <a href="events.php">Browse Events</a>
        <a href="my_events.php">My Events</a>
        <a href="manage_events.php">Manage Events</a>
        <a href="view_attendance.php" class="active">View Attendance</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main class="main-content">
    <header class="banner">
        <div>
            <h1>Attendance Records</h1>
            <p>Select an event to view participant attendance.</p>
        </div>
        <img src="images/banner-books.png" alt="Banner">
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
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
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
    <?php endif; ?>
</main>
</body>
</html>
