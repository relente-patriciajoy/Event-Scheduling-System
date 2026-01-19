<?php
/**
 * Dynamic Sidebar Component
 * Place in: components/sidebar.php
 *
 * IMPORTANT: The parent file MUST define $role before including this file
 * Example in parent file:
 *   $role = 'user'; // or 'event_head'
 *   include('../../components/sidebar.php');
 */

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
  <div class="logo">Eventix</div>

  <nav>
    <!-- Common Links (All Users) -->
    <a href="../dashboard/home.php" class="<?= $current_page === 'home.php' ? 'active' : '' ?>">
      <i data-lucide="home"></i>
      Home
    </a>

    <a href="../dashboard/events.php" class="<?= $current_page === 'events.php' ? 'active' : '' ?>">
      <i data-lucide="calendar"></i>
      Browse Events
    </a>

    <a href="../dashboard/my_events.php" class="<?= $current_page === 'my_events.php' ? 'active' : '' ?>">
      <i data-lucide="user-check"></i>
      My Events
    </a>

    <a href="../dashboard/attendance.php" class="<?= $current_page === 'attendance.php' ? 'active' : '' ?>">
      <i data-lucide="clipboard-check"></i>
      Attendance
    </a>

    <a href="../calendar/calendar.php" class="<?= $current_page === 'calendar.php' ? 'active' : '' ?>">
      <i data-lucide="calendar-days"></i>
      Event Calendar
    </a>

    <?php if (isset($role) && $role === 'event_head'): ?>
    <!-- Event Head Management Hub -->
    <a href="../event/manage_events.php" class="<?= in_array($current_page, [
        'manage_events.php',
        'scan_qr.php',
        'view_attendance.php',
        'reports.php',
        'participant_engagement.php',
        'inactive_tracking.php'
    ]) ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard"></i>
        Event Management
    </a>
    <?php endif; ?>

    <!-- Add back to hub link on sub-pages -->
    <?php if (isset($role) && $role === 'event_head' && in_array($current_page, ['scan_qr.php', 'view_attendance.php', 'reports.php', 'participant_engagement.php', 'inactive_tracking.php'])): ?>
    <div style="padding: 0 16px; margin-top: 8px;">
      <a href="../event/manage_events.php" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: rgba(128, 0, 32, 0.1); border-radius: 8px; color: var(--maroon); font-size: 13px; text-decoration: none; font-weight: 500;">
        <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
        Back to Hub
      </a>
    </div>
    <?php endif; ?>

    <a href="../auth/logout.php?return=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
      <i data-lucide="log-out"></i>
      Logout
    </a>
  </nav>
</aside>