<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}
include('../../includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

$query = "
SELECT e.title, e.start_time, e.end_time, v.name AS venue, r.registration_date, r.table_number, r.status
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
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="dashboard-layout">
<?php include('../components/sidebar.php'); ?>

<main class="main-content">
    <header class="banner">
        <div>
            <h1>My Registered Events</h1>
            <p>See all the events you've registered for.</p>
        </div>
        <img src="../../assets/eventix-logo.png" alt="Eventix logo" />
    </header>

    <section class="grid-section">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <p><strong>Venue:</strong> <?= htmlspecialchars($row['venue']) ?></p>
                    <p><strong>Date:</strong> <?= $row['start_time'] ?> – <?= $row['end_time'] ?></p>
                    <p><strong>Table Number:</strong> <?= $row['table_number']?></p>
                    <p><strong>Status:</strong> <?= $row['status'] ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven’t registered for any events yet.</p>
        <?php endif; ?>
    </section>
</main>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
  lucide.createIcons();
</script>
</body>
</html>