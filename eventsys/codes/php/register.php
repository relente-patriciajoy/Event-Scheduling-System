<?php
include('../includes/db.php');

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role']; // usually "attendee"

    $stmt = $conn->prepare("INSERT INTO user (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $email, $phone, $password, $role);

    if ($stmt->execute()) {
        $message = "Registration successful! <a href='index.php'>Login</a>";
    } else {
        $message = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eventix Register</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="login-page">

<div class="register-container">
    <div class="login-box">
        <img src="../assets/eventix-logo.png" alt="Eventix Logo" class="logo" style="max-width: 80px; margin-bottom: 20px;" />
        <h2>Create Your Account</h2>
        <p>Please fill in the form below to register.</p>

        <?php if (!empty($message)): ?>
            <p style="color: #f87171; font-size: 0.9rem; margin-bottom: 20px;"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="first-input-group">
							<div class="input-group">
								<label for="full_name">Full Name</label>
								<input type="text" name="full_name" required>
							</div>

							<div class="input-group">
								<label for="email">Email Address</label>
								<input type="email" name="email" required>
							</div>
            </div>

						<div class="second-input-group">
							<div class="input-group">
									<label for="phone">Phone Number</label>
									<input type="text" name="phone">
							</div>

							<div class="input-group">
									<label for="password">Password</label>
									<input type="password" name="password" required>
							</div>
						</div>

            <input type="hidden" name="role" value="attendee">

            <button type="submit">Register</button>
        </form> 

        <div class="register-link">
            Already have an account? <a href="index.php">Login</a>
        </div>
    </div>
</div>

</body>
</html>