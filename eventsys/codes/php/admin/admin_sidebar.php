<!--
  HAMBURGER MENU SYSTEM FOR ADMIN PANEL
  Add this code to your admin_sidebar.php file
-->

<!-- Mobile Header with Hamburger (Add BEFORE the sidebar) -->
<div class="mobile-header">
    <button class="hamburger-menu" id="hamburgerBtn" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <h1 class="mobile-logo">Eventix Admin</h1>
</div>

<!-- Overlay for mobile menu -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Your existing sidebar with added class -->
<aside class="sidebar" id="adminSidebar">
  <h2 class="logo">Eventix Admin</h2>

  <button class="sidebar-close" id="closeSidebarBtn" aria-label="Close menu">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
  </button>

  <nav>
    <a href="../admin/admin_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>">
      <i data-lucide="layout-dashboard"></i> Dashboard
    </a>

    <!-- Maintenance Dropdown -->
    <div class="dropdown-nav <?= in_array(basename($_SERVER['PHP_SELF']), ['manage_user.php', 'manage_venue.php', 'manage_organizer.php', 'manage_categories.php']) ? 'open' : '' ?>">
      <div class="dropdown-toggle" onclick="toggleDropdown(this)">
        <i data-lucide="database"></i>
        <span>Maintenance</span>
        <span>▾</span>
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

    <a href="../admin/admin_view_attendance.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_view_attendance.php' ? 'active' : '' ?>">
      <i data-lucide="users"></i> Attendance
    </a>

    <a href="../admin/user_promotions.php" class="<?= basename($_SERVER['PHP_SELF']) === 'user_promotions.php' ? 'active' : '' ?>">
      <i data-lucide="user-plus"></i> Promote Users
    </a>

    <a href="../admin/backup_restore.php">
      <i data-lucide="database"></i> Backup & Restore
    </a>

    <a href="../auth/logout.php?return=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
      <i data-lucide="log-out"></i> Logout
    </a>
  </nav>
</aside>

<script>
// Hamburger Menu Functionality
(function() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const closeSidebarBtn = document.getElementById('closeSidebarBtn');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const body = document.body;

    // Open sidebar
    function openSidebar() {
        sidebar.classList.add('mobile-open');
        overlay.classList.add('active');
        body.style.overflow = 'hidden'; // Prevent background scrolling
        hamburgerBtn.classList.add('active');
    }

    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
        body.style.overflow = '';
        hamburgerBtn.classList.remove('active');
    }

    // Toggle sidebar
    function toggleSidebar() {
        if (sidebar.classList.contains('mobile-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    // Event listeners
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', toggleSidebar);
    }

    if (closeSidebarBtn) {
        closeSidebarBtn.addEventListener('click', closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar when clicking any navigation link (mobile only)
    const navLinks = sidebar.querySelectorAll('nav a:not(.dropdown-toggle)');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });

    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
            closeSidebar();
        }
    });

    // Close sidebar when window is resized to desktop
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        }, 250);
    });
})();

// Dropdown toggle function
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
/* ============================================
   MOBILE HEADER WITH HAMBURGER
   ============================================ */

.mobile-header {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    z-index: 1001;
    align-items: center;
    padding: 0 16px;
    gap: 16px;
}

.mobile-logo {
    color: white;
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    flex: 1;
}

/* Hamburger Button */
.hamburger-menu {
    width: 44px;
    height: 44px;
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 6px;
    padding: 8px;
    position: relative;
    z-index: 1002;
    -webkit-tap-highlight-color: transparent;
}

.hamburger-menu span {
    width: 26px;
    height: 3px;
    background: white;
    border-radius: 2px;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    display: block;
}

/* Hamburger Animation */
.hamburger-menu.active span:nth-child(1) {
    transform: translateY(9px) rotate(45deg);
}

.hamburger-menu.active span:nth-child(2) {
    opacity: 0;
    transform: translateX(-20px);
}

.hamburger-menu.active span:nth-child(3) {
    transform: translateY(-9px) rotate(-45deg);
}

/* Sidebar Close Button (mobile only) */
.sidebar-close {
    display: none;
    position: absolute;
    top: 16px;
    right: 16px;
    width: 40px;
    height: 40px;
    background: rgba(230, 57, 70, 0.15);
    border: none;
    border-radius: 8px;
    color: #e63946;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
    z-index: 10;
}

.sidebar-close:hover {
    background: rgba(230, 57, 70, 0.25);
    transform: scale(1.05);
}

.sidebar-close:active {
    transform: scale(0.95);
}

/* Sidebar Header */
.sidebar .logo {
    font-size: 1.8rem;
    font-weight: 700;
    color: #ffffff;
    padding: 32px 24px 24px 24px;
    letter-spacing: -0.5px;
    text-align: left;
    position: relative;
    background: linear-gradient(135deg, rgba(230, 57, 70, 0.15) 0%, transparent 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    margin-bottom: 8px;
}

/* Overlay for mobile sidebar */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.sidebar-overlay.active {
    opacity: 1;
}

/* ============================================
   MOBILE RESPONSIVE STYLES
   ============================================ */

@media (max-width: 768px) {
    /* Show mobile header */
    .mobile-header {
        display: flex;
    }

    /* Adjust main content for mobile header */
    .management-content {
        margin-left: 0;
        width: 100%;
        padding-top: 76px; /* 60px header + 16px spacing */
    }

    /* Hide sidebar by default on mobile */
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        width: 280px;
        max-width: 85vw;
    }

    /* Show sidebar when open */
    .sidebar.mobile-open {
        transform: translateX(0);
    }

    /* Show overlay when sidebar is open */
    .sidebar.mobile-open ~ .sidebar-overlay,
    .sidebar-overlay.active {
        display: block;
    }

    /* Show close button in sidebar */
    .sidebar-close {
        display: flex;
    }

    /* Adjust sidebar header for mobile */
    .sidebar .logo {
        padding: 20px 60px 20px 20px; /* Extra right padding for close button */
        font-size: 1.4rem;
    }

    /* Optimize sidebar navigation for mobile */
    .sidebar nav {
        padding: 12px 8px;
    }

    .sidebar nav a {
        padding: 14px 16px;
        font-size: 0.95rem;
    }
}

/* Tablet adjustments */
@media (min-width: 769px) and (max-width: 1023px) {
    /* Show mobile header on small tablets */
    .mobile-header {
        display: flex;
    }

    .management-content {
        margin-left: 0;
        padding-top: 76px;
    }

    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .sidebar.mobile-open {
        transform: translateX(0);
    }

    .sidebar-overlay.active {
        display: block;
    }

    .sidebar-close {
        display: flex;
    }
}

/* Desktop - hide mobile elements */
@media (min-width: 1024px) {
    .mobile-header {
        display: none;
    }

    .sidebar-overlay {
        display: none !important;
    }

    .sidebar-close {
        display: none;
    }

    .sidebar {
        transform: translateX(0) !important;
    }

    .management-content {
        margin-left: 260px;
        padding-top: 32px;
    }
}

/* Touch optimization for mobile */
@media (hover: none) and (pointer: coarse) {
    .hamburger-menu,
    .sidebar-close,
    .sidebar nav a {
        min-height: 44px; /* iOS touch target */
    }
}

/* Animation for smooth appearance */
@keyframes slideInFromLeft {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.sidebar.mobile-open {
    animation: slideInFromLeft 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>