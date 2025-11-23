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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Venues - Eventix</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/management.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="dashboard-layout">
  <?php include('sidebar.php'); ?>

  <main class="management-content">
      <!-- Page Header -->
      <div class="management-header">
          <h1>Manage Venues</h1>
          <p>Add, edit, and manage event venues</p>
      </div>

      <!-- Alert Messages -->
      <?php if (isset($_GET['status'])): ?>
          <div class="management-alert <?= $_GET['status'] === 'error' ? 'error' : 'success' ?>">
              <?php
                  if ($_GET['status'] === 'added') echo "✅ Venue added successfully.";
                  elseif ($_GET['status'] === 'updated') echo "✏️ Venue updated successfully.";
                  elseif ($_GET['status'] === 'deleted') echo "🗑️ Venue deleted successfully.";
                  elseif ($_GET['status'] === 'error') echo "❌ Error: Venue might be linked to existing events.";
              ?>
              <span class="close-btn" onclick="this.parentElement.style.display='none';">×</span>
          </div>
      <?php endif; ?>

      <!-- Search Card -->
      <div class="management-card">
          <form method="GET" class="management-search">
              <input
                  type="text"
                  name="search"
                  placeholder="Search by venue name or city..."
                  value="<?= htmlspecialchars($search) ?>"
              >
              <button type="submit" class="btn btn-primary">
                  <i data-lucide="search"></i>
                  Search
              </button>
          </form>
      </div>

      <!-- Add/Edit Form Card -->
      <div class="management-card">
          <h2><?= $edit_venue ? 'Edit Venue' : 'Add New Venue' ?></h2>
          <form method="POST" class="management-form">
              <?php if ($edit_venue): ?>
                  <input type="hidden" name="venue_id" value="<?= $edit_venue['venue_id'] ?>">
              <?php endif; ?>

              <div class="form-group">
                  <label for="name">Venue Name <span class="text-danger">*</span></label>
                  <input
                      type="text"
                      id="name"
                      name="name"
                      placeholder="Enter venue name"
                      value="<?= $edit_venue['name'] ?? '' ?>"
                      required
                  >
              </div>

              <div class="form-row">
                  <div class="form-group">
                      <label for="address">Address <span class="text-danger">*</span></label>
                      <input
                          type="text"
                          id="address"
                          name="address"
                          placeholder="Enter address"
                          value="<?= $edit_venue['address'] ?? '' ?>"
                          required
                      >
                  </div>

                  <div class="form-group">
                      <label for="city">City <span class="text-danger">*</span></label>
                      <input
                          type="text"
                          id="city"
                          name="city"
                          placeholder="Enter city"
                          value="<?= $edit_venue['city'] ?? '' ?>"
                          required
                      >
                  </div>
              </div>

              <div class="form-group">
                  <label for="capacity">Capacity <span class="text-danger">*</span></label>
                  <input
                      type="number"
                      id="capacity"
                      name="capacity"
                      placeholder="Enter maximum capacity"
                      value="<?= $edit_venue['capacity'] ?? '' ?>"
                      min="1"
                      required
                  >
              </div>

              <div class="form-actions">
                  <button type="submit" name="<?= $edit_venue ? 'update_venue' : 'add_venue' ?>" class="btn btn-primary">
                      <i data-lucide="<?= $edit_venue ? 'save' : 'plus' ?>"></i>
                      <?= $edit_venue ? 'Update Venue' : 'Add Venue' ?>
                  </button>
                  <?php if ($edit_venue): ?>
                      <a href="manage_venues.php" class="btn btn-secondary">
                          <i data-lucide="x"></i>
                          Cancel
                      </a>
                  <?php endif; ?>
              </div>
          </form>
      </div>

      <!-- Venues Table Card -->
      <div class="management-card">
          <h2>All Venues</h2>
          <?php if ($venues->num_rows > 0): ?>
              <table class="management-table">
                  <thead>
                      <tr>
                          <th>Venue Name</th>
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
                              <td>
                                  <span class="badge badge-info">
                                      <?= $venue['capacity'] ?> people
                                  </span>
                              </td>
                              <td class="actions">
                                  <a href="manage_venues.php?edit=<?= $venue['venue_id'] ?>" class="btn btn-edit btn-sm">
                                      <i data-lucide="edit"></i>
                                      Edit
                                  </a>
                                  <a
                                      href="manage_venues.php?delete=<?= $venue['venue_id'] ?>"
                                      class="btn btn-delete btn-sm"
                                      onclick="return confirm('Are you sure you want to delete this venue?')"
                                  >
                                      <i data-lucide="trash-2"></i>
                                      Delete
                                  </a>
                              </td>
                          </tr>
                      <?php endwhile; ?>
                  </tbody>
              </table>
          <?php else: ?>
              <div class="empty-state">
                  <i data-lucide="map-pin"></i>
                  <h3>No Venues Found</h3>
                  <p>No venues match your search criteria.</p>
              </div>
          <?php endif; ?>
      </div>
  </main>

  <script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.management-alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
  </script>
</body>
</html>