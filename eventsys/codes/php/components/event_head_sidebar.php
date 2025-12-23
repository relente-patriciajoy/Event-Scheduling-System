<aside class="sidebar">
  <h2 class="logo">Eventix</h2>
  <nav>
    <a href="../dashboard/home.php" class="<?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">
      <i data-lucide="home"></i> Home
    </a>
    
    <a href="../dashboard/events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>">
      <i data-lucide="calendar"></i> Browse Events
    </a>
    
    <a href="../dashboard/my_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'my_events.php' ? 'active' : '' ?>">
      <i data-lucide="user-check"></i> My Events
    </a>
    
    <a href="../dashboard/attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active' : '' ?>">
      <i data-lucide="check-square"></i> Attendance
    </a>
    
    <a href="../calendar/calendar.php" class="<?= basename($_SERVER['PHP_SELF']) === 'calendar.php' ? 'active' : '' ?>">
      <i data-lucide="calendar-days"></i> Event Calendar
    </a>

    <!-- Event Head Exclusive Section -->
    <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); margin: 10px 0; padding-top: 10px;"></div>
    
    <a href="../event/manage_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_events.php' ? 'active' : '' ?>">
      <i data-lucide="settings"></i> Manage Events
    </a>
    
    <a href="../qr/scan_qr.php" class="<?= basename($_SERVER['PHP_SELF']) === 'scan_qr.php' ? 'active' : '' ?>">
      <i data-lucide="scan"></i> QR Scanner
    </a>
    
    <a href="../event/view_attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'view_attendance.php' ? 'active' : '' ?>">
      <i data-lucide="eye"></i> View Attendance
    </a>

    <a href="../auth/logout.php">
      <i data-lucide="log-out"></i> Logout
    </a>
  </nav>
</aside>

<script>
  // Initialize Lucide icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
</script>