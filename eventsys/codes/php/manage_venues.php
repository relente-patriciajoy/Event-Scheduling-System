<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('../includes/db.php');

$user_id = $_SESSION['user_id'];
$success = $error = "";
$edit_venue = null;

// Check user role
$role_stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if ($role !== 'admin' && $role !== 'event_head') {
    die("Access denied.");
}

// Handle Add Venue
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_venue'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $capacity = $_POST['capacity'];

    $stmt = $conn->prepare("INSERT INTO venue (name, address, city, capacity) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $name, $address, $city, $capacity);

    if ($stmt->execute()) {
        $success = "Venue added successfully.";
    } else {
        $error = "Error adding venue.";
    }
    $stmt->close();
}

// Handle Edit Load
if (isset($_GET['edit'])) {
    $venue_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM venue WHERE venue_id = ?");
    $stmt->bind_param("i", $venue_id);
    $stmt->execute();
    $edit_venue = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_venue'])) {
    $venue_id = $_POST['venue_id'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $capacity = $_POST['capacity'];

    $stmt = $conn->prepare("UPDATE venue SET name = ?, address = ?, city = ?, capacity = ? WHERE venue_id = ?");
    $stmt->bind_param("sssii", $name, $address, $city, $capacity, $venue_id);

    if ($stmt->execute()) {
        $success = "Venue updated successfully.";
        $edit_venue = null;
    } else {
        $error = "Error updating venue.";
    }
    $stmt->close();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $venue_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM venue WHERE venue_id = ?");
    $stmt->bind_param("i", $venue_id);

    if ($stmt->execute()) {
        $success = "Venue deleted.";
    } else {
        $error = "Error: Venue might be linked to existing events.";
    }
    $stmt->close();
}

// Search
$search = $_GET['search'] ?? '';
$search_query = "SELECT * FROM venue";
if (!empty($search)) {
    $search_query .= " WHERE name LIKE ? OR city LIKE ?";
    $stmt = $conn->prepare($search_query);
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $venues = $stmt->get_result();
    $stmt->close();
} else {
    $venues = $conn->query("SELECT * FROM venue");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Venues</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-layout">
<main class="main-content">
    <div class="navbar-container">
        <h1>Manage Venues</h1>
    </div>

    <div class="card" style="margin-bottom: 30px;">
        <?php if ($success): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php elseif ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
            <?php if ($edit_venue): ?>
                <input type="hidden" name="venue_id" value="<?= $edit_venue['venue_id'] ?>">
            <?php endif; ?>
            <input type="text" name="name" placeholder="Venue Name" value="<?= $edit_venue['name'] ?? '' ?>" required>
            <input type="text" name="address" placeholder="Address" value="<?= $edit_venue['address'] ?? '' ?>" required>
            <input type="text" name="city" placeholder="City" value="<?= $edit_venue['city'] ?? '' ?>" required>
            <input type="number" name="capacity" placeholder="Capacity" value="<?= $edit_venue['capacity'] ?? '' ?>" required>
            <button type="submit" name="<?= $edit_venue ? 'update_venue' : 'add_venue' ?>">
                <?= $edit_venue ? 'Update Venue' : 'Add Venue' ?>
            </button>
        </form>
    </div>

    <div class="card">
        <form method="GET" style="margin-bottom: 20px;">
            <input type="text" name="search" placeholder="Search venue by name or city" value="<?= htmlspecialchars($search) ?>" style="width: 100%; padding: 10px;">
        </form>

        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Capacity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($venue = $venues->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($venue['name']) ?></td>
                        <td><?= htmlspecialchars($venue['address']) ?></td>
                        <td><?= htmlspecialchars($venue['city']) ?></td>
                        <td><?= $venue['capacity'] ?></td>
                        <td>
                            <a href="?edit=<?= $venue['venue_id'] ?>">Edit</a> |
                            <a href="?delete=<?= $venue['venue_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>