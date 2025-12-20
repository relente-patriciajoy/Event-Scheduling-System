<?php
include('../../includes/db.php');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=attendance_export.xls");

echo "Event Title\tParticipant Name\tCheck-In Time\tCheck-Out Time\tStatus\n";

$query = "
SELECT e.title AS event_title, 
       CONCAT(u.first_name, ' ', u.last_name) AS participant_name, 
       a.check_in_time, a.check_out_time, a.status
FROM attendance a
JOIN registration r ON a.registration_id = r.registration_id
JOIN user u ON r.user_id = u.user_id
JOIN event e ON r.event_id = e.event_id
";

$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    echo "{$row['event_title']}\t{$row['participant_name']}\t{$row['check_in_time']}\t{$row['check_out_time']}\t{$row['status']}\n";
}

exit();
?>