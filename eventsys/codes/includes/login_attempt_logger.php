<?php
/**
 * Login Attempt Logger Functions
 * Tracks successful and failed login attempts for security monitoring
 */

/**
 * Log a login attempt (success or failure)
 * 
 * @param mysqli $conn Database connection
 * @param int|null $user_id User ID (null if user not found)
 * @param string $email Email address used for login
 * @param int $success 1 for success, 0 for failure
 * @return bool True on successful log, false on failure
 */
function logLoginAttempt($conn, $user_id, $email, $success) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $conn->prepare("
        INSERT INTO login_attempt 
        (user_id, email, success, ip_address, user_agent, attempted_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        error_log("logLoginAttempt SQL Error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isiss", $user_id, $email, $success, $ip_address, $user_agent);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get recent failed login attempts for a user
 * 
 * @param mysqli $conn Database connection
 * @param string $email Email address
 * @param int $minutes Time window in minutes (default: 30)
 * @return int Number of failed attempts
 */
function getFailedLoginAttempts($conn, $email, $minutes = 30) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as failed_count 
        FROM login_attempt 
        WHERE email = ? 
        AND success = 0 
        AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    
    if (!$stmt) {
        return 0;
    }
    
    $stmt->bind_param("si", $email, $minutes);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return (int)$row['failed_count'];
}

/**
 * Check if account should be temporarily locked due to too many failed attempts
 * 
 * @param mysqli $conn Database connection
 * @param string $email Email address
 * @param int $max_attempts Maximum allowed failed attempts (default: 5)
 * @param int $lockout_minutes Lockout duration in minutes (default: 15)
 * @return array ['locked' => bool, 'attempts' => int, 'time_remaining' => int]
 */
function checkAccountLockout($conn, $email, $max_attempts = 5, $lockout_minutes = 15) {
    $failed_attempts = getFailedLoginAttempts($conn, $email, $lockout_minutes);
    
    $is_locked = $failed_attempts >= $max_attempts;
    
    $time_remaining = 0;
    if ($is_locked) {
        // Get time of oldest failed attempt in window
        $stmt = $conn->prepare("
            SELECT attempted_at 
            FROM login_attempt 
            WHERE email = ? 
            AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ORDER BY attempted_at ASC 
            LIMIT 1
        ");
        
        if ($stmt) {
            $stmt->bind_param("si", $email, $lockout_minutes);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $lockout_end = strtotime($row['attempted_at']) + ($lockout_minutes * 60);
                $time_remaining = max(0, $lockout_end - time());
            }
            
            $stmt->close();
        }
    }
    
    return [
        'locked' => $is_locked,
        'attempts' => $failed_attempts,
        'time_remaining' => $time_remaining
    ];
}

/**
 * Clear failed login attempts for a user (after successful login)
 * 
 * @param mysqli $conn Database connection
 * @param string $email Email address
 * @return bool True on success
 */
function clearFailedAttempts($conn, $email) {
    $stmt = $conn->prepare("
        DELETE FROM login_attempt 
        WHERE email = ? 
        AND success = 0 
        AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("s", $email);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get login history for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $limit Number of records to return (default: 10)
 * @return array Array of login attempts
 */
function getLoginHistory($conn, $user_id, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT 
            attempt_id,
            email,
            success,
            ip_address,
            user_agent,
            attempted_at
        FROM login_attempt 
        WHERE user_id = ? 
        ORDER BY attempted_at DESC 
        LIMIT ?
    ");
    
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    $stmt->close();
    return $history;
}

/**
 * Clean up old login attempts (for maintenance)
 * Removes attempts older than specified days
 * 
 * @param mysqli $conn Database connection
 * @param int $days Number of days to keep (default: 90)
 * @return int Number of records deleted
 */
function cleanupOldLoginAttempts($conn, $days = 90) {
    $stmt = $conn->prepare("
        DELETE FROM login_attempt 
        WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    
    if (!$stmt) {
        return 0;
    }
    
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected;
}
?>