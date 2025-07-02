<?php 
  session_start();
  $user_id = $_SESSION['user_id'];
  include('../includes/db.php');
  
  $delete_id = $_GET['delete'];
  $stmt = $conn->prepare("DELETE FROM event 
            WHERE event_id = ? 
            AND organizer_id IN 
            (SELECT organizer_id 
            FROM organizer 
            WHERE contact_email = 
              (SELECT email 
              FROM user 
              WHERE user_id = ?))");
  $stmt->bind_param("ii", $delete_id, $user_id);
  $stmt->execute();
  $stmt->close();

  echo "Event Deleted.";
?>