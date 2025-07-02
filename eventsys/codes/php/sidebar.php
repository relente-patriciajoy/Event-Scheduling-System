<!-- sidebar.php -->
<aside class="sidebar">
  <h2 class="logo">Eventix</h2>
  <nav>
    <a href="home.php" class="<?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">
      <i data-lucide="home" style="margin-right: 8px;"></i> Home
    </a>
    <a href="events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>">
      <i data-lucide="calendar" style="margin-right: 8px;"></i> Browse Events
    </a>
    <a href="my_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'my_events.php' ? 'active' : '' ?>">
      <i data-lucide="user-check" style="margin-right: 8px;"></i> My Events
    </a>
    <a href="attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active' : '' ?>">
      <i data-lucide="check-square" style="margin-right: 8px;"></i> Attendance
    </a>

    <?php if ($role === 'event_head'): ?>
      <a href="manage_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_events.php' ? 'active' : '' ?>">
        <i data-lucide="settings" style="margin-right: 8px;"></i> Manage Events
      </a>
      <a href="view_attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'view_attendance.php' ? 'active' : '' ?>">
        <i data-lucide="eye" style="margin-right: 8px;"></i> View Attendance
      </a>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
      <div class="dropdown-toggle" onclick="toggleDropdown(this)">
        <i data-lucide="database" style="margin-right: 8px;"></i>
        <span>Maintenance</span>
        <span style="margin-left:auto;">▾</span>
      </div>
      <div class="dropdown-links">
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_venues.php">Manage Venues</a>
        <a href="manage_categories.php">Manage Categories</a>
        <a href="manage_organizers.php">Manage Organizers</a>
      </div>
    <?php endif; ?>

    <a href="logout.php"><i data-lucide="log-out" style="margin-right: 8px;"></i> Logout</a>
  </nav>
</aside>

<script>
  function toggleDropdown(el) {
    el.classList.toggle('open');
    el.nextElementSibling.classList.toggle('open');
  }
</script>

<style>
  .dropdown-toggle {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 10px 12px;
    color: white;
    transition: background 0.2s;
  }

  .dropdown-toggle:hover {
    background-color: #2e3a59;
  }

  .dropdown-links {
    display: none;
    flex-direction: column;
    margin-left: 20px;
    padding-left: 8px;
  }

  .dropdown-links.open {
    display: flex;
  }

  .dropdown-links a {
    color: #fff;
    text-decoration: none;
    padding: 6px 0;
  }

  .dropdown-links a:hover {
    text-decoration: underline;
  }
</style>