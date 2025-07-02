<?php
include('../includes/db.php');

// ADD Category
if (isset($_POST['add_category'])) {
    $name = $_POST['category_name'];
    $desc = $_POST['description'];
    $stmt = $conn->prepare("INSERT INTO event_category (category_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $desc);
    $stmt->execute();
    header("Location: manage_categories.php?status=added");
    exit();
}

// UPDATE Category
if (isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $name = $_POST['category_name'];
    $desc = $_POST['description'];
    $stmt = $conn->prepare("UPDATE event_category SET category_name=?, description=? WHERE category_id=?");
    $stmt->bind_param("ssi", $name, $desc, $id);
    $stmt->execute();
    header("Location: manage_categories.php?status=updated");
    exit();
}

// DELETE Category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM event_category WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_categories.php?status=deleted");
    exit();
}

// SEARCH
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM event_category WHERE category_name LIKE ?";
$stmt = $conn->prepare($query);
$searchTerm = "%$search%";
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$categories = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Event Categories</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ccc; }
        .alert { padding: 10px; background: #def; margin-bottom: 10px; }
        form { margin-bottom: 20px; }
    </style>
</head>
<body>

<h2>Event Category Maintenance</h2>

<!-- ALERTS -->
<?php if (isset($_GET['status'])): ?>
    <div class="alert">
        <?php
            if ($_GET['status'] === 'added') echo "Category added.";
            elseif ($_GET['status'] === 'updated') echo "Category updated.";
            elseif ($_GET['status'] === 'deleted') echo "Category deleted.";
        ?>
    </div>
<?php endif; ?>

<!-- SEARCH FORM -->
<form method="GET">
    <input type="text" name="search" placeholder="Search category..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>

<!-- ADD/EDIT FORM -->
<form method="POST">
    <input type="hidden" name="category_id" value="<?= $_GET['edit_id'] ?? '' ?>">
    <input type="text" name="category_name" placeholder="Category Name" required value="<?= $_GET['edit_name'] ?? '' ?>">
    <input type="text" name="description" placeholder="Description" value="<?= $_GET['edit_desc'] ?? '' ?>">
    <button type="submit" name="<?= isset($_GET['edit_id']) ? 'update_category' : 'add_category' ?>">
        <?= isset($_GET['edit_id']) ? 'Update' : 'Add' ?>
    </button>
    <?php if (isset($_GET['edit_id'])): ?>
        <a href="manage_categories.php">Cancel</a>
    <?php endif; ?>
</form>

<!-- DATA TABLE -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Category Name</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $categories->fetch_assoc()): ?>
            <tr>
                <td><?= $row['category_id'] ?></td>
                <td><?= htmlspecialchars($row['category_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td>
                    <a href="manage_categories.php?edit_id=<?= $row['category_id'] ?>&edit_name=<?= urlencode($row['category_name']) ?>&edit_desc=<?= urlencode($row['description']) ?>">Edit</a> |
                    <a href="manage_categories.php?delete=<?= $row['category_id'] ?>" onclick="return confirm('Delete this category?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>