<?php
session_start();
include('../includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please login.");
}

// Check if the user is an event head
$user_id = $_SESSION['user_id'];
$role_check = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$role_check->bind_param("i", $user_id);
$role_check->execute();
$result = $role_check->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['role'] !== 'event_head') {
    die("Access denied. Only Event Heads can promote users.");
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE user SET role = 'event_head' WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
        $message = "✅ User promoted to event head!";
    } else {
        $message = "❌ Error updating role.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Promote User</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="form-container">
    <h2>Promote User to Event Head</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="User's Email" required>
        <button type="submit">Promote</button>
    </form>
    <p><?= $message ?></p>
    <a href="home.php">Back to Dashboard</a>
</body>
</html>