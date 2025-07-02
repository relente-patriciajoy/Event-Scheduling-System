<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include('../includes/db.php');

// Access control
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

// Add User
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user'])) {
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $first_name, $middle_name, $last_name, $email, $phone, $password, $role);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_users.php?status=added");
    exit();
}

// Update User
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_user'])) {
    $user_id_edit = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE user SET first_name=?, middle_name=?, last_name=?, email=?, phone=?, role=? WHERE user_id=?");
    $stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $email, $phone, $role, $user_id_edit);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_users.php?status=updated");
    exit();
}

// Delete
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_users.php?status=deleted");
    exit();
}

// Search
$search_term = $_GET['search'] ?? '';
$stmt = $conn->prepare("SELECT * FROM user WHERE CONCAT(first_name, ' ', last_name, email, phone) LIKE ?");
$search_param = "%$search_term%";
$stmt->bind_param("s", $search_param);
$stmt->execute();
$users = $stmt->get_result();

// Edit
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 20px;
            background-color: #f8f9fc;
        }
        .card {
            background: white;
            padding: 24px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
        input, select, button {
            padding: 10px;
            margin: 8px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            width: 100%;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background-color: #f2f2f2;
        }
        .btn-edit {
            background-color: #ffa600;
            padding: 6px 12px;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9em;
        }
        .btn-delete {
            background-color: #ff4f4f;
            padding: 6px 12px;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9em;
        }
        .btn-edit:hover { background-color: #cc8400; }
        .btn-delete:hover { background-color: #cc3b3b; }
        .search-box {
            display: flex;
            gap: 12px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            position: relative;
        }
        .alert span {
            position: absolute;
            top: 10px;
            right: 15px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <?php if (isset($_GET['status'])): ?>
        <div class="alert">
            <?php
                if ($_GET['status'] === 'added') echo "✅ User added successfully.";
                elseif ($_GET['status'] === 'updated') echo "✏️ User updated successfully.";
                elseif ($_GET['status'] === 'deleted') echo "🗑️ User deleted successfully.";
            ?>
            <span onclick="this.parentElement.style.display='none';">×</span>
        </div>
    <?php endif; ?>

    <h1>Manage Users</h1>

    <div class="card">
        <form method="GET" class="search-box">
            <input type="text" name="search" placeholder="Search users" value="<?= htmlspecialchars($search_term) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="card">
        <h2><?= $edit_user ? 'Edit User' : 'Add New User' ?></h2>
        <form method="POST">
            <?php if ($edit_user): ?>
                <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?>">
            <?php endif; ?>
            <input type="text" name="first_name" placeholder="First Name" value="<?= $edit_user['first_name'] ?? '' ?>" required>
            <input type="text" name="middle_name" placeholder="Middle Name" value="<?= $edit_user['middle_name'] ?? '' ?>">
            <input type="text" name="last_name" placeholder="Last Name" value="<?= $edit_user['last_name'] ?? '' ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?= $edit_user['email'] ?? '' ?>" required>
            <input type="text" name="phone" placeholder="Phone" value="<?= $edit_user['phone'] ?? '' ?>" required>
            <?php if (!$edit_user): ?>
                <input type="password" name="password" placeholder="Password" required>
            <?php endif; ?>
            <select name="role" required>
                <option value="">-- Select Role --</option>
                <option value="user" <?= (isset($edit_user['role']) && $edit_user['role'] == 'user') ? 'selected' : '' ?>>User</option>
                <option value="event_head" <?= (isset($edit_user['role']) && $edit_user['role'] == 'event_head') ? 'selected' : '' ?>>Event Head</option>
                <option value="admin" <?= (isset($edit_user['role']) && $edit_user['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>
            <button type="submit" name="<?= $edit_user ? 'update_user' : 'add_user' ?>">
                <?= $edit_user ? 'Update User' : 'Add User' ?>
            </button>
        </form>
    </div>

    <div class="card">
        <h2>Existing Users</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['role']) ?></td>
                        <td>
                            <a class="btn-edit" href="manage_users.php?edit=<?= $row['user_id'] ?>">Edit</a>
                            <a class="btn-delete" href="manage_users.php?delete=<?= $row['user_id'] ?>" onclick="return confirm('Delete this user?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>