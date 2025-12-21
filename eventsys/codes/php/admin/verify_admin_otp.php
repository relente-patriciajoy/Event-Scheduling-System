<?php
/**
 * Admin OTP Verification Page - DEBUG VERSION
 * Use this temporarily to see what's happening
 */
session_start();

require_once('../../includes/db.php');
require_once('../../includes/otp_function.php');

// Check if there's pending admin login
if (!isset($_SESSION['pending_admin_login'])) {
    header("Location: admin-login.php");
    exit();
}

$error = "";
$resend_message = "";
$debug_info = "";
$email = $_SESSION['pending_admin_login']['email'];

// DEBUG: Show what OTP codes exist in database
$debug_query = $conn->prepare("SELECT otp_id, otp_code, otp_type, expires_at, is_used, created_at FROM otp_code WHERE email = ? ORDER BY created_at DESC LIMIT 5");
$debug_query->bind_param("s", $email);
$debug_query->execute();
$debug_result = $debug_query->get_result();

$debug_info .= "<h3 style='color: #e63946;'>DEBUG INFO FOR: " . htmlspecialchars($email) . "</h3>";
$debug_info .= "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 0.9rem;'>";
$debug_info .= "<tr><th>OTP Code</th><th>Type</th><th>Expires At</th><th>Used?</th><th>Created</th></tr>";

while ($row = $debug_result->fetch_assoc()) {
    $is_expired = strtotime($row['expires_at']) < time() ? 'YES' : 'NO';
    $debug_info .= "<tr>";
    $debug_info .= "<td style='padding: 5px; font-weight: bold; color: blue;'>" . $row['otp_code'] . "</td>";
    $debug_info .= "<td style='padding: 5px;'>" . $row['otp_type'] . "</td>";
    $debug_info .= "<td style='padding: 5px; color: " . ($is_expired === 'YES' ? 'red' : 'green') . ";'>" . $row['expires_at'] . " (Expired: $is_expired)</td>";
    $debug_info .= "<td style='padding: 5px;'>" . ($row['is_used'] ? 'YES' : 'NO') . "</td>";
    $debug_info .= "<td style='padding: 5px;'>" . $row['created_at'] . "</td>";
    $debug_info .= "</tr>";
}
$debug_info .= "</table>";

// Handle OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $otp_input = trim($_POST['otp_code'] ?? '');

    if (empty($otp_input)) {
        $error = "Please enter the OTP code.";
    } elseif (strlen($otp_input) !== 6 || !ctype_digit($otp_input)) {
        $error = "Please enter a valid 6-digit OTP code.";
    } else {
        $debug_info .= "<p style='color: orange;'>Attempting to verify OTP: <strong>$otp_input</strong></p>";
        
        // Try login type first
        $verification = verifyOTP($conn, $email, $otp_input, 'login');
        
        if ($verification['success']) {
            $debug_info .= "<p style='color: green;'>✅ OTP verified successfully with type 'login'</p>";
            
            $login_data = $_SESSION['pending_admin_login'];
            
            // Clear pending login
            unset($_SESSION['pending_admin_login']);
            unset($_SESSION['otp_id']);
            
            // Set admin session variables
            $_SESSION['user_id'] = $login_data['user_id'];
            $_SESSION['full_name'] = $login_data['full_name'];
            $_SESSION['role'] = 'admin';
            $_SESSION['email'] = $login_data['email'];
            $_SESSION['login_time'] = time();
            $_SESSION['is_admin_portal'] = true;
            
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $debug_info .= "<p style='color: red;'>❌ Verification failed: " . $verification['message'] . "</p>";
            $error = $verification['message'];
        }
    }
}

// Handle resend OTP
if (isset($_POST['resend_otp'])) {
    if (!canRequestOTP($conn, $email)) {
        $resend_message = "Please wait before requesting another OTP.";
    } else {
        $user_data = $_SESSION['pending_admin_login'];
        $name = $user_data['full_name'];
        $phone = $user_data['phone'];
        $user_id = $user_data['user_id'];

        $otp_result = createOTP($conn, $email, $phone, $user_id, 'login');

        if ($otp_result) {
            $delivery = sendOTPDual($email, $phone, $otp_result['otp_code'], $name);
            
            if ($delivery['email'] || $delivery['sms']) {
                $_SESSION['otp_id'] = $otp_result['otp_id'];
                $resend_message = "New OTP code has been sent: <strong>" . $otp_result['otp_code'] . "</strong>";
            } else {
                $resend_message = "Failed to send OTP. Please try again.";
            }
        } else {
            $resend_message = "Failed to generate OTP. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Verification DEBUG - Eventix</title>
    <link rel="icon" type="image/png" href="../../assets/eventix-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/auth.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="auth-page">

<div class="auth-container" style="max-width: 800px;">
    <div class="auth-box">
        <img src="../../assets/eventix-logo.png" alt="Eventix Logo" class="logo" />
        
        <h2>🔍 DEBUG MODE</h2>
        <p>Admin OTP Verification - Troubleshooting</p>

        <!-- Debug Information -->
        <div style="background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
            <?= $debug_info ?>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($resend_message)): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                <?php echo $resend_message; ?>
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
                Verify & Access Dashboard
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <form method="POST" action="" style="display: inline;">
                <button
                    type="submit"
                    name="resend_otp"
                    class="auth-button button-outline"
                    style="margin-top: 10px;"
                >
                    Resend OTP (will show code in message)
                </button>
            </form>
        </div>

        <div class="auth-link" style="margin-top: 30px;">
            <a href="admin-login.php">← Back to Admin Login</a>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>

</body>
</html>