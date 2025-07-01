<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include('../includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

$query = "
SELECT e.title, e.start_time, e.end_time, v.name AS venue, r.registration_date, r.status
FROM registration r
JOIN event e ON r.event_id = e.event_id
JOIN venue v ON e.venue_id = v.venue_id
WHERE r.user_id = ?
ORDER BY e.start_time
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
    <title>My Registered Events</title>
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
        <?php if ($role === 'event_head'): ?>
            <a href="manage_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_events.php' ? 'active' : '' ?>">
                <i data-lucide="settings"></i> Manage Events
            </a>
        <?php endif; ?>
        <a href="logout.php"><i data-lucide="log-out"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <header class="banner">
        <div>
            <h1>My Registered Events</h1>
            <p>See all the events you've registered for.</p>
        </div>
        <img src="/eventsys/codes/assets/images-removebg-preview.png" alt="B1G logo" />
    </header>

    <section class="grid-section">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <p><strong>Venue:</strong> <?= htmlspecialchars($row['venue']) ?></p>
                    <p><strong>Date:</strong> <?= $row['start_time'] ?> – <?= $row['end_time'] ?></p>
                    <p><strong>Status:</strong> <?= $row['status'] ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven’t registered for any events yet.</p>
        <?php endif; ?>
    </section>
</main>
<script>
    lucide.createIcons();
</script>
</body>
</html>