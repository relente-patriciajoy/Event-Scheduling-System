<?php
/**
 * OTP Functions for Eventix System
 * Handles OTP generation, validation, and delivery via SMS and Email
 *
 * CURRENT STATUS: Email OTP ENABLED | SMS OTP DISABLED (for future use)
 */

// OTP Configuration
define('OTP_LENGTH', 6);
define('OTP_EXPIRY_MINUTES', 5);
define('MAX_OTP_ATTEMPTS', 3);

/**
 * Generate a random 6-digit OTP code
 */
function generateOTP() {
    return str_pad(rand(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
}

/**
 * Create and store OTP in database
 * 
 * @param mysqli $conn Database connection
 * @param string $email User email
 * @param string $phone User phone (optional)
 * @param int $user_id User ID (null for registration)
 * @param string $type OTP type (registration, login, reset_password)
 * @return array|false Returns array with otp_code and otp_id on success, false on failure
 */
function createOTP($conn, $email, $phone = null, $user_id = null, $type = 'registration') {
    // Generate OTP code
    $otp_code = generateOTP();
    
    // Calculate expiry time
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
    
    // Invalidate any existing unused OTPs for this email
    $stmt = $conn->prepare("UPDATE otp_code SET is_used = 1 WHERE email = ? AND is_used = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();
    
    // Insert new OTP
    $stmt = $conn->prepare("INSERT INTO otp_code (user_id, email, phone, otp_code, otp_type, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $email, $phone, $otp_code, $type, $expires_at);
    
    if ($stmt->execute()) {
        $otp_id = $stmt->insert_id;
        $stmt->close();
        return [
            'otp_code' => $otp_code,
            'otp_id' => $otp_id,
            'expires_at' => $expires_at
        ];
    }
    
    $stmt->close();
    return false;
}

/**
 * Verify OTP code
 * 
 * @param mysqli $conn Database connection
 * @param string $email User email
 * @param string $otp_code OTP code to verify
 * @param string $type OTP type
 * @return array Returns array with success status and message
 */
function verifyOTP($conn, $email, $otp_code, $type = 'registration') {
    $stmt = $conn->prepare("
        SELECT otp_id, user_id, expires_at, is_used 
        FROM otp_code 
        WHERE email = ? AND otp_code = ? AND otp_type = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->bind_param("sss", $email, $otp_code, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return [
            'success' => false,
            'message' => 'Invalid OTP code.'
        ];
    }
    
    $otp = $result->fetch_assoc();
    $stmt->close();
    
    // Check if already used
    if ($otp['is_used']) {
        return [
            'success' => false,
            'message' => 'OTP code has already been used.'
        ];
    }
    
    // Check if expired
    if (strtotime($otp['expires_at']) < time()) {
        return [
            'success' => false,
            'message' => 'OTP code has expired. Please request a new one.'
        ];
    }
    
    // Mark OTP as used
    $update_stmt = $conn->prepare("UPDATE otp_code SET is_used = 1 WHERE otp_id = ?");
    $update_stmt->bind_param("i", $otp['otp_id']);
    $update_stmt->execute();
    $update_stmt->close();
    
    return [
        'success' => true,
        'message' => 'OTP verified successfully.',
        'user_id' => $otp['user_id'],
        'otp_id' => $otp['otp_id']
    ];
}

/**
 * Send OTP via Email using PHPMailer
 * ENABLED - Currently in use
 * 
 * @param string $email Recipient email
 * @param string $otp_code OTP code
 * @param string $name Recipient name
 * @return bool Returns true on success, false on failure
 */
function sendOTPEmail($email, $otp_code, $name = 'User') {
    // You'll need to install PHPMailer via Composer
    // composer require phpmailer/phpmailer
    
    require_once '../vendor/autoload.php'; // Adjust path as needed
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Change to your SMTP host
        $mail->SMTPAuth = true;
        $mail->Username = 'eventix.system@gmail.com'; // CHANGE THIS - Gmail address
        $mail->Password = 'gjzo qozj stqh iomm'; // CHANGE THIS - 16-character app password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('eventix.system@gmail.com', 'Eventix - Security');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Eventix Verification Code';
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; padding: 20px;'>
                <div style='max-width: 600px; margin: 0 auto; background: #f8f8f8; padding: 30px; border-radius: 10px;'>
                    <h2 style='color: #800020;'>Eventix - Email Verification</h2>
                    <p>Hello {$name},</p>
                    <p>Your verification code is:</p>
                    <div style='background: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;'>
                        <h1 style='color: #800020; font-size: 36px; letter-spacing: 8px; margin: 0;'>{$otp_code}</h1>
                    </div>
                    <p>This code will expire in " . OTP_EXPIRY_MINUTES . " minutes.</p>
                    <p style='color: #666; font-size: 14px;'>If you didn't request this code, please ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='color: #999; font-size: 12px;'>CCF B1G Ministry - Eventix System</p>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Your Eventix verification code is: {$otp_code}. This code expires in " . OTP_EXPIRY_MINUTES . " minutes.";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send OTP via SMS using Semaphore API
 * DISABLED - Commented out for future use
 * 
 * To enable SMS in the future:
 * 1. Get API key from semaphore.co
 * 2. Load credits (minimum ₱100)
 * 3. Uncomment the code below
 * 4. Replace 'YOUR_SEMAPHORE_API_KEY' with your actual API key
 * 5. Uncomment SMS code in sendOTPDual() function
 *
 * @param string $phone Phone number (format: +639XXXXXXXXX)
 * @param string $otp_code OTP code
 * @return bool Returns true on success, false on failure
 */
function sendOTPSMS($phone, $otp_code) {
    // SMS DISABLED - Return success without sending
    // This allows the system to work without SMS functionality
    return true;

    /* ============================================
       SMS CODE DISABLED - UNCOMMENT TO ENABLE
       ============================================

    $apiKey = 'YOUR_SEMAPHORE_API_KEY'; // Get from semaphore.co
    $senderName = 'Eventix'; // Your registered sender name
    
    // Format phone number (remove spaces, add +63 prefix if needed)
    $phone = preg_replace('/\s+/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '+63' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) !== '+63') {
        $phone = '+63' . $phone;
    }
    
    $message = "Your Eventix verification code is: {$otp_code}. Valid for " . OTP_EXPIRY_MINUTES . " minutes. Do not share this code.";
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'apikey' => $apiKey,
        'number' => $phone,
        'message' => $message,
        'sendername' => $senderName
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return true;
    } else {
        error_log("SMS sending failed: " . $response);
        return false;
    }

    ============================================ */
}

/**
 * Send OTP via both SMS and Email
 * ⚠️ SMS PART DISABLED - Only Email is sent
 * 
 * @param string $email User email
 * @param string $phone User phone
 * @param string $otp_code OTP code
 * @param string $name User name
 * @return array Returns status for both delivery methods
 */
function sendOTPDual($email, $phone, $otp_code, $name = 'User') {
    $result = [
        'email' => false,
        'sms' => false
    ];
    
    // ✅ Send via email (ENABLED)
    if (!empty($email)) {
        $result['email'] = sendOTPEmail($email, $otp_code, $name);
    }
    
    // ⚠️ SMS DISABLED - Uncomment to enable SMS in the future
    // if (!empty($phone)) {
    //     $result['sms'] = sendOTPSMS($phone, $otp_code);
    // }
    
    return $result;
}

/**
 * Clean up expired OTPs (should be run periodically via cron)
 * 
 * @param mysqli $conn Database connection
 * @return int Number of deleted records
 */
function cleanupExpiredOTPs($conn) {
    $stmt = $conn->prepare("DELETE FROM otp_code WHERE expires_at < NOW() OR (is_used = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY))");
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

/**
 * Check if user can request new OTP (rate limiting)
 * 
 * @param mysqli $conn Database connection
 * @param string $email User email
 * @return bool Returns true if user can request OTP
 */
function canRequestOTP($conn, $email) {
    // Check if user has requested OTP in the last minute
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM otp_code 
        WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['count'] < 3; // Max 3 requests per minute
}
?>