<?php
/**
 * OTP Functions for Eventix System
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
 */
function createOTP($conn, $email, $phone = null, $user_id = null, $type = 'registration') {
    // Generate OTP code
    $otp_code = generateOTP();
    
    // Calculate expiry time
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
    
    // Invalidate any existing unused OTPs for this email AND type
    $stmt = $conn->prepare("UPDATE otp_code SET is_used = 1 WHERE email = ? AND otp_type = ? AND is_used = 0");
    $stmt->bind_param("ss", $email, $type);
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
 * Verify OTP code - FIXED VERSION
 */
function verifyOTP($conn, $email, $otp_code, $type = 'registration') {
    $stmt = $conn->prepare("
        SELECT otp_id, user_id, expires_at, is_used 
        FROM otp_code 
        WHERE email = ? AND otp_code = ? AND otp_type = ? AND is_used = 0
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->bind_param("sss", $email, $otp_code, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();

        // Check if OTP exists but is already used
        $check_stmt = $conn->prepare("
            SELECT is_used, expires_at
            FROM otp_code
            WHERE email = ? AND otp_code = ? AND otp_type = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $check_stmt->bind_param("sss", $email, $otp_code, $type);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $check_row = $check_result->fetch_assoc();
            $check_stmt->close();

            if ($check_row['is_used']) {
                return [
                    'success' => false,
                    'message' => 'This OTP code has already been used. Please request a new one.'
                ];
            } elseif (strtotime($check_row['expires_at']) < time()) {
                return [
                    'success' => false,
                    'message' => 'OTP code has expired. Please request a new one.'
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Invalid OTP code. Please check and try again.'
        ];
    }
    
    $otp = $result->fetch_assoc();
    $stmt->close();
    
    // Check if expired
    if (strtotime($otp['expires_at']) < time()) {
        return [
            'success' => false,
            'message' => 'OTP code has expired. Please request a new one.'
        ];
    }
    
    // Mark OTP as used ONLY after successful verification
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
 */
function sendOTPEmail($email, $otp_code, $name = 'User') {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'eventix.system@gmail.com';
        $mail->Password = 'gjzo qozj stqh iomm';
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
 * Send OTP via SMS - DISABLED
 */
function sendOTPSMS($phone, $otp_code) {
    return true; // SMS disabled
}

/**
 * Send OTP via both SMS and Email
 */
function sendOTPDual($email, $phone, $otp_code, $name = 'User') {
    $result = [
        'email' => false,
        'sms' => false
    ];
    
    if (!empty($email)) {
        $result['email'] = sendOTPEmail($email, $otp_code, $name);
    }
    
    return $result;
}

/**
 * Clean up expired OTPs
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
 */
function canRequestOTP($conn, $email) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM otp_code 
        WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['count'] < 3;
}
?>