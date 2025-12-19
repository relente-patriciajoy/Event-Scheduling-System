<?php
/**
 * OTP Verification Page
 * Handles verification for registration and login
 */
session_start();

require_once('../includes/db.php');
require_once __DIR__ . '/../includes/otp_function.php';

// Check if verification type is set
$verification_type = $_GET['type'] ?? 'registration';

// Check if there's pending registration/login data
if ($verification_type === 'registration' && !isset($_SESSION['pending_registration'])) {
    header("Location: register.php");
    exit();
} elseif ($verification_type === 'login' && !isset($_SESSION['pending_login'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";
$resend_message = "";

// Get email from session
$email = $verification_type === 'registration'
    ? $_SESSION['pending_registration']['email']
    : $_SESSION['pending_login']['email'];

// Handle OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $otp_input = trim($_POST['otp_code'] ?? '');

    if (empty($otp_input)) {
        $error = "Please enter the OTP code.";
    } elseif (strlen($otp_input) !== 6 || !ctype_digit($otp_input)) {
        $error = "Please enter a valid 6-digit OTP code.";
    } else {
        // Verify OTP
        $verification = verifyOTP($conn, $email, $otp_input, $verification_type);
        
        if ($verification['success']) {
            if ($verification_type === 'registration') {
                // Complete registration
                $reg_data = $_SESSION['pending_registration'];

                $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, phone, password, role, status, email_verified, phone_verified) VALUES (?, ?, ?, ?, ?, ?, 'user', 'active', 1, 1)");

                $stmt->bind_param("ssssss",
                    $reg_data['first_name'],
                    $reg_data['middle_name'],
                    $reg_data['last_name'],
                    $reg_data['email'],
                    $reg_data['phone'],
                    $reg_data['password']
                );

                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;

                    // Clear pending registration
                    unset($_SESSION['pending_registration']);
                    unset($_SESSION['otp_id']);

                    // Auto-login after registration
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['full_name'] = trim($reg_data['first_name'] . ' ' . $reg_data['last_name']);
                    $_SESSION['role'] = 'user';
                    $_SESSION['email'] = $reg_data['email'];
                    $_SESSION['login_time'] = time();

                    $stmt->close();
                    header("Location: home.php?welcome=1");
                    exit();
                } else {
                    $error = "Registration failed. Please try again.";
                }
            } else {
                // Complete login
                $login_data = $_SESSION['pending_login'];
                
                // Clear pending login
                unset($_SESSION['pending_login']);
                unset($_SESSION['otp_id']);
                
                // Set session variables
                $_SESSION['user_id'] = $login_data['user_id'];
                $_SESSION['full_name'] = $login_data['full_name'];
                $_SESSION['role'] = $login_data['role'];
                $_SESSION['email'] = $login_data['email'];
                $_SESSION['login_time'] = time();
                
                header("Location: home.php");
                exit();
            }
        } else {
            $error = $verification['message'];
        }
    }
}

// Handle resend OTP
if (isset($_POST['resend_otp'])) {
    if (!canRequestOTP($conn, $email)) {
        $resend_message = "Please wait before requesting another OTP.";
    } else {
        // Get user data
        if ($verification_type === 'registration') {
            $user_data = $_SESSION['pending_registration'];
            $name = trim($user_data['first_name'] . ' ' . $user_data['last_name']);
            $phone = $user_data['phone'];
        } else {
            $user_data = $_SESSION['pending_login'];
            $name = $user_data['full_name'];
            $phone = $user_data['phone'];
        }

        // Generate new OTP
        $otp_result = createOTP($conn, $email, $phone, null, $verification_type);

        if ($otp_result) {
            $delivery = sendOTPDual($email, $phone, $otp_result['otp_code'], $name);
            
            if ($delivery['email'] || $delivery['sms']) {
                $_SESSION['otp_id'] = $otp_result['otp_id'];
                $resend_message = "New OTP code has been sent!";
            } else {
                $resend_message = "Failed to send OTP. Please try again.";
            }
        } else {
            $resend_message = "Failed to generate OTP. Please try again.";
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
    <title>Verify OTP - Eventix</title>
    <link rel="icon" type="image/png" href="../assets/eventix-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/auth.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-box">
        <img src="../assets/eventix-logo.png" alt="Eventix Logo" class="logo" />
        
        <h2>Verify Your Identity</h2>
        <p>We've sent a 6-digit code to:<br><strong><?php echo htmlspecialchars($email); ?></strong></p>
        <p style="font-size: 0.9rem; color: #6b6b6b;">Check your email and SMS messages</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($resend_message)): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                <?php echo htmlspecialchars($resend_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form">
            <div class="input-group">
                <label for="otp_code">Enter 6-Digit Code</label>
                <input
                    type="text"
                    id="otp_code"
                    name="otp_code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    placeholder="000000"
                    required
                    autofocus
                    style="text-align: center; font-size: 1.5rem; letter-spacing: 8px; font-weight: 600;"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                >
            </div>

            <button type="submit" name="verify_otp" class="auth-button">
                Verify Code
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <p style="color: #6b6b6b; font-size: 0.9rem;">
                Didn't receive the code?
            </p>
            <form method="POST" action="" style="display: inline;">
                <button
                    type="submit"
                    name="resend_otp"
                    class="auth-button button-outline"
                    style="margin-top: 10px;"
                >
                    Resend OTP
                </button>
            </form>
        </div>

        <div class="auth-link" style="margin-top: 30px;">
            <a href="<?php echo $verification_type === 'registration' ? 'register.php' : 'index.php'; ?>">
                ← Back to <?php echo $verification_type === 'registration' ? 'Registration' : 'Login'; ?>
            </a>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 8px; font-size: 0.85rem; color: #666;">
            <p style="margin: 0;"><strong>Note:</strong> OTP expires in 5 minutes</p>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    // Auto-submit when 6 digits entered
    const otpInput = document.getElementById('otp_code');
    otpInput.addEventListener('input', function() {
        if (this.value.length === 6) {
            // Optional: auto-submit
            // this.form.submit();
        }
    });

    // Auto-dismiss alerts
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