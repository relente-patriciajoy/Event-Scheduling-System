<?php
/**
 * Device Recognition Functions - Complete Fixed Version
 * Handles "Remember Me" functionality with trusted devices
 * 
 * Token Duration: 30 days (2,592,000 seconds = 30 * 24 * 60 * 60)
 * Cookie Expiration: 30 days from creation
 * Database Expiration: 30 days from last trust action
 */

/**
 * Generate device fingerprint based on user agent and IP
 */
function generateDeviceFingerprint() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Create fingerprint hash
    return hash('sha256', $user_agent . $ip_address);
}

/**
 * Get or create device token (stored in cookie)
 * Cookie expires in 30 days (same as device trust duration)
 */
function getDeviceToken() {
    $cookie_name = 'device_token';
    
    if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name])) {
        return $_COOKIE[$cookie_name];
    }
    
    // Generate new device token (64 character hex string)
    $token = bin2hex(random_bytes(32));
    
    // Set cookie for 30 days (2,592,000 seconds)
    $expiry = time() + (30 * 24 * 60 * 60); // 30 days in seconds
    
    // Use secure settings based on HTTPS availability
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    setcookie($cookie_name, $token, [
        'expires' => $expiry,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    return $token;
}

/**
 * Check if current device is trusted
 * Returns true if device is trusted and not expired
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to check
 * @return bool True if device is trusted, false otherwise
 */
function isTrustedDevice($conn, $user_id) {
    $device_token = getDeviceToken();
    $device_fingerprint = generateDeviceFingerprint();
    
    // Check both trusted_until and expires_at for compatibility
    $stmt = $conn->prepare("
        SELECT device_id, trusted_until, expires_at 
        FROM trusted_device 
        WHERE user_id = ? 
        AND device_token = ? 
        AND device_fingerprint = ? 
        AND (trusted_until > NOW() OR expires_at > NOW())
    ");
    
    if (!$stmt) {
        error_log("isTrustedDevice SQL Error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("iss", $user_id, $device_token, $device_fingerprint);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $is_trusted = $result->num_rows > 0;
    
    if ($is_trusted) {
        // Update last_used timestamp
        $row = $result->fetch_assoc();
        $update_stmt = $conn->prepare("UPDATE trusted_device SET last_used = NOW() WHERE device_id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("i", $row['device_id']);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }
    
    $stmt->close();
    return $is_trusted;
}

/**
 * Trust current device for specified number of days
 * Default: 30 days (2,592,000 seconds)
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $days Number of days to trust device (default: 30)
 * @return bool True on success, false on failure
 */
function trustDevice($conn, $user_id, $days = 30) {
    $device_token = getDeviceToken();
    $device_fingerprint = generateDeviceFingerprint();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Calculate expiration date (both columns for compatibility)
    $expiration = date('Y-m-d H:i:s', strtotime("+$days days"));
    
    // Truncate user agent if too long (for device_name column)
    $device_name = substr($user_agent, 0, 100);
    
    // Check if device token already exists (for ANY user or this user)
    $check_stmt = $conn->prepare("
        SELECT device_id, user_id
        FROM trusted_device 
        WHERE device_token = ?
    ");
    
    if (!$check_stmt) {
        error_log("trustDevice Check SQL Error: " . $conn->error);
        return false;
    }
    
    $check_stmt->bind_param("s", $device_token);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Device token exists - update it
        $row = $result->fetch_assoc();
        $check_stmt->close();
        
        // Update existing device with new user_id and extended trust period
        $update_stmt = $conn->prepare("
            UPDATE trusted_device 
            SET user_id = ?,
                trusted_until = ?,
                expires_at = ?,
                device_fingerprint = ?,
                device_name = ?,
                user_agent = ?,
                ip_address = ?,
                last_used = NOW()
            WHERE device_token = ?
        ");
        
        if (!$update_stmt) {
            error_log("trustDevice Update SQL Error: " . $conn->error);
            return false;
        }
        
        $update_stmt->bind_param("isssssss",
            $user_id,
            $expiration, 
            $expiration, 
            $device_fingerprint, 
            $device_name,
            $user_agent,
            $ip_address,
            $device_token
        );
        
        $success = $update_stmt->execute();

        if (!$success) {
            error_log("trustDevice Update Error: " . $update_stmt->error);
        }

        $update_stmt->close();
        return $success;
        
    } else {
        // Insert new trusted device
        $check_stmt->close();
        
        $insert_stmt = $conn->prepare("
            INSERT INTO trusted_device 
            (user_id, device_token, device_fingerprint, device_name, user_agent, ip_address, trusted_until, expires_at, created_at, last_used) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        if (!$insert_stmt) {
            error_log("trustDevice Insert SQL Error: " . $conn->error);
            return false;
        }
        
        $insert_stmt->bind_param("isssssss", 
            $user_id, 
            $device_token, 
            $device_fingerprint, 
            $device_name,
            $user_agent,
            $ip_address,
            $expiration,
            $expiration
        );
        
        $success = $insert_stmt->execute();

        if (!$success) {
            error_log("trustDevice Insert Error: " . $insert_stmt->error);
        }

        $insert_stmt->close();
        return $success;
    }
}

/**
 * Check if login is suspicious based on login patterns
 * Currently checks for multiple failed attempts
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return bool True if suspicious, false otherwise
 */
function isSuspiciousLogin($conn, $user_id) {
    // Check for failed login attempts in last 30 minutes
    $stmt = $conn->prepare("
        SELECT COUNT(*) as failed_count 
        FROM login_attempt 
        WHERE user_id = ? 
        AND success = 0 
        AND attempted_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    
    if (!$stmt) {
        return false; // Fail open if query fails
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    // Consider suspicious if 3+ failed attempts in last 30 minutes
    return ($row['failed_count'] >= 3);
}

/**
 * Remove device trust (e.g., when user clicks "Forget this device")
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param string $device_token Optional specific device token (default: current device)
 * @return bool True on success
 */
function untrustDevice($conn, $user_id, $device_token = null) {
    if ($device_token === null) {
        $device_token = getDeviceToken();
    }
    
    $stmt = $conn->prepare("
        DELETE FROM trusted_device 
        WHERE user_id = ? 
        AND device_token = ?
    ");
    
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("is", $user_id, $device_token);
    $success = $stmt->execute();
    $stmt->close();
    
    // Also clear the cookie
    setcookie('device_token', '', time() - 3600, '/', '', true, true);
    
    return $success;
}

/**
 * Clean up expired trusted devices
 * Should be called periodically (e.g., via cron job)
 * 
 * @param mysqli $conn Database connection
 * @return int Number of devices removed
 */
function cleanupExpiredDevices($conn) {
    $stmt = $conn->prepare("
        DELETE FROM trusted_device 
        WHERE trusted_until < NOW() 
        OR expires_at < NOW()
    ");
    
    if (!$stmt) {
        return 0;
    }
    
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected;
}

/**
 * Get all trusted devices for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Array of trusted devices
 */
function getUserTrustedDevices($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT 
            device_id,
            device_name,
            ip_address,
            created_at,
            last_used,
            COALESCE(trusted_until, expires_at) as expires_at
        FROM trusted_device 
        WHERE user_id = ? 
        AND (trusted_until > NOW() OR expires_at > NOW())
        ORDER BY last_used DESC
    ");
    
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $devices = [];
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
    
    $stmt->close();
    return $devices;
}
?>