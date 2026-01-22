<?php
/**
 * ADMIN - View All Attendance
 * Admins can view attendance for ALL events
 */
session_start();

// Admin access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin/admin-login.php");
    exit();
}

include('../../includes/db.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Fetch ALL events
$events_query = $conn->query("SELECT event_id, title FROM event ORDER BY start_time DESC");

// Handle selected event
$selected_event = $_GET['event_id'] ?? null;
$attendances = [];

if ($selected_event) {
    $query = "
        SELECT u.first_name, u.middle_name, u.last_name, u.email, 
               a.check_in_time, a.check_out_time, a.status
        FROM registration r
        JOIN user u ON r.user_id = u.user_id
        LEFT JOIN attendance a ON r.registration_id = a.registration_id
        WHERE r.event_id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selected_event);
    $stmt->execute();
    $attendances = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - Admin Panel</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/management.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body class="dashboard-layout">
    <?php include('admin_sidebar.php'); ?>

    <main class="management-content">
        <!-- Page Header -->
        <div class="admin-header">
          <div class="admin-badge">
              <i data-lucide="shield" style="width: 14px; height: 14px;"></i>
              Administrator
          </div>
          <h1>View Attendance</h1>
          <p>View attendance records for all events</p>
        </div>

        <!-- Event Selection -->
        <div class="management-card">
          <h2>Select Event</h2>
          <form method="GET" style="display: flex; flex-direction: column; gap: 12px;">
            <div class="form-group" style="margin: 0;">
              <label for="event_id"><strong>Choose Event:</strong></label>
              <select name="event_id" id="event_id" required style="padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0; width: 100%;">
                <option value="">-- Select an Event --</option>
                <?php while ($event = $events_query->fetch_assoc()): ?>
                  <option value="<?= $event['event_id'] ?>" <?= $selected_event == $event['event_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($event['title']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">
              <i data-lucide="eye"></i>
              View Attendance
            </button>
          </form>
        </div>

        <?php if ($selected_event && $attendances): ?>
            <!-- Attendance Table -->
            <div class="management-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Attendance List</h2>
                    <button onclick="exportToExcel()" class="btn btn-primary btn-sm">
                        <i data-lucide="download"></i>
                        Export to Excel
                    </button>
                </div>

                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Search by name or email..." 
                    style="width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 8px; border: 2px solid #e0e0e0;"
                    onkeyup="filterTable()"
                >

                <table class="management-table" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $attendances->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= $row['check_in_time'] ?? '—' ?></td>
                                <td><?= $row['check_out_time'] ?? '—' ?></td>
                                <td>
                                    <span class="badge badge-<?= ($row['status'] ?? 'absent') === 'present' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($row['status'] ?? 'absent') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($selected_event): ?>
            <div class="management-card">
                <div class="empty-state">
                    <i data-lucide="users"></i>
                    <h3>No Participants Yet</h3>
                    <p>No one has registered for this event yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        lucide.createIcons();

        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('attendanceTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[0];
                const emailCell = rows[i].getElementsByTagName('td')[1];
                
                if (nameCell && emailCell) {
                    const nameText = nameCell.textContent || nameCell.innerText;
                    const emailText = emailCell.textContent || emailCell.innerText;
                    
                    if (nameText.toLowerCase().indexOf(filter) > -1 || emailText.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }

        function exportToExcel() {
            const table = document.getElementById('attendanceTable');
            let csv = 'Name,Email,Check-In,Check-Out,Status\n';
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cols = row.querySelectorAll('td');
                    const rowData = Array.from(cols).map(td => {
                        return '"' + td.innerText.trim().replace(/"/g, '""') + '"';
                    }).join(',');
                    csv += rowData + '\n';
                }
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'attendance_export.csv';
            link.click();
        }
    </script>
</body>
</html>