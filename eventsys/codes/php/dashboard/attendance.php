<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}

include('../../includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Fetch role (for sidebar)
$stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

// Handle check-in
if (isset($_POST['check_in'])) {
    $registration_id = $_POST['registration_id'];

    $stmt = $conn->prepare("INSERT INTO attendance (registration_id, check_in_time, status) 
                            VALUES (?, NOW(), 'present') 
                            ON DUPLICATE KEY UPDATE check_in_time = NOW(), status = 'present'");
    $stmt->bind_param("i", $registration_id);
    $stmt->execute();
    $stmt->close();
}

// Handle check-out
if (isset($_POST['check_out'])) {
    $registration_id = $_POST['registration_id'];

    $stmt = $conn->prepare("UPDATE attendance SET check_out_time = NOW() WHERE registration_id = ?");
    $stmt->bind_param("i", $registration_id);
    $stmt->execute();
    $stmt->close();
}

// Get upcoming/past registrations
$query = "
SELECT r.registration_id, e.title, e.start_time, e.end_time,
       a.check_in_time, a.check_out_time, a.status
FROM registration r
JOIN event e ON r.event_id = e.event_id
LEFT JOIN attendance a ON r.registration_id = a.registration_id
WHERE r.user_id = ?
ORDER BY e.start_time DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Attendance - Eventix</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <?php if ($role === 'event_head'): ?>
    <link rel="stylesheet" href="../../css/event_head.css">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="dashboard-layout <?= $role === 'event_head' ? 'event-head-page' : '' ?>">
<!-- Sidebar -->
<?php include('../components/sidebar.php'); ?>

<main class="main-content">
    <header class="banner <?= $role === 'event_head' ? 'event-head-banner' : '' ?>">
        <div>
            <?php if ($role === 'event_head'): ?>
            <div class="event-head-badge">
                <i data-lucide="briefcase" style="width: 14px; height: 14px;"></i>
                Event Organizer
            </div>
            <?php endif; ?>
            <h1>Attendance Tracker</h1>
            <p>Check in and out of your events.</p>
        </div>
        <img src="../../assets/eventix-logo.png" alt="Eventix logo" />
    </header>

    <section class="grid-section">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <p><strong>Event Time:</strong><br><?= $row['start_time'] ?> → <?= $row['end_time'] ?></p>
                    <p><strong>Checked In:</strong> <?= $row['check_in_time'] ?? 'Not yet' ?></p>
                    <p><strong>Checked Out:</strong> <?= $row['check_out_time'] ?? 'Not yet' ?></p>
                    <p><strong>Status:</strong> <?= $row['status'] ?? 'absent' ?></p>

                    <?php if (!$row['check_in_time']): ?>
                        <form method="post">
                            <input type="hidden" name="registration_id" value="<?= $row['registration_id'] ?>">
                            <button type="submit" name="check_in">
                                <i data-lucide="log-in" style="width: 16px; height: 16px;"></i>
                                Check In
                            </button>
                        </form>
                    <?php elseif ($row['check_in_time'] && !$row['check_out_time']): ?>
                        <form method="post">
                            <input type="hidden" name="registration_id" value="<?= $row['registration_id'] ?>">
                            <button type="submit" name="check_out">
                                <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                                Check Out
                            </button>
                        </form>
                    <?php else: ?>
                        <p style="color: #059669; font-weight: 600;">
                            <i data-lucide="check-circle" style="width: 16px; height: 16px; vertical-align: middle;"></i>
                            <em>Attendance complete</em>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card">
                <p>You haven't registered for any events yet.</p>
                <a href="events.php" style="display: inline-flex; align-items: center; gap: 8px; margin-top: 15px;">
                    <i data-lucide="search" style="width: 16px; height: 16px;"></i>
                    Browse Events
                </a>
            </div>
        <?php endif; ?>
    </section>
</main>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
  lucide.createIcons();
</script>
</body>
</html>