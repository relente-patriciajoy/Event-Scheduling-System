<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}

include('../../includes/db.php');

$user_id = $_SESSION['user_id'];

// Check role
$role_stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if ($role !== 'event_head') {
    die("Access denied. This feature is only available for Event Organizers.");
}

// Get organizer's ID
$email_stmt = $conn->prepare("SELECT email FROM user WHERE user_id = ?");
$email_stmt->bind_param("i", $user_id);
$email_stmt->execute();
$email_stmt->bind_result($email);
$email_stmt->fetch();
$email_stmt->close();

$org_stmt = $conn->prepare("SELECT organizer_id FROM organizer WHERE contact_email = ?");
$org_stmt->bind_param("s", $email);
$org_stmt->execute();
$org_stmt->bind_result($organizer_id);
$org_stmt->fetch();
$org_stmt->close();

// Overall engagement metrics query
$engagement_query = $conn->prepare("
    SELECT 
        u.user_id,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.email,
        u.phone,
        COUNT(DISTINCT r.event_id) as total_events_registered,
        COUNT(DISTINCT CASE WHEN a.attendance_id IS NOT NULL THEN r.event_id END) as events_attended,
        COUNT(DISTINCT CASE WHEN a.attendance_id IS NULL AND e.end_time < NOW() THEN r.event_id END) as events_missed,
        ROUND(
            (COUNT(DISTINCT CASE WHEN a.attendance_id IS NOT NULL THEN r.event_id END) * 100.0) / 
            NULLIF(COUNT(DISTINCT CASE WHEN e.end_time < NOW() THEN r.event_id END), 0), 
            2
        ) as attendance_rate,
        MIN(r.registration_date) as first_registration,
        MAX(r.registration_date) as last_registration,
        MAX(e.start_time) as last_event_date,
        DATEDIFF(NOW(), MIN(r.registration_date)) as days_as_participant,
        CASE 
            WHEN COUNT(DISTINCT r.event_id) >= 5 AND 
                 (COUNT(DISTINCT CASE WHEN a.attendance_id IS NOT NULL THEN r.event_id END) * 100.0) / 
                 NULLIF(COUNT(DISTINCT CASE WHEN e.end_time < NOW() THEN r.event_id END), 0) >= 80 
            THEN 'Highly Engaged'
            
            WHEN COUNT(DISTINCT r.event_id) >= 3 AND 
                 (COUNT(DISTINCT CASE WHEN a.attendance_id IS NOT NULL THEN r.event_id END) * 100.0) / 
                 NULLIF(COUNT(DISTINCT CASE WHEN e.end_time < NOW() THEN r.event_id END), 0) >= 60 
            THEN 'Active'
            
            WHEN COUNT(DISTINCT r.event_id) >= 2 AND 
                 (COUNT(DISTINCT CASE WHEN a.attendance_id IS NOT NULL THEN r.event_id END) * 100.0) / 
                 NULLIF(COUNT(DISTINCT CASE WHEN e.end_time < NOW() THEN r.event_id END), 0) >= 40 
            THEN 'Moderate'
            
            WHEN (COUNT(DISTINCT CASE WHEN a.attendance_id IS NOT NULL THEN r.event_id END) * 100.0) / 
                 NULLIF(COUNT(DISTINCT CASE WHEN e.end_time < NOW() THEN r.event_id END), 0) < 40 
                 OR COUNT(DISTINCT CASE WHEN a.attendance_id IS NOT NULL THEN r.event_id END) = 0
            THEN 'At Risk'
            
            ELSE 'New'
        END as engagement_level
    FROM registration r
    JOIN user u ON r.user_id = u.user_id
    JOIN event e ON r.event_id = e.event_id
    LEFT JOIN attendance a ON r.registration_id = a.registration_id
    WHERE e.organizer_id = ?
    GROUP BY u.user_id
    ORDER BY attendance_rate DESC, total_events_registered DESC
");

$engagement_query->bind_param("i", $organizer_id);
$engagement_query->execute();
$participants = $engagement_query->get_result();

// Calculate summary statistics
$total_unique = 0;
$highly_engaged = 0;
$active = 0;
$moderate = 0;
$at_risk = 0;
$new_members = 0;
$total_attendance_rate = 0;
$count = 0;

$participants->data_seek(0);
while ($p = $participants->fetch_assoc()) {
    $total_unique++;
    $count++;
    $total_attendance_rate += $p['attendance_rate'] ?? 0;
    
    switch ($p['engagement_level']) {
        case 'Highly Engaged':
            $highly_engaged++;
            break;
        case 'Active':
            $active++;
            break;
        case 'Moderate':
            $moderate++;
            break;
        case 'At Risk':
            $at_risk++;
            break;
        case 'New':
            $new_members++;
            break;
    }
}

$avg_attendance_rate = $count > 0 ? round($total_attendance_rate / $count, 2) : 0;
$participants->data_seek(0);

// Get monthly trends for the last 6 months
$trends_query = $conn->prepare("
    SELECT 
        DATE_FORMAT(e.start_time, '%Y-%m') as month,
        DATE_FORMAT(e.start_time, '%b %Y') as month_name,
        COUNT(DISTINCT r.registration_id) as total_registrations,
        COUNT(DISTINCT CASE WHEN a.attendance_id IS NOT NULL THEN r.registration_id END) as attended,
        COUNT(DISTINCT r.user_id) as unique_participants,
        ROUND(
            (COUNT(DISTINCT CASE WHEN a.attendance_id IS NOT NULL THEN r.registration_id END) * 100.0) / 
            NULLIF(COUNT(DISTINCT r.registration_id), 0),
            2
        ) as attendance_rate
    FROM event e
    JOIN registration r ON e.event_id = r.event_id
    LEFT JOIN attendance a ON r.registration_id = a.registration_id
    WHERE e.organizer_id = ?
        AND e.start_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        AND e.end_time < NOW()
    GROUP BY DATE_FORMAT(e.start_time, '%Y-%m')
    ORDER BY month DESC
");

$trends_query->bind_param("i", $organizer_id);
$trends_query->execute();
$trends = $trends_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant Engagement - Eventix</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/event_head.css">
    <link rel="stylesheet" href="../../css/participant_engagement.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="dashboard-layout event-head-page">
    <!-- Sidebar -->
    <?php include('../components/sidebar.php'); ?>

    <main class="main-content">
        <header class="banner event-head-banner">
            <div>
                <div class="event-head-badge">
                    <i data-lucide="briefcase"></i>
                    Event Organizer
                </div>
                <h1>Participant Engagement Analytics</h1>
                <p>Track participant behavior and engagement across all your events</p>
            </div>
            <img src="../../assets/eventix-logo.png" alt="Eventix logo" />
        </header>

        <div class="engagement-container">
            <!-- Summary Dashboard -->
            <div class="metrics-dashboard">
                <div class="metric-card metric-primary">
                    <div class="metric-icon">
                        <i data-lucide="users"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-label">Total Unique Participants</div>
                        <div class="metric-value"><?= $total_unique ?></div>
                        <small>Across all your events</small>
                    </div>
                </div>

                <div class="metric-card metric-success">
                    <div class="metric-icon">
                        <i data-lucide="trending-up"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-label">Average Attendance Rate</div>
                        <div class="metric-value"><?= $avg_attendance_rate ?>%</div>
                        <small>Overall performance</small>
                    </div>
                </div>

                <div class="metric-card metric-info">
                    <div class="metric-icon">
                        <i data-lucide="star"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-label">Highly Engaged</div>
                        <div class="metric-value"><?= $highly_engaged ?></div>
                        <small>5+ events, 80%+ attendance</small>
                    </div>
                </div>

                <div class="metric-card metric-warning">
                    <div class="metric-icon">
                        <i data-lucide="alert-circle"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-label">At Risk</div>
                        <div class="metric-value"><?= $at_risk ?></div>
                        <small>Need attention</small>
                    </div>
                </div>
            </div>

            <!-- Engagement Distribution -->
            <div class="engagement-distribution">
                <h3>
                    <i data-lucide="pie-chart"></i>
                    Engagement Level Distribution
                </h3>
                <div class="distribution-grid">
                    <div class="distribution-item level-highly-engaged">
                        <div class="distribution-bar" style="width: <?= $total_unique > 0 ? ($highly_engaged / $total_unique * 100) : 0 ?>%"></div>
                        <div class="distribution-label">
                            <span class="level-name">Highly Engaged</span>
                            <span class="level-count"><?= $highly_engaged ?></span>
                        </div>
                    </div>
                    <div class="distribution-item level-active">
                        <div class="distribution-bar" style="width: <?= $total_unique > 0 ? ($active / $total_unique * 100) : 0 ?>%"></div>
                        <div class="distribution-label">
                            <span class="level-name">Active</span>
                            <span class="level-count"><?= $active ?></span>
                        </div>
                    </div>
                    <div class="distribution-item level-moderate">
                        <div class="distribution-bar" style="width: <?= $total_unique > 0 ? ($moderate / $total_unique * 100) : 0 ?>%"></div>
                        <div class="distribution-label">
                            <span class="level-name">Moderate</span>
                            <span class="level-count"><?= $moderate ?></span>
                        </div>
                    </div>
                    <div class="distribution-item level-at-risk">
                        <div class="distribution-bar" style="width: <?= $total_unique > 0 ? ($at_risk / $total_unique * 100) : 0 ?>%"></div>
                        <div class="distribution-label">
                            <span class="level-name">At Risk</span>
                            <span class="level-count"><?= $at_risk ?></span>
                        </div>
                    </div>
                    <div class="distribution-item level-new">
                        <div class="distribution-bar" style="width: <?= $total_unique > 0 ? ($new_members / $total_unique * 100) : 0 ?>%"></div>
                        <div class="distribution-label">
                            <span class="level-name">New</span>
                            <span class="level-count"><?= $new_members ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trends Chart -->
            <?php if ($trends->num_rows > 0): ?>
            <div class="trends-section">
                <h3>
                    <i data-lucide="activity"></i>
                    Participation Trends (Last 6 Months)
                </h3>
                <canvas id="trendsChart"></canvas>
            </div>
            <?php endif; ?>

            <!-- Participants Table -->
            <div class="participants-section">
                <div class="section-header">
                    <h3>
                        <i data-lucide="users"></i>
                        All Participants
                    </h3>
                    <div class="section-actions">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search participants...">
                        <select id="filterEngagement" class="filter-select">
                            <option value="all">All Levels</option>
                            <option value="Highly Engaged">Highly Engaged</option>
                            <option value="Active">Active</option>
                            <option value="Moderate">Moderate</option>
                            <option value="At Risk">At Risk</option>
                            <option value="New">New</option>
                        </select>
                        <button onclick="exportToExcel()" class="export-btn">
                            <i data-lucide="download"></i>
                            Export
                        </button>
                    </div>
                </div>

                <?php if ($participants->num_rows > 0): ?>
                <table class="engagement-table" id="engagementTable">
                    <thead>
                        <tr>
                            <th>Participant</th>
                            <th>Email</th>
                            <th>Total Events</th>
                            <th>Attended</th>
                            <th>Missed</th>
                            <th>Attendance Rate</th>
                            <th>Engagement Level</th>
                            <th>Member Since</th>
                            <th>Last Event</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $participants->data_seek(0);
                        while ($participant = $participants->fetch_assoc()):
                            $full_name = trim($participant['first_name'] . ' ' . ($participant['middle_name'] ?: '') . ' ' . $participant['last_name']);
                            $engagement_class = strtolower(str_replace(' ', '-', $participant['engagement_level']));
                        ?>
                        <tr data-engagement="<?= htmlspecialchars($participant['engagement_level']) ?>">
                            <td><?= htmlspecialchars($full_name) ?></td>
                            <td><?= htmlspecialchars($participant['email']) ?></td>
                            <td><?= $participant['total_events_registered'] ?></td>
                            <td><span class="badge badge-success"><?= $participant['events_attended'] ?></span></td>
                            <td><span class="badge badge-danger"><?= $participant['events_missed'] ?></span></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $participant['attendance_rate'] ?? 0 ?>%"></div>
                                    <span class="progress-text"><?= $participant['attendance_rate'] ?? 0 ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="engagement-badge engagement-<?= $engagement_class ?>">
                                    <?= $participant['engagement_level'] ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($participant['first_registration'])) ?></td>
                            <td><?= $participant['last_event_date'] ? date('M j, Y', strtotime($participant['last_event_date'])) : 'N/A' ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i data-lucide="inbox"></i>
                    <h3>No Participants Yet</h3>
                    <p>Start creating events to see participant engagement data.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // Trends Chart
        <?php if ($trends->num_rows > 0): ?>
        const trendsData = {
            labels: [
                <?php
                $trends->data_seek(0);
                $months = [];
                while ($t = $trends->fetch_assoc()) {
                    $months[] = "'" . $t['month_name'] . "'";
                }
                echo implode(', ', array_reverse($months));
                ?>
            ],
            datasets: [
                {
                    label: 'Registrations',
                    data: [
                        <?php
                        $trends->data_seek(0);
                        $regs = [];
                        while ($t = $trends->fetch_assoc()) {
                            $regs[] = $t['total_registrations'];
                        }
                        echo implode(', ', array_reverse($regs));
                        ?>
                    ],
                    borderColor: '#e63946',
                    backgroundColor: 'rgba(230, 57, 70, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Attended',
                    data: [
                        <?php
                        $trends->data_seek(0);
                        $attended = [];
                        while ($t = $trends->fetch_assoc()) {
                            $attended[] = $t['attended'];
                        }
                        echo implode(', ', array_reverse($attended));
                        ?>
                    ],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }
            ]
        };

        const ctx = document.getElementById('trendsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: trendsData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('filterEngagement').addEventListener('change', filterTable);

        function filterTable() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const filterValue = document.getElementById('filterEngagement').value;
            const table = document.getElementById('engagementTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[1].textContent.toLowerCase();
                const engagement = row.getAttribute('data-engagement');

                const matchesSearch = name.includes(searchValue) || email.includes(searchValue);
                const matchesFilter = filterValue === 'all' || engagement === filterValue;

                row.style.display = matchesSearch && matchesFilter ? '' : 'none';
            }
        }

        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('engagementTable');
            let tableHTML = table.outerHTML.replace(/ /g, '%20');
            const filename = 'participant_engagement_<?= date('Y-m-d') ?>.xls';
            
            const downloadLink = document.createElement('a');
            document.body.appendChild(downloadLink);
            downloadLink.href = 'data:application/vnd.ms-excel,' + tableHTML;
            downloadLink.download = filename;
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>