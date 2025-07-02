<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include('../includes/db.php');

// Check role (optional: restrict to admin)
$user_id = $_SESSION['user_id'];
$role_stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if ($role !== 'admin') {
    echo "Access denied.";
    exit();
}

// ADD
if (isset($_POST['add_category'])) {
    $name = $_POST['category_name'];
    $desc = $_POST['description'];
    $stmt = $conn->prepare("INSERT INTO event_category (category_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $desc);
    $stmt->execute();
    header("Location: manage_categories.php?status=added");
    exit();
}

// UPDATE
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

// DELETE
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
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
        }

        .form-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        input[type="text"], select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            width: 100%;
        }

        button {
            padding: 10px 16px;
            border: none;
            background: #4f8aff;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
        }

        button:hover {
            background: #3c6fdd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #f9f9f9;
        }

        .alert {
            background: #def;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .actions a {
            margin-right: 8px;
            text-decoration: none;
            color: #0077cc;
        }

        .actions a:hover {
            text-decoration: underline;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-box input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .search-box button {
            padding: 10px 16px;
            background: #4f8aff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            white-space: nowrap;
        }

    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Event Categories</h2>

        <!-- Alert messages -->
        <?php if (isset($_GET['status'])): ?>
            <div class="alert">
                <?php
                    if ($_GET['status'] === 'added') echo "✅ Category added successfully.";
                    elseif ($_GET['status'] === 'updated') echo "✅ Category updated successfully.";
                    elseif ($_GET['status'] === 'deleted') echo "✅ Category deleted.";
                ?>
            </div>
        <?php endif; ?>

        <!-- Search Form -->
        <form method="GET" class="search-box">
            <input type="text" name="search" placeholder="Search category..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>

        <!-- Add / Update Form -->
        <form method="POST">
            <div class="form-group">
                <input type="hidden" name="category_id" value="<?= $_GET['edit_id'] ?? '' ?>">
                <input type="text" name="category_name" placeholder="Category Name" required value="<?= $_GET['edit_name'] ?? '' ?>">
                <input type="text" name="description" placeholder="Description" value="<?= $_GET['edit_desc'] ?? '' ?>">
                <button type="submit" name="<?= isset($_GET['edit_id']) ? 'update_category' : 'add_category' ?>">
                    <?= isset($_GET['edit_id']) ? 'Update' : 'Add' ?>
                </button>
                <?php if (isset($_GET['edit_id'])): ?>
                    <a href="manage_categories.php" style="color: #666; align-self: center;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Category Table -->
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
                        <td class="actions">
                            <a href="manage_categories.php?edit_id=<?= $row['category_id'] ?>&edit_name=<?= urlencode($row['category_name']) ?>&edit_desc=<?= urlencode($row['description']) ?>">Edit</a>
                            <a href="manage_categories.php?delete=<?= $row['category_id'] ?>" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>