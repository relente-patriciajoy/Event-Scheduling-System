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

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eventix Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">

<div class="login-container">
    <div class="login-box">
        <img src="assets/eventix-logo.png" alt="Eventix Logo" class="logo" style="max-width: 80px; margin-bottom: 20px;" />
        <h2>Welcome Back!</h2>
        <p>Please login to Eventix with your email address</p>

        <?php if (!empty($error)): ?>
            <p style="color: #f87171; font-size: 0.9rem; margin-bottom: 20px;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="options">
                <label><input type="checkbox" name="remember"> Stay logged in</label>
                <a href="#">Forgot your password?</a>
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="register-link">
            Don’t have an account? <a href="register.php">Register</a>
        </div>
    </div>
</div>

</body>
</html>