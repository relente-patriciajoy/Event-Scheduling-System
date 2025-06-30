<?php
include('../includes/db.php');
session_start();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password, role FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;

            // Optional: redirect based on role
            header("Location: index.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No user found with that email.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Eventix Login</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="login-page">

  <div class="login-container">
    <div class="login-box">
      <img src="assets/eventix-logo.png" alt="Eventix Logo" class="logo" style="max-width: 80px; margin-bottom: 20px;" />
      <h2>Welcome Back!</h2>
      <p>Please login to Eventix with your email address</p>

      <?php if (!empty($error)): ?>
        <p style="color: #f87171; font-size: 0.9rem; margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form action="login.php" method="POST">
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

        <button type="submit">SIGN IN</button>
      </form>

      <div class="register-link">
        Don’t have an account? <a href="register.php">Sign Up</a>
      </div>
    </div>
  </div>

</body>
</html>