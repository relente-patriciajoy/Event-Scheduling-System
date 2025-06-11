<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

$query = "SELECT e.event_id, e.title, e.description, e.start_time, e.end_time, e.price, v.name AS venue 
          FROM event e
          JOIN venue v ON e.venue_id = v.venue_id
          ORDER BY e.start_time ASC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Events</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-layout">
<aside class="sidebar">
    <h2 class="logo">EventSys</h2>
    <nav>
        <a href="home.php">Home</a>
        <a class="active" href="events.php">Browse Events</a>
        <a href="my_events.php">My Events</a>
				<a href="attendance.php">Attendance</a>
        <?php if ($role === 'event_head'): ?>
            <a href="manage_events.php">Manage Events</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main class="main-content">
    <header class="banner">
        <div>
            <h1>Upcoming Events</h1>
            <p>Explore and register for exciting events.</p>
        </div>
        <img src="images/banner-books.png" alt="Banner">
    </header>

    <section class="grid-section">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card">
                <h3><?= htmlspecialchars($row['title']) ?></h3>
                <p><strong>Venue:</strong> <?= htmlspecialchars($row['venue']) ?></p>
                <p><strong>Date:</strong> <?= $row['start_time'] ?> – <?= $row['end_time'] ?></p>
                <p><strong>Price:</strong> $<?= number_format($row['price'], 2) ?></p>
                <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                <form method="POST" action="event_register.php">
                    <input type="hidden" name="event_id" value="<?= $row['event_id'] ?>">
                    <button type="submit">Register</button>
                </form>
            </div>
        <?php endwhile; ?>
    </section>
</main>
</body>
</html>