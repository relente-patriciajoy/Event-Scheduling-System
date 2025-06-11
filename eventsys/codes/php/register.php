<?php
include('includes/db.php');

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role']; // hidden input from form

    $stmt = $conn->prepare("INSERT INTO user (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $email, $phone, $password, $role);

    if ($stmt->execute()) {
        echo "Registration successful! <a href='index.php'>Login</a>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<div class="form-container">
    <h2>Create Account</h2>
    <form method="post">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="text" name="phone" placeholder="Phone Number">
        <input type="password" name="password" placeholder="Password" required>
        <input type="hidden" name="role" value="attendee">
        <button type="submit">Register</button>
        <p>Already have an account? <a href="index.php">Login</a></p>
    </form>
    <p class="message"><?= $message ?></p>
</div>
</body>
</html>