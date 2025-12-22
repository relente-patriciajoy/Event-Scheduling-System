<!-- Admin Sidebar - Consistent Navigation -->
<aside class="sidebar">
  <h2 class="logo">Eventix Admin</h2>
  <nav>
    <a href="../admin/admin_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>">
      <i data-lucide="layout-dashboard"></i> Dashboard
    </a>
    
    <!-- Maintenance Dropdown -->
    <div class="dropdown-nav <?= in_array(basename($_SERVER['PHP_SELF']), ['manage_user.php', 'manage_venue.php', 'manage_organizer.php', 'manage_categories.php']) ? 'open' : '' ?>">
      <div class="dropdown-toggle" onclick="toggleDropdown(this)">
        <i data-lucide="database"></i>
        <span>Maintenance</span>
        <span style="margin-left:auto;">▾</span>
      </div>
      <div class="dropdown-menu">
        <a href="../admin/manage_user.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_user.php' ? 'active' : '' ?>">Users</a>
        <a href="../admin/manage_venue.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_venue.php' ? 'active' : '' ?>">Venues</a>
        <a href="../admin/manage_organizer.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_organizer.php' ? 'active' : '' ?>">Organizers</a>
        <a href="../admin/manage_categories.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_categories.php' ? 'active' : '' ?>">Categories</a>
      </div>
    </div>
    
    <a href="../admin/admin_all_events.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_all_events.php' ? 'active' : '' ?>">
      <i data-lucide="calendar"></i> All Events
    </a>

    <a href="../qr/scan_qr.php" class="<?= basename($_SERVER['PHP_SELF']) === 'scan_qr.php' ? 'active' : '' ?>">
      <i data-lucide="scan"></i> QR Scanner
    </a>
    
    <a href="../admin/admin_view_attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_view_attendance.php' ? 'active' : '' ?>">
      <i data-lucide="users"></i> Attendance
    </a>
    
    <a href="../admin/user_promotions.php" class="<?= basename($_SERVER['PHP_SELF']) === 'user_promotions.php' ? 'active' : '' ?>">
      <i data-lucide="user-plus"></i> Promote Users
    </a>
    
    <a href="../auth/logout.php">
      <i data-lucide="log-out"></i> Logout
    </a>
  </nav>
</aside>

<script>
  function toggleDropdown(toggle) {
    const container = toggle.closest(".dropdown-nav");
    container.classList.toggle("open");
  }
  
  // Initialize Lucide icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
</script>

<style>
  /* Ensure dropdown styles are loaded */
  .dropdown-nav {
    display: flex;
    flex-direction: column;
  }

  .dropdown-toggle {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 14px 16px;
    color: #e0e0e0;
    border-radius: 8px;
    transition: all 0.3s ease;
    gap: 12px;
    font-weight: 500;
    margin-bottom: 2px;
    border-left: 3px solid transparent;
    user-select: none;
  }

  .dropdown-toggle:hover {
    background-color: rgba(230, 57, 70, 0.15);
    color: #ffffff;
    border-left: 3px solid #e63946;
    padding-left: 20px;
    transform: translateX(2px);
  }

  .dropdown-toggle.open {
    background-color: rgba(230, 57, 70, 0.1);
    color: #ffffff;
  }

  .dropdown-toggle span:last-child {
    transition: transform 0.3s ease;
  }

  .dropdown-nav.open .dropdown-toggle span:last-child {
    transform: rotate(180deg);
  }

  .dropdown-menu {
    display: none;
    flex-direction: column;
    margin-left: 30px;
    padding-left: 20px;
    margin-top: 4px;
    margin-bottom: 8px;
    border-left: 2px solid rgba(230, 57, 70, 0.3);
    gap: 2px;
  }

  .dropdown-nav.open .dropdown-menu {
    display: flex;
  }

  .dropdown-menu a {
    color: #b0b0b0;
    text-decoration: none;
    padding: 10px 12px;
    font-size: 0.95rem;
    border-radius: 6px;
    transition: all 0.3s ease;
    position: relative;
    font-weight: 400;
  }

  .dropdown-menu a::before {
    content: '•';
    position: absolute;
    left: 0;
    color: #e63946;
    opacity: 0;
    transition: opacity 0.3s ease;
  }

  .dropdown-menu a:hover {
    background-color: rgba(230, 57, 70, 0.1);
    color: #ffffff;
    padding-left: 20px;
    transform: translateX(2px);
  }

  .dropdown-menu a:hover::before {
    opacity: 1;
  }

  .dropdown-menu a.active {
    background-color: rgba(230, 57, 70, 0.15);
    color: #ffffff;
    font-weight: 600;
  }
</style>