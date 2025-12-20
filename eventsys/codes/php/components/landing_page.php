<?php
/**
 * CCF B1G Landing Page
 * Main landing page for Christ Commission Fellowship - Be One with God
 */

include('../../includes/db.php');

// Fetch upcoming events (limit to 3 for landing page)
$query = "SELECT e.event_id, e.title, e.description, e.start_time, e.end_time 
          FROM event e 
          WHERE e.start_time >= NOW() 
          ORDER BY e.start_time ASC 
          LIMIT 3";
$events_result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="CCF B1G - Christ Commission Fellowship Be One with God. Join our community and grow in faith together.">
    <meta name="keywords" content="CCF, B1G, Christ Commission Fellowship, Be One with God, Christian Fellowship, Philippines">
    
    <title>CCF B1G - Be One With God</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../../assets/ccf-b1g-favicon.png">
    <link rel="apple-touch-icon" href="../../assets/ccf-b1g-favicon.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/landing_page.css">
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="logo">
            <img src="../../assets/ccf-b1g-favicon.png" alt="CCF B1G Logo" class="logo-image">
            <span>CCF B1G</span>
        </div>
        <ul class="nav-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#events">Events</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        <a href="../auth/index.php" class="cta-button">Login / Register</a>
        <button class="mobile-menu-btn">
            <i data-lucide="menu"></i>
        </button>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Be One With <span class="highlight">God</span></h1>
                <p>Join CCF B1G community and grow in faith together. Experience authentic fellowship, spiritual growth, and purposeful living.</p>
                <div class="hero-buttons">
                    <a href="#events" class="primary-btn">
                        Explore Events
                        <i data-lucide="arrow-right"></i>
                    </a>
                    <a href="#about" class="secondary-btn">Learn More</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-card">
                    <h3>Our Mission</h3>
                    <p>To make disciples who make disciples for Christ. We are committed to helping every believer grow in their relationship with God and share His love with others.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <h2 class="section-title">Why Join CCF B1G?</h2>
        <p class="section-subtitle">Grow deeper in your faith journey</p>
        <div class="about-content">
            <div class="about-card">
                <div class="about-icon">
                    <i data-lucide="users"></i>
                </div>
                <h3>Community</h3>
                <p>Connect with like-minded believers who support and encourage one another in faith and life.</p>
            </div>
            <div class="about-card">
                <div class="about-icon">
                    <i data-lucide="book-open"></i>
                </div>
                <h3>Bible Study</h3>
                <p>Engage in meaningful discussions and dive deeper into God's Word through our weekly studies.</p>
            </div>
            <div class="about-card">
                <div class="about-icon">
                    <i data-lucide="heart"></i>
                </div>
                <h3>Service</h3>
                <p>Make a difference in the community through various outreach programs and ministries.</p>
            </div>
        </div>
    </section>

    <!-- Events Section (from database) -->
    <section class="events" id="events">
        <h2 class="section-title">Upcoming Events</h2>
        <p class="section-subtitle">Join us and be part of something special</p>
        <div class="events-grid">
            <?php if ($events_result->num_rows > 0): ?>
                <?php while ($event = $events_result->fetch_assoc()): ?>
                    <div class="event-card">
                        <div class="event-image">
                            <i data-lucide="calendar"></i>
                        </div>
                        <div class="event-content">
                            <h3><?= htmlspecialchars($event['title']) ?></h3>
                            <div class="event-date">
                                <i data-lucide="clock"></i>
                                <?= date('F j, Y - g:i A', strtotime($event['start_time'])) ?>
                            </div>
                            <p><?= htmlspecialchars(substr($event['description'], 0, 120)) ?>...</p>
                            <a href="../auth/index.php" class="event-link">
                                Register Now
                                <i data-lucide="arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Default events if none in database -->
                <div class="event-card">
                    <div class="event-image">
                        <i data-lucide="calendar"></i>
                    </div>
                    <div class="event-content">
                        <h3>Weekly Fellowship</h3>
                        <div class="event-date">
                            <i data-lucide="clock"></i>
                            Every Friday, 7:00 PM
                        </div>
                        <p>Join us for our regular fellowship meetings filled with worship, teaching, and fellowship.</p>
                        <a href="../auth/index.php" class="event-link">
                            Register Now
                            <i data-lucide="arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="event-card">
                    <div class="event-image">
                        <i data-lucide="book-open"></i>
                    </div>
                    <div class="event-content">
                        <h3>Bible Study Group</h3>
                        <div class="event-date">
                            <i data-lucide="clock"></i>
                            Every Wednesday, 6:30 PM
                        </div>
                        <p>Deep dive into Scripture and grow together in understanding God's Word.</p>
                        <a href="../auth/index.php" class="event-link">
                            Register Now
                            <i data-lucide="arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="event-card">
                    <div class="event-image">
                        <i data-lucide="music"></i>
                    </div>
                    <div class="event-content">
                        <h3>Worship Night</h3>
                        <div class="event-date">
                            <i data-lucide="clock"></i>
                            Monthly, First Saturday
                        </div>
                        <p>Experience powerful worship and encounter God's presence in a special way.</p>
                        <a href="../auth/index.php" class="event-link">
                            Register Now
                            <i data-lucide="arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div style="text-align: center; margin-top: 3rem;">
            <a href="../auth/index.php" class="primary-btn">View All Events</a>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>CCF B1G</h3>
                <p>Christ Commission Fellowship - Be One with God. A community of believers passionate about growing in faith and making disciples.</p>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Facebook">
                        <i data-lucide="facebook"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Instagram">
                        <i data-lucide="instagram"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Email">
                        <i data-lucide="mail"></i>
                    </a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <p>
                    <a href="#home" style="color: rgba(255,255,255,0.7); text-decoration: none;">Home</a><br>
                    <a href="#about" style="color: rgba(255,255,255,0.7); text-decoration: none;">About Us</a><br>
                    <a href="#events" style="color: rgba(255,255,255,0.7); text-decoration: none;">Events</a><br>
                    <a href="../auth/index.php" style="color: rgba(255,255,255,0.7); text-decoration: none;">Login</a>
                </p>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>
                    Email: email@ccfb1g.ph<br>
                    Phone: (02) 8772 3035<br>
                    Location: 3F CCF Bldg., Madrigal Business Park, Prime Street, Muntinlupa, 1780 Metro Manila
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> CCF B1G. All rights reserved. | Made with ❤️ for God's glory</p>
        </div>
    </footer>

    <!-- Custom JavaScript -->
    <script src="../../js/landing_page.js"></script>
</body>
</html>