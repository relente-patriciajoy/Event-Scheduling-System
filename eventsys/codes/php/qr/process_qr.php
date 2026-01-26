<?php
require_once('../../includes/session.php');
require_once('../../includes/role_protection.php');
/**
 * QR Code Processing Backend
 * Handles check-in/check-out requests from scanner
 */
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit();
}

require_once('../../includes/db.php');
require_once('../../includes/qr_function.php');

// Check role
$user_id = $_SESSION['user_id'];
$role_stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if ($role !== 'event_head' && $role !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only event heads can process QR codes.'
    ]);
    exit();
}

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['qr_data']) || !isset($data['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data.'
    ]);
    exit();
}

$qr_data = $data['qr_data'];
$action = $data['action'];

// Process based on action
if ($action === 'checkin') {
    $result = processCheckIn($qr_data, $conn);
} elseif ($action === 'checkout') {
    $result = processCheckOut($qr_data, $conn);
} else {
    $result = [
        'success' => false,
        'message' => 'Invalid action specified.'
    ];
}

// Return result as JSON
echo json_encode($result);
$conn->close();
?>