<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('../includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

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
    <title>Event Attendance</title>
    <link rel="stylesheet" href="../css/style.css">
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
        <a href="attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active' : '' ?>">
            <i data-lucide="check-square"></i> Attendance
        </a>
        <a href="logout.php"><i data-lucide="log-out"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <header class="banner">
        <div>
            <h1>Attendance Tracker</h1>
            <p>Check in and out of your events.</p>
        </div>
        <img src="../images/banner-books.png" alt="Banner">
    </header>

    <section class="grid-section">
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
                        <button type="submit" name="check_in">Check In</button>
                    </form>
                <?php elseif ($row['check_in_time'] && !$row['check_out_time']): ?>
                    <form method="post">
                        <input type="hidden" name="registration_id" value="<?= $row['registration_id'] ?>">
                        <button type="submit" name="check_out">Check Out</button>
                    </form>
                <?php else: ?>
                    <p><em>Attendance complete</em></p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </section>
</main>
<script>
    lucide.createIcons();
</script>
</body>
</html>