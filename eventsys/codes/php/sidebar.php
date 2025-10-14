<aside class="sidebar">
  <h2 class="logo">Eventix</h2>
  <nav>
    <a href="home.php" class="<?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">
      <i data-lucide="home"></i> Home
    </a>
    <a href="events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>">
      <i data-lucide="calendar"></i> Browse Events
    </a>
    <a href="my_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'my_events.php' ? 'active' : '' ?>">
      <i data-lucide="user-check"></i> My Events
    </a>
    <a href="attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active' : '' ?>">
      <i data-lucide="check-square"></i> Attendance
    </a>

    <?php if ($role === 'event_head'): ?>
      <a href="manage_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_events.php' ? 'active' : '' ?>">
        <i data-lucide="settings"></i> Manage Events
      </a>
      <a href="view_attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'view_attendance.php' ? 'active' : '' ?>">
        <i data-lucide="eye"></i> View Attendance
      </a>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
      <div class="dropdown-toggle" onclick="toggleDropdown(this)">
        <i data-lucide="database"></i>
        <span>Maintenance</span>
        <span>▾</span>
      </div>
      <div class="dropdown-links">
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_venues.php">Manage Venues</a>
        <a href="manage_categories.php">Manage Categories</a>
        <a href="manage_organizers.php">Manage Organizers</a>
      </div>
    <?php endif; ?>

    <a href="logout.php"><i data-lucide="log-out"></i> Logout</a>
  </nav>
</aside>

<script>
  function toggleDropdown(el) {
    el.classList.toggle('open');
    el.nextElementSibling.classList.toggle('open');
  }
</script>