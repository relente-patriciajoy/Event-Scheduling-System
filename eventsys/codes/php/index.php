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
$error = "";
$email_value = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize inputs
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password_input = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Store email for form repopulation
    $email_value = htmlspecialchars($email);
    
    // Validate inputs
    if (empty($email) || empty($password_input)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT user_id, password, first_name, middle_name, last_name, role FROM user WHERE email = ? AND status = 'active'");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $hashed_password, $first_name, $middle_name, $last_name, $role);
                $stmt->fetch();
                
                // Verify password
                if (password_verify($password_input, $hashed_password)) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['full_name'] = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
                    $_SESSION['role'] = $role;
                    $_SESSION['email'] = $email;
                    $_SESSION['login_time'] = time();
                    
                    // Handle "Remember Me"
                    if ($remember) {
                        // Set cookie for 30 days
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), "/", "", true, true);
                        
                        // Store token in database (you'll need a remember_tokens table)
                        // $stmt_token = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)");
                        // $expires = date('Y-m-d H:i:s', time() + (86400 * 30));
                        // $stmt_token->bind_param("iss", $user_id, $token, $expires);
                        // $stmt_token->execute();
                    }
                    
                    // Log successful login (optional)
                    // $stmt_log = $conn->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
                    // $ip = $_SERVER['REMOTE_ADDR'];
                    // $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    // $stmt_log->bind_param("iss", $user_id, $ip, $user_agent);
                    // $stmt_log->execute();
                    
                    // Redirect to dashboard
                    header("Location: home.php");
                    exit();
                } else {
                    $error = "Incorrect password. Please try again.";
                }
            } else {
                $error = "No account found with this email address.";
            }
            
            $stmt->close();
        } else {
            $error = "An error occurred. Please try again later.";
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
    <title>Login - Eventix</title>
    
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

<div class="auth-container">
    <div class="auth-box">
        <!-- Logo -->
        <img src="../assets/eventix-logo.png" alt="Eventix Logo" class="logo" />
        
        <!-- Heading -->
        <h2>Welcome Back!</h2>
        <p>Please login to <strong>Eventix</strong> with your email address</p>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 8px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" class="auth-form" autocomplete="on">
            <!-- Email Input -->
            <div class="input-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo $email_value; ?>"
                    placeholder="Enter your email"
                    required 
                    autofocus
                    autocomplete="email"
                >
            </div>

            <!-- Password Input -->
            <div class="input-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword(event)">
                        <svg id="eye-icon" data-lucide="eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <!-- Options Row -->
            <div class="options">
                <label class="remember-label">
                    <input type="checkbox" name="remember" class="remember-checkbox"> 
                    <span>Stay logged in</span>
                </label>
                <a href="forgot_password.php">Forgot password?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="auth-button">Login</button>
        </form>

        <!-- Register Link -->
        <div class="auth-link">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Password toggle functionality
    function togglePassword(event) {
        event.preventDefault();
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        
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
    
    form.addEventListener('submit', function() {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    });
    
    // Remove error message after 5 seconds
    const errorAlert = document.querySelector('.alert-error');
    if (errorAlert) {
        setTimeout(() => {
            errorAlert.style.opacity = '0';
            errorAlert.style.transform = 'translateY(-10px)';
            setTimeout(() => errorAlert.remove(), 300);
        }, 5000);
    }
</script>

</body>
</html>