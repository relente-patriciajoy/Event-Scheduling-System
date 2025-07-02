<?php
session_start();
include('../includes/db.php');

// Handle add/update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $email = $_POST['contact_email'];
    $phone = $_POST['phone'];

    if (isset($_POST['organizer_id']) && $_POST['organizer_id'] !== '') {
        // Update
        $organizer_id = $_POST['organizer_id'];
        $stmt = $conn->prepare("UPDATE organizer SET name=?, contact_email=?, phone=? WHERE organizer_id=?");
        $stmt->bind_param("sssi", $name, $email, $phone, $organizer_id);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_organizers.php?status=updated");
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO organizer (name, contact_email, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $phone);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_organizers.php?status=added");
    }
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM organizer WHERE organizer_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_organizers.php?status=deleted");
    exit();
}

// Fetch for editing
$edit_organizer = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM organizer WHERE organizer_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_organizer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Search
$search = $_GET['search'] ?? '';
$search_param = '%' . $search . '%';

if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM organizer WHERE name LIKE ? OR contact_email LIKE ? OR phone LIKE ?");
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare("SELECT * FROM organizer");
}

$stmt->execute();
$organizers = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Organizers</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container { max-width: 900px; margin: auto; padding: 20px; font-family: 'Poppins', sans-serif; background: #f8f9fc; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        form input, form button { margin: 5px 0; padding: 10px; width: 100%; border-radius: 8px; border: 1px solid #ccc; }
        form button { background: #4f8aff; color: white; border: none; cursor: pointer; }
        form button:hover { background: #3c6fdd; }
        .actions a { margin-right: 8px; color: #0077cc; text-decoration: none; }
        .actions a:hover { text-decoration: underline; }

        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 500;
            position: relative;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert span {
            position: absolute;
            top: 8px;
            right: 15px;
            font-size: 18px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Manage Organizers</h1>

    <!-- Alert Message -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert <?= $_GET['status'] === 'deleted' ? 'alert-info' : 'alert-success' ?>">
            <?php
                if ($_GET['status'] === 'added') echo "Organizer added successfully.";
                elseif ($_GET['status'] === 'updated') echo "Organizer updated successfully.";
                elseif ($_GET['status'] === 'deleted') echo "Organizer deleted.";
            ?>
            <span onclick="this.parentElement.style.display='none';">&times;</span>
        </div>
    <?php endif; ?>

    <form method="GET" style="margin-bottom: 20px;">
        <input type="text" name="search" placeholder="Search organizer..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>

    <h2><?= $edit_organizer ? "Edit Organizer" : "Add Organizer" ?></h2>
    <form method="POST">
        <input type="hidden" name="organizer_id" value="<?= $edit_organizer['organizer_id'] ?? '' ?>">
        <input type="text" name="name" placeholder="Organizer Name" value="<?= $edit_organizer['name'] ?? '' ?>" required>
        <input type="email" name="contact_email" placeholder="Email" value="<?= $edit_organizer['contact_email'] ?? '' ?>" required>
        <input type="text" name="phone" placeholder="Phone" value="<?= $edit_organizer['phone'] ?? '' ?>" required>
        <button type="submit"><?= $edit_organizer ? "Update Organizer" : "Add Organizer" ?></button>
    </form>

    <h2 style="margin-top: 40px;">Organizer List</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($organizers->num_rows > 0): ?>
                <?php while ($row = $organizers->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['contact_email']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td class="actions">
                            <a href="manage_organizers.php?edit=<?= $row['organizer_id'] ?>">Edit</a>
                            <a href="manage_organizers.php?delete=<?= $row['organizer_id'] ?>" onclick="return confirm('Are you sure you want to delete this organizer?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No organizers found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Auto-dismiss alert after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) alert.style.display = 'none';
    }, 5000);
</script>
</body>
</html>