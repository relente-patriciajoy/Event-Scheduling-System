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

$query = "SELECT 
            e.event_id, 
            e.title, 
            e.description, 
            e.start_time, 
            e.end_time, 
            e.capacity, 
            e.price, 
            v.name AS venue,
            (e.capacity - COUNT(r.registration_id)) AS available_seats
        FROM event e
        JOIN venue v ON e.venue_id = v.venue_id
        LEFT JOIN registration r ON e.event_id = r.event_id
        GROUP BY e.event_id
        ORDER BY e.start_time ASC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Browse Events</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-layout">
<?php include('sidebar.php'); ?>

<main class="main-content">
    <header class="banner">
        <div>
            <h1>Upcoming Events</h1>
            <p>Explore and register for exciting events.</p>
        </div>
        <img src="../assets/eventix-logo.png" alt="Eventix logo" />
    </header>

    <!-- Registration status indicator -->
    <?php
    if (isset($_SESSION['register_status'])) {
        echo '<div id="register-alert" class="alert alert-warning">'
            . htmlspecialchars($_SESSION['register_status']) .
            '</div>';
        unset($_SESSION['register_status']);
        
    }
    ?>

    <div id="overlay_container" class="overlay hidden">
        <div id= "view_details_container" class="view-details-container">
            <h2 id="event_title">Title</h2>
            <p id="event_description">
                Lorem ipsum dolor sit, amet consectetur adipisicing elit. Perferendis, 
                hic laborum possimus suscipit ab error ullam incidunt maxime, explicabo quisquam omnis 
                minima quia inventore nihil beatae aspernatur tenetur sit repellat!
            </p>
            <br>
            <label id="event_start"><strong>Start:</strong> 2025-06-20 22:25:00</label><br>
            <label id="event_end"><strong>End:</strong> 2025-06-24 22:25:00</label>
            <button class="close-button" onClick="showEventDetails()" style="margin-top: 60px">Close</button>
        </div>
    </div>

    <section class="grid-section">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card">
                <h3><?= htmlspecialchars($row['title']) ?></h3>
                <p><strong>Venue:</strong> <?= htmlspecialchars($row['venue']) ?></p>
                <p><strong>Date:</strong>  
                <?= date("M d, Y h:i A", strtotime($row['start_time'])) ?>
                        –<br>   
                <?= date("M d, Y h:i A", strtotime($row['end_time'])) ?></p>
                <p><strong>Price:</strong> $<?= number_format($row['price'], 2) ?></p>
                <p><strong>Available:</strong> <?= htmlspecialchars($row['available_seats'])?> slot/s</p>
                <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                <form method="POST" action="event_register.php">
                    <button type="button" onClick="showEventDetails(<?= $row['event_id']?>)">View Details</button>
                    <input type="hidden" name="capacity" value="<?= $row['capacity'] ?>">
                    <input type="hidden" name="event_id" value="<?= $row['event_id'] ?>">
                    <button type="submit">Register</button>
                </form>
            </div>
        <?php endwhile; ?>
    </section>
</main>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>
<script>
  const alertBox = document.getElementById('register-alert');
  if(alertBox) {
    setTimeout(() => {
        alertBox.style.display = 'none';
    }, 3000);
  }
</script>
<script src="../js/script.js"></script>

</body>
</html>