<?php
/**
 * ADMIN LOGIN PAGE - Secret Portal
 * Access: /admin-login
 * Only for administrators
 */
session_start();

// Redirect if already logged in as admin
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

require_once('../../includes/db.php');
require_once('../../includes/otp_function.php');

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
        // Verify admin credentials - ONLY ADMIN ROLE ALLOWED
        $stmt = $conn->prepare("SELECT user_id, password, first_name, middle_name, last_name, phone, role FROM user WHERE email = ? AND status = 'active' AND role = 'admin'");
        
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
                        // Store admin login data in session temporarily
                        $full_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);

                        $_SESSION['pending_admin_login'] = [
                            'user_id' => $user_id,
                            'full_name' => $full_name,
                            'email' => $email,
                            'phone' => $phone,
                            'role' => 'admin',
                            'remember' => $remember,
                            'timestamp' => time()
                        ];

                        // Generate and send OTP - Use 'login' type for compatibility
                        $otp_result = createOTP($conn, $email, $phone, $user_id, 'login');

                        if ($otp_result) {
                            $delivery = sendOTPDual($email, $phone, $otp_result['otp_code'], $full_name);

                            if ($delivery['email'] || $delivery['sms']) {
                                $_SESSION['otp_id'] = $otp_result['otp_id'];
                                header("Location: verify_admin_otp.php");
                                exit();
                            } else {
                                $error = "Failed to send OTP. Please try again.";
                            }
                        } else {
                            $error = "Failed to generate OTP. Please try again.";
                        }
                    }
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "Access denied. This portal is for administrators only.";
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
    <title>Admin Portal - Eventix</title>
    <link rel="icon" type="image/png" href="../../assets/eventix-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/auth.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Admin-specific styling */
        .admin-badge {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #e63946;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .auth-box h2 {
            background: linear-gradient(135deg, #1a1a1a 0%, #e63946 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .security-notice {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #856404;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
    </style>
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-box">
        <img src="../../assets/eventix-logo.png" alt="Eventix Logo" class="logo" />
        
        <div class="admin-badge">
            <i data-lucide="shield" style="width: 16px; height: 16px;"></i>
            Admin Portal
        </div>
        
        <h2>Administrator Access</h2>
        <p>Secure login for <strong>system administrators</strong> only</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form" autocomplete="on">
            <div class="input-group">
                <label for="email">Admin Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo $email_value; ?>"
                    placeholder="Enter your admin email"
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
                    <span>Keep me logged in</span>
                </label>
            </div>

            <button type="submit" class="auth-button">
                <i data-lucide="shield-check" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 8px;"></i>
                Access Admin Panel
            </button>

            <div class="security-notice">
                <i data-lucide="lock" style="width: 18px; height: 18px; flex-shrink: 0; margin-top: 2px;"></i>
                <span>This is a secure area. You'll receive a verification code via email and SMS after login.</span>
            </div>
        </form>

        <div class="auth-link">
            Regular user? <a href="../auth/index.php">Login here</a>
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