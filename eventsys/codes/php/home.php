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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="dashboard-layout">
  <aside class="sidebar">
      <h2 class="logo">EventSys</h2>
      <nav>
          <a href="home.php" class="active">Home</a>
          <a href="events.php">Browse Events</a>
          <a href="my_events.php">My Events</a>
          <a href="attendance.php">Attendance</a>
          <?php if ($role === 'event_head'): ?>
            <a href="manage_events.php">Manage Events</a>
          <?php endif; ?>
          <?php if ($role === 'event_head'): ?>
            <a href="view_attendance.php">View Attendance</a>
          <?php endif; ?>

          <a href="logout.php">Logout</a>
      </nav>
  </aside>

  <main class="main-content">
      <header class="banner">
          <div>
              <h1>Hi, <?= htmlspecialchars($full_name) ?></h1>
              <p>Welcome to your dashboard. Let’s manage and discover events easily.</p>
          </div>
          <img src="images/banner-books.png" alt="Books" />
      </header>

      <section class="grid-section">
        <div class="card">
          <h3>Browse Events</h3>
          <p>Find and register for upcoming events.</p>
          <a href="events.php">Explore</a>
        </div>

        <div class="card">
          <h3>My Registrations</h3>
          <p>View events you’ve registered for.</p>
          <a href="my_events.php">View</a>
        </div>

        <?php if ($role === 'event_head'): ?>
        <div class="card">
            <h3>Manage Events</h3>
            <p>Create and update the events you organize.</p>
            <a href="manage_events.php">Manage</a>
        </div>
        <?php endif; ?>
      </section>
  </main>
</body>
</html>