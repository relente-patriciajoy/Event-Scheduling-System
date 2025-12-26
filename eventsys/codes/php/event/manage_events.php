<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}
include('../../includes/db.php');

$user_id = $_SESSION['user_id'];

// Check role
$role_stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if ($role !== 'event_head') {
    echo "Access denied.";
    exit();
}

// Handle add event
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_event'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $venue_name = $_POST['venue_name'];
    $venue_address = $_POST['venue_address'];
    $venue_city = $_POST['venue_city'];
    $capacity = $_POST['capacity'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];

    // Insert venue and get ID
    $venue_stmt = $conn->prepare("INSERT INTO venue (name, address, city) VALUES (?, ?, ?)");
    $venue_stmt->bind_param("sss", $venue_name, $venue_address, $venue_city);
    $venue_stmt->execute();
    $venue_id = $venue_stmt->insert_id;
    $venue_stmt->close();

    // Fetch user's info
    $user_stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, phone FROM user WHERE user_id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_stmt->bind_result($first_name, $middle_name, $last_name, $email, $phone);
    $user_stmt->fetch();
    $user_stmt->close();

    // Create full name from user info
    $full_name = trim("$first_name $middle_name $last_name");

    // Check or insert organizer
    $org_stmt = $conn->prepare("SELECT organizer_id FROM organizer WHERE contact_email = ?");
    $org_stmt->bind_param("s", $email);
    $org_stmt->execute();
    $org_stmt->bind_result($organizer_id);
    $org_stmt->fetch();
    $org_stmt->close();

    if (!$organizer_id) {
        $insert_org = $conn->prepare("INSERT INTO organizer (name, contact_email, phone) VALUES (?, ?, ?)");
        $insert_org->bind_param("sss", $full_name, $email, $phone);
        $insert_org->execute();
        $organizer_id = $insert_org->insert_id;
        $insert_org->close();
    }

    // Insert event
    $stmt = $conn->prepare("INSERT INTO event (title, description, start_time, end_time, venue_id, organizer_id, capacity, price, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiiidi", $title, $description, $start_time, $end_time, $venue_id, $organizer_id, $capacity, $price, $category_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_events.php");
    exit();
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_event'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $capacity = $_POST['capacity'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $event_id = $_POST['event_id'];

    $stmt = $conn->prepare("UPDATE event SET title=?, description=?, start_time=?, end_time=?, capacity=?, price=?, category_id=? WHERE event_id=? AND organizer_id IN (SELECT organizer_id FROM organizer WHERE contact_email = (SELECT email FROM user WHERE user_id = ?))");
    $stmt->bind_param("ssssiddii", $title, $description, $start_time, $end_time, $capacity, $price, $category_id, $event_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_events.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM event WHERE event_id = ? AND organizer_id IN (SELECT organizer_id FROM organizer WHERE contact_email = (SELECT email FROM user WHERE user_id = ?))");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_events.php");
    exit();
}

// Fetch event to edit
$edit_event = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM event WHERE event_id = ? AND organizer_id IN (SELECT organizer_id FROM organizer WHERE contact_email = (SELECT email FROM user WHERE user_id = ?))");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $edit_event = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch categories
$category_result = $conn->query("SELECT category_id, category_name FROM event_category");

// Fetch own events
$stmt = $conn->prepare("SELECT e.event_id, e.title, e.start_time, e.end_time, v.name AS venue FROM event e JOIN venue v ON e.venue_id = v.venue_id JOIN organizer o ON e.organizer_id = o.organizer_id JOIN user u ON o.contact_email = u.email WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage My Events</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/event_head.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="dashboard-layout event-head-page">

<!-- Sidebar -->
<?php include('../components/sidebar.php'); ?>

<main class="main-content">
    <!-- Event Head Banner -->
    <header class="banner event-head-banner">
        <div>
            <div class="event-head-badge">
                <i data-lucide="briefcase" style="width: 14px; height: 14px;"></i>
                Event Organizer
            </div>
            <h1>Manage My Events</h1>
            <p>Create, edit, and manage your organized events</p>
        </div>
        <img src="../../assets/eventix-logo.png" alt="Eventix logo" />
    </header>

    <div class="dashboard">
        <div class="manage-events-container">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <div style="display: flex; gap: 12px;">
                    <a href="../dashboard/home.php" class="back-link">
                        <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                        Back to Dashboard
                    </a>
                    <a href="view_attendance.php" class="attendance-link">
                        <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                        View Attendance
                    </a>
                </div>
            </div>

            <h2>
                <i data-lucide="<?= $edit_event ? 'edit' : 'plus-circle' ?>"></i>
                <?= $edit_event ? "Edit Event" : "Create New Event" ?>
            </h2>

            <form method="POST">
                <input type="hidden" name="event_id" value="<?= $edit_event['event_id'] ?? '' ?>">
                <input type="text" name="title" placeholder="Event Title" value="<?= $edit_event['title'] ?? '' ?>" required>
                <textarea name="description" placeholder="Event Description" required><?= $edit_event['description'] ?? '' ?></textarea>
                <input type="datetime-local" name="start_time" value="<?= $edit_event['start_time'] ?? '' ?>" required>
                <input type="datetime-local" name="end_time" value="<?= $edit_event['end_time'] ?? '' ?>" required>

                <?php if (!$edit_event): ?>
                    <input type="text" name="venue_name" placeholder="Venue Name" required>
                    <input type="text" name="venue_address" placeholder="Venue Address">
                    <input type="text" name="venue_city" placeholder="Venue City">
                <?php else: ?>
                    <p style="background: #f9f9f9; padding: 12px; border-radius: 8px; border-left: 4px solid var(--maroon); color: #6b6b6b;">
                        <i data-lucide="info" style="width: 16px; height: 16px; vertical-align: middle;"></i>
                        <em>Venue cannot be edited for existing events.</em>
                    </p>
                <?php endif; ?>

                <input type="number" name="capacity" placeholder="Capacity" value="<?= $edit_event['capacity'] ?? '' ?>" required>
                <input type="number" step="0.01" name="price" placeholder="Price" value="<?= $edit_event['price'] ?? '' ?>" required>

                <select name="category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php
                    $category_result->data_seek(0);
                    while ($cat = $category_result->fetch_assoc()):
                    ?>
                        <option value="<?= $cat['category_id'] ?>"
                            <?= (isset($edit_event['category_id']) && $edit_event['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <div style="display: flex; gap: 12px; margin-top: 10px;">
                    <?php if ($edit_event): ?>
                        <button type="submit" name="update_event" style="flex: 1;">
                            <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                            Update Event
                        </button>
                        <a href="manage_events.php" style="flex: 1; padding: 12px 20px; background: #f0f0f0; color: #6b6b6b; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                            Cancel
                        </a>
                    <?php else: ?>
                        <button type="submit" name="add_event" style="width: 100%;">
                            <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            Add Event
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="my-events-container">
            <h2>
                <i data-lucide="calendar"></i>
                My Events
            </h2>
            <div class="event-list">
                <?php while ($row = $events->fetch_assoc()): ?>
                    <div class="event-card">
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p>
                            <i data-lucide="map-pin" style="width: 16px; height: 16px; vertical-align: middle; color: var(--maroon);"></i>
                            <strong>Venue:</strong> <?= htmlspecialchars($row['venue']) ?>
                        </p>
                        <p>
                            <i data-lucide="calendar" style="width: 16px; height: 16px; vertical-align: middle; color: var(--maroon);"></i>
                            <strong>From:</strong> <?= $row['start_time'] ?>
                        </p>
                        <p>
                            <i data-lucide="clock" style="width: 16px; height: 16px; vertical-align: middle; color: var(--maroon);"></i>
                            <strong>To:</strong> <?= $row['end_time'] ?>
                        </p>
                        <div class="event-actions" style="margin-top: 18px;">
                            <a href="manage_events.php?edit=<?= $row['event_id'] ?>" class="edit-link">
                                <i data-lucide="edit" style="width: 16px; height: 16px;"></i>
                                Edit
                            </a>
                            <a href="manage_events.php?delete=<?= $row['event_id'] ?>" class="delete-link" onclick="return confirm('Are you sure?')">
                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                Delete
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>