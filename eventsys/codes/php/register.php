<?php
include('../includes/db.php');

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$first_name = $_POST['first_name'];	
	$middle_name = $_POST['middle_name'];
	$last_name = $_POST['last_name'];
	$email = $_POST['email'];
	$phone = $_POST['phone'];
	$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
	$role = $_POST['role']; // usually "attendee"

	$stmt = $conn->prepare("INSERT INTO user (first_name, last_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
	$stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone, $password, $role);

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
	<div class="register-box">
		<img src="../assets/eventix-logo.png" alt="Eventix Logo" class="logo" style="max-width: 80px;" />
		<h2>Create Your Account</h2>
		<p>Please fill in the form below to register.</p>

		<?php if (!empty($message)): ?>
			<p style="color: #f87171; font-size: 0.9rem; margin-bottom: 20px;"><?php echo $message; ?></p>
		<?php endif; ?>

		<form method="post">
			<div class="first-input-group">
				<div class="input-group">
					<label for="first_name">First Name</label>
					<input type="text" name="first_name" required>
				</div>

				<div class="input-group">
					<label for="middle_name">Middle Name</label>
					<input type="text" name="middle_name" required>
				</div>

				<div class="input-group">
					<label for="last_name">Last Name</label>
					<input type="text" name="last_name" required>
				</div>
			</div>

			<div class="second-input-group">
				<div class="input-group">
					<label for="email">Email Address</label>
					<input type="email" name="email" class="max-width wide-input" required>
				</div>
			</div>

			<div class="third-input-group">
					<div class="input-group">
					<label for="phone">Phone Number</label>
					<input type="text" name="phone" class="max-width wide-input">
				</div>
			</div>

			<div class="fourth-input-group">
				<div class="input-group">
					<label for="password">Password</label>
					<input type="password" name="password" class="max-width wide-input" required>
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