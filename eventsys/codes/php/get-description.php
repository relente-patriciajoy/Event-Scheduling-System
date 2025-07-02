<?php
include('../includes/db.php');
$event_id = $_GET['event_id'];
$stmt = $conn->prepare("SELECT title, description, start_time, end_time FROM event WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

// Format the datetime fields
$data['start_time'] = date("M d, Y h:i A", strtotime($data['start_time']));
$data['end_time'] = date("M d, Y h:i A", strtotime($data['end_time']));

echo json_encode($data);
?>