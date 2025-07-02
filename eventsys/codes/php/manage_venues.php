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
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            margin: 0;
            padding: 0;
        }

        .main-content {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 24px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        h1 {
            font-size: 26px;
            margin-bottom: 20px;
        }

        input, button {
            padding: 10px;
            margin: 8px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            background-color: #4f8aff;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #376fd6;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            position: relative;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert span {
            position: absolute;
            top: 8px;
            right: 15px;
            font-size: 18px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.9em;
            text-decoration: none;
            cursor: pointer;
            color: white;
            display: inline-block;
        }

        .btn-edit {
            background-color: #ffa600;
        }
        .btn-edit:hover {
            background-color: #cc8400;
        }

        .btn-delete {
            background-color: #ff4f4f;
        }
        .btn-delete:hover {
            background-color: #cc3b3b;
        }

        .search-box input {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }

        .actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
    </style>
</head>
<body>
<main class="main-content">
    <div class="card">

        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <div style="display: flex; gap: 12px;">
                <a href="home.php" class="back-link">
                    &larr; Back to Dashboard
                </a>
            </div>
        </div>
        
        <h1>Manage Venues</h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
                <span onclick="this.parentElement.style.display='none';">&times;</span>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-error">
                <?= $error ?>
                <span onclick="this.parentElement.style.display='none';">&times;</span>
            </div>
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
        <form method="GET" class="search-box">
            <input type="text" name="search" placeholder="Search venue by name or city" value="<?= htmlspecialchars($search) ?>">
        </form>

        <table>
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
                        <td class="actions">
                            <a class="btn-edit" href="?edit=<?= $venue['venue_id'] ?>">Edit</a>
                            <a class="btn-delete" href="?delete=<?= $venue['venue_id'] ?>" onclick="return confirm('Are you sure you want to delete this venue?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    // Auto-dismiss alerts
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => alert.style.display = 'none');
    }, 5000);
</script>
</body>
</html>