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

    <?php if ($role === 'event_head'): ?>
      <a href="../event/manage_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_events.php' ? 'active' : '' ?>">
        <i data-lucide="settings"></i> Manage Events
      </a>
      <a href="../event/view_attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'view_attendance.php' ? 'active' : '' ?>">
        <i data-lucide="eye"></i> View Attendance
      </a>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
      <div class="dropdown-nav">
        <div class="dropdown-toggle" onclick="toggleDropdown(this)">
          <i data-lucide="database" style="margin-right: 8px;"></i>
          <span>Maintenance</span>
          <span style="margin-left:auto;">▾</span>
        </div>

        <div class="dropdown-menu">
          <a href="../admin/manage_user.php">Users</a>
          <a href="../admin/manage_venue.php">Venues</a>
          <a href="../admin/manage_organizer.php">Organizers</a>
          <a href="../admin/manage_categories.php">Categories</a>
          <a href="../admin/manage_payment.php">Payments</a>
        </div>
      </div>
    <?php endif; ?>

    <a href="../auth/logout.php"><i data-lucide="log-out"></i> Logout</a>
  </nav>
</aside>

<script>
  function toggleDropdown(toggle) {
    const container = toggle.closest(".dropdown-nav");
    container.classList.toggle("open");
  }
</script>