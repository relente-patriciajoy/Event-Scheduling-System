<?php
/**
 * Device Recognition System
 * Handles "Remember Me" functionality and suspicious login detection
 */

/**
 * Generate device fingerprint based on browser/device info
 */
function generateDeviceFingerprint() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    
    $fingerprint_data = $user_agent . '|' . $accept_language . '|' . $accept_encoding;
    return hash('sha256', $fingerprint_data);
}

/**
 * Generate secure device token
 */
function generateDeviceToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Check if device is trusted
 */
function isTrustedDevice($conn, $user_id) {
    $device_token = $_COOKIE['device_token'] ?? null;
    $device_fingerprint = generateDeviceFingerprint();
    
    if (!$device_token) {
        return false;
    }
    
    $stmt = $conn->prepare("
        SELECT device_id, expires_at 
        FROM trusted_device 
        WHERE user_id = ? 
        AND device_token = ? 
        AND device_fingerprint = ?
        AND expires_at > NOW()
    ");
    
    $stmt->bind_param("iss", $user_id, $device_token, $device_fingerprint);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update last used time
        $device = $result->fetch_assoc();
        $update_stmt = $conn->prepare("UPDATE trusted_device SET last_used = NOW() WHERE device_id = ?");
        $update_stmt->bind_param("i", $device['device_id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        $stmt->close();
        return true;
    }
    
    $stmt->close();
    return false;
}

/**
 * Add device to trusted list
 */
function trustDevice($conn, $user_id, $remember_days = 30) {
    $device_token = generateDeviceToken();
    $device_fingerprint = generateDeviceFingerprint();
    $device_name = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$remember_days} days"));
    
    $stmt = $conn->prepare("
        INSERT INTO trusted_device (user_id, device_token, device_fingerprint, device_name, expires_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("issss", $user_id, $device_token, $device_fingerprint, $device_name, $expires_at);
    
    if ($stmt->execute()) {
        // Set secure cookie
        setcookie(
            'device_token',
            $device_token,
            [
                'expires' => strtotime("+{$remember_days} days"),
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']), // HTTPS only if available
                'httponly' => true, // Prevent JavaScript access
                'samesite' => 'Strict'
            ]
        );
        $stmt->close();
        return true;
    }
    
    $stmt->close();
    return false;
}

/**
 * Check for suspicious login activity
 */
function isSuspiciousLogin($conn, $user_id) {
    // Check for recent failed login attempts
    $stmt = $conn->prepare("
        SELECT COUNT(*) as failed_attempts
        FROM login_attempt
        WHERE user_id = ?
        AND success = 0
        AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // If 3+ failed attempts in last hour, require OTP
    return $result['failed_attempts'] >= 3;
}

/**
 * Log login attempt
 */
function logLoginAttempt($conn, $user_id, $email, $success) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $conn->prepare("
        INSERT INTO login_attempt (user_id, email, success, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("isiss", $user_id, $email, $success, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

/**
 * Clean up expired trusted devices (run as cron job)
 */
function cleanupExpiredDevices($conn) {
    $stmt = $conn->prepare("DELETE FROM trusted_device WHERE expires_at < NOW()");
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}
?>