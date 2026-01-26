<?php
require_once('../../includes/session.php');
require_once('../../includes/role_protection.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}

include('../../includes/db.php');

$user_id = $_SESSION['user_id'];
$registration_id = $_GET['reg_id'] ?? null;
$message = "";

// Validate registration
if (!$registration_id) {
    die("Invalid registration.");
}

$stmt = $conn->prepare("SELECT r.registration_id, e.title, e.price
                        FROM registration r
                        JOIN event e ON r.event_id = e.event_id
                        WHERE r.registration_id = ? AND r.user_id = ?");
$stmt->bind_param("ii", $registration_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$registration = $result->fetch_assoc();

if (!$registration) {
    die("Unauthorized or invalid registration.");
}

// Handle payment submission (only if price > 0)
if ($_SERVER["REQUEST_METHOD"] === "POST" && $registration['price'] > 0) {
    $method = $_POST['payment_method'];
    $amount = $registration['price'];

    $stmt = $conn->prepare("INSERT INTO payment (registration_id, amount, payment_method) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $registration_id, $amount, $method);
    $stmt->execute();

    $message = "Payment successful!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pay for Event</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<div class="form-container">
    <h2>Pay for: <?= htmlspecialchars($registration['title']) ?></h2>

    <?php if ($registration['price'] == 0): ?>
        <p><strong>Amount:</strong> Free</p>
        <p class="message">This event is free. No payment is required.</p>
        <a href="../dashboard/my_events.php">Go to My Events</a>
    <?php elseif ($message): ?>
        <p><strong>Amount:</strong> $<?= number_format($registration['price'], 2) ?></p>
        <p class="message"><?= $message ?></p>
        <a href="../dashboard/my_events.php">Go to My Events</a>
    <?php else: ?>
        <p><strong>Amount:</strong> $<?= number_format($registration['price'], 2) ?></p>
        <form method="post">
            <label>Payment Method</label>
            <select name="payment_method" required>
                <option value="">Select</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Gcash">Gcash</option>
                <option value="PayPal">PayPal</option>
            </select>
            <button type="submit">Pay Now</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>