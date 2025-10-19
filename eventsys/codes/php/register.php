<?php
// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

// Include database connection
require_once('../includes/db.php');

// Initialize variables
$errors = [];
$success = "";
$form_data = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Store form data for repopulation
    $form_data = [
        'first_name' => htmlspecialchars($first_name),
        'middle_name' => htmlspecialchars($middle_name),
        'last_name' => htmlspecialchars($last_name),
        'email' => htmlspecialchars($email),
        'phone' => htmlspecialchars($phone)
    ];

    // Validate inputs
    if (empty($first_name)) {
        $errors[] = "First name is required.";
    } elseif (strlen($first_name) < 2) {
        $errors[] = "First name must be at least 2 characters long.";
    }

    if (empty($last_name)) {
        $errors[] = "Last name is required.";
    } elseif (strlen($last_name) < 2) {
        $errors[] = "Last name must be at least 2 characters long.";
    }

    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (!empty($phone) && !preg_match('/^[0-9\s\-\+\(\)]{10,}$/', $phone)) {
        $errors[] = "Please enter a valid phone number.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "This email address is already registered.";
            }
            $stmt->close();
        }
    }

    // If no errors, create account
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user'; // Default role
        $status = 'active';
        $created_at = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, phone, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt) {
            $stmt->bind_param("sssssssss", $first_name, $middle_name, $last_name, $email, $phone, $hashed_password, $role, $status, $created_at);

            if ($stmt->execute()) {
                $success = "Account created successfully! Redirecting to login...";

                // Redirect after 2 seconds
                header("refresh:2;url=index.php");
            } else {
                $errors[] = "An error occurred while creating your account. Please try again.";
            }

            $stmt->close();
        } else {
            $errors[] = "Database error. Please try again later.";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Register - Eventix</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/eventix-logo.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Files -->
    <link rel="stylesheet" href="../css/auth.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="auth-page">

<div class="auth-container register-container">
    <div class="auth-box">
        <!-- Logo -->
        <img src="../assets/eventix-logo.png" alt="Eventix Logo" class="logo" />

        <!-- Heading -->
        <h2>Create Your Account</h2>
        <p>Please fill in the form below to register.</p>

        <!-- Success Message -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 8px;"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" style="text-align: left;">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 8px; margin-bottom: 8px;"></i>
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 8px 0 0 24px; padding: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li style="margin-bottom: 6px;"><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form method="POST" action="" class="auth-form" autocomplete="on">

            <!-- Name Fields Row -->
            <div class="form-row">
                <div class="input-group">
                    <label for="first_name">First Name <span style="color: #e63946;">*</span></label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?php echo $form_data['first_name'] ?? ''; ?>"
                        placeholder="Enter your first name"
                        required
                        autocomplete="given-name"
                    >
                </div>

                <div class="input-group">
                    <label for="middle_name">Middle Name</label>
                    <input
                        type="text"
                        id="middle_name"
                        name="middle_name"
                        value="<?php echo $form_data['middle_name'] ?? ''; ?>"
                        placeholder="Enter your middle name"
                        autocomplete="additional-name"
                    >
                </div>
            </div>

            <!-- Last Name Field -->
            <div class="input-group">
                <label for="last_name">Last Name <span style="color: #e63946;">*</span></label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    value="<?php echo $form_data['last_name'] ?? ''; ?>"
                    placeholder="Enter your last name"
                    required
                    autocomplete="family-name"
                >
            </div>

            <!-- Email Field -->
            <div class="input-group">
                <label for="email">Email Address <span style="color: #e63946;">*</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo $form_data['email'] ?? ''; ?>"
                    placeholder="Enter your email"
                    required
                    autocomplete="email"
                >
            </div>

            <!-- Phone Field -->
            <div class="input-group">
                <label for="phone">Phone Number</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?php echo $form_data['phone'] ?? ''; ?>"
                    placeholder="Enter your phone number"
                    autocomplete="tel"
                >
            </div>

            <!-- Password Field -->
            <div class="input-group">
                <label for="password">Password <span style="color: #e63946;">*</span></label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter a strong password"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword(event, 'password')">
                        <svg id="eye-icon-password" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <small style="color: #6b6b6b; margin-top: 6px; display: block;">
                    At least 8 characters, 1 uppercase letter, and 1 number
                </small>
            </div>

            <!-- Confirm Password Field -->
            <div class="input-group">
                <label for="confirm_password">Confirm Password <span style="color: #e63946;">*</span></label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter your password"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword(event, 'confirm_password')">
                        <svg id="eye-icon-confirm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="auth-button">Register</button>
        </form>

        <!-- Login Link -->
        <div class="auth-link">
            Already have an account? <a href="index.php">Login</a>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Password toggle functionality
    function togglePassword(event, fieldId) {
        event.preventDefault();
        const passwordInput = document.getElementById(fieldId);
        const eyeIcon = document.getElementById('eye-icon-' + (fieldId === 'password' ? 'password' : 'confirm'));

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            passwordInput.type = 'password';
            eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
    }

    // Add loading state to button on submit
    const form = document.querySelector('.auth-form');
    const submitBtn = document.querySelector('.auth-button');

    form.addEventListener('submit', function(e) {
        // Basic client-side validation
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return;
        }

        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    });

    // Remove error message after 8 seconds
    const errorAlert = document.querySelector('.alert-error');
    if (errorAlert) {
        setTimeout(() => {
            errorAlert.style.opacity = '0';
            errorAlert.style.transform = 'translateY(-10px)';
            setTimeout(() => errorAlert.remove(), 300);
        }, 8000);
    }
</script>

</body>
</html>