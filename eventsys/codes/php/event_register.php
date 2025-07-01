<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];

    // Check if already registered
    $check = $conn->prepare("SELECT * FROM registration WHERE user_id = ? AND event_id = ?");
    $check->bind_param("ii", $user_id, $event_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['register_status'] = "You have already registered for this event.";
        header("Location: events.php");
        exit();
    } else {
       $stmt = $conn->prepare("INSERT INTO registration (user_id, event_id) VALUES (?, ?)");
       $stmt->bind_param("ii", $user_id, $event_id);

       if ($stmt->execute()) {
            $registration_id = $stmt->insert_id;
            header("Location: pay.php?reg_id=" . $registration_id);
            exit();
        } else {
            echo "<script>alert('Registration failed. Please try again.'); window.location.href='events.php';</script>";
        }
    }
} else {
    header("Location: events.php");
}
?>