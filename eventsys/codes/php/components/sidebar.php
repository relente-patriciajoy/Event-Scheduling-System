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
        <!-- Event Head Exclusive Links -->
        <a href="../event/manage_events.php" class="<?= $current_page === 'manage_events.php' ? 'active' : '' ?>">
            <i data-lucide="settings"></i>
            Manage Events
        </a>

        <a href="../qr/scan_qr.php" class="<?= $current_page === 'scan_qr.php' ? 'active' : '' ?>">
            <i data-lucide="scan"></i>
            QR Scanner
        </a>

        <a href="../event/view_attendance.php" class="<?= $current_page === 'view_attendance.php' ? 'active' : '' ?>">
            <i data-lucide="eye"></i>
            View Attendance
        </a>

        <a href="../event/reports.php" class="<?= $current_page === 'reports.php' ? 'active' : '' ?>">
            <i data-lucide="file-text"></i>
            Reports
        </a>

        <a href="../event/inactive_tracking.php" class="<?= $current_page === 'inactive_tracking.php' ? 'active' : '' ?>">
            <i data-lucide="user-x"></i>
            Inactive Members
        </a>
        <?php endif; ?>

        <a href="../auth/logout.php">
            <i data-lucide="log-out"></i>
            Logout
        </a>
    </nav>
</aside>