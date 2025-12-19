<?php
/**
 * Login Page with OTP Authentication
 * Step 1: Verify password, then send OTP
 */
session_start();

// Check if user just logged out
$just_logged_out = isset($_SESSION['just_logged_out']);
if ($just_logged_out) {
    unset($_SESSION['just_logged_out']);
}

// Only redirect if logged in AND didn't just log out
if (isset($_SESSION['user_id']) && !$just_logged_out) {
    header("Location: home.php");
    exit();
}

require_once('../includes/db.php');
require_once __DIR__ . '/../includes/otp_function.php';

$error = "";
$email_value = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password_input = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    $email_value = htmlspecialchars($email);

    if (empty($email) || empty($password_input)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Verify user credentials
        $stmt = $conn->prepare("SELECT user_id, password, first_name, middle_name, last_name, phone, role FROM user WHERE email = ? AND status = 'active'");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $hashed_password, $first_name, $middle_name, $last_name, $phone, $role);
                $stmt->fetch();

                // Verify password
                if (password_verify($password_input, $hashed_password)) {
                    $stmt->close();

                    // Check OTP rate limiting
                    if (!canRequestOTP($conn, $email)) {
                        $error = "Too many OTP requests. Please wait before trying again.";
                    } else {
                        // Store login data in session temporarily
                        $full_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);

                        $_SESSION['pending_login'] = [
                            'user_id' => $user_id,
                            'full_name' => $full_name,
                            'email' => $email,
                            'phone' => $phone,
                            'role' => $role,
                            'remember' => $remember,
                            'timestamp' => time()
                        ];

                        // Generate and send OTP
                        $otp_result = createOTP($conn, $email, $phone, $user_id, 'login');

                        if ($otp_result) {
                            $delivery = sendOTPDual($email, $phone, $otp_result['otp_code'], $full_name);

                            if ($delivery['email'] || $delivery['sms']) {
                                $_SESSION['otp_id'] = $otp_result['otp_id'];
                                header("Location: verify_otp.php?type=login");
                                exit();
                            } else {
                                $error = "Failed to send OTP. Please try again.";
                            }
                        } else {
                            $error = "Failed to generate OTP. Please try again.";
                        }
                    }
                } else {
                    $error = "Incorrect password. Please try again.";
                }
            } else {
                $error = "No account found with this email address.";
            }

            if ($stmt) {
                $stmt->close();
            }
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
    <title>Login - Eventix</title>
    <link rel="icon" type="image/png" href="../assets/eventix-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/auth.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-box">
        <img src="../assets/eventix-logo.png" alt="Eventix Logo" class="logo" />
        
        <h2>Welcome Back!</h2>
        <p>Login to <strong>Eventix</strong> with OTP verification</p>

        <?php if ($just_logged_out): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                You have been successfully logged out.
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form" autocomplete="on">
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
                        <svg id="eye-icon" data-lucide="eye"></svg>
                    </button>
                </div>
            </div>

            <div class="options">
                <label class="remember-label">
                    <input type="checkbox" name="remember" class="remember-checkbox">
                    <span>Stay logged in</span>
                </label>
                <a href="forgot_password.php">Forgot password?</a>
            </div>

            <button type="submit" class="auth-button">
                Continue to Verification
            </button>

            <div style="margin-top: 15px; padding: 12px; background: #f0f0f0; border-radius: 8px; font-size: 0.85rem; color: #666; text-align: left;">
                <p style="margin: 0; display: flex; align-items: flex-start; gap: 8px;">
                    <i data-lucide="shield-check" style="width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px;"></i>
                    <span>After entering your password, you'll receive a verification code via SMS and email.</span>
                </p>
            </div>
        </form>

        <div class="auth-link">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    
    function togglePassword(event) {
        event.preventDefault();
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.setAttribute('data-lucide', 'eye-off');
        } else {
            passwordInput.type = 'password';
            eyeIcon.setAttribute('data-lucide', 'eye');
        }

        lucide.createIcons();
    }

    const form = document.querySelector('.auth-form');
    const submitBtn = document.querySelector('.auth-button');

    form.addEventListener('submit', function() {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    });

    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
</script>

</body>
</html>