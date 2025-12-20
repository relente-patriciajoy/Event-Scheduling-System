<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}

include('../../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];
    $maxCapacity = $_POST['capacity'] ?? 0;

    function assignTableNumber($conn, $maxCapacity) {
        $maxAttempts = $maxCapacity;
        $attempts = 0;
        $count = 0;

        do {
            $randomTable = rand(1, $maxCapacity);
            $stmt = $conn->prepare("SELECT COUNT(*) FROM registration WHERE table_number = ?");
            $stmt->bind_param("i", $randomTable);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            $attempts++;
        } while($count > 0 && $attempts < $maxAttempts);

        if($count == 0) {
            return $randomTable;
        } else {
            return null; // No available table found
        }
    }

    $assignedTable = assignTableNumber($conn, $maxCapacity);

    if($assignedTable === null) {
        echo "<script>alert('No available tables for this event. Please try again later.'); window.location.href='events.php';</script>";
        exit();
    }

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
       $stmt = $conn->prepare("INSERT INTO registration (user_id, event_id, table_number) VALUES (?, ?, ?)");
       $stmt->bind_param("iii", $user_id, $event_id, $assignedTable);

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