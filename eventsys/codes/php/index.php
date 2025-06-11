<?php
include('includes/db.php');
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password_input = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password, full_name FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $hashed_password, $full_name);
        $stmt->fetch();

        if (password_verify($password_input, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            header("Location: home.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<div class="form-container">
    <h2>Login</h2>
    <form method="post">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <p>Don’t have an account? <a href="register.php">Register</a></p>
    </form>
    <p class="error"><?= $error ?></p>
</div>
</body>
</html>