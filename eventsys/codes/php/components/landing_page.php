<?php
/**
 * CCF B1G Landing Page
 * Main landing page - Shows ALL events without requiring login
 */

include('../../includes/db.php');

// Fetch ALL upcoming and past events (no limit)
$query = "SELECT e.event_id, e.title, e.description, e.start_time, e.end_time, 
          v.name AS venue_name, v.city,
          (e.capacity - COUNT(r.registration_id)) AS available_seats,
          e.capacity, e.price
          FROM event e 
          LEFT JOIN venue v ON e.venue_id = v.venue_id
          LEFT JOIN registration r ON e.event_id = r.event_id
          WHERE e.start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          GROUP BY e.event_id
          ORDER BY e.start_time ASC";
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

    <!-- ALL Events Section -->
    <section class="events" id="events">
        <h2 class="section-title">All Events</h2>
        <p class="section-subtitle">View all our events - Login to register</p>
        
        <div class="events-grid">
            <?php if ($events_result && $events_result->num_rows > 0): ?>
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
                            
                            <?php if ($event['venue_name']): ?>
                                <div class="event-date">
                                    <i data-lucide="map-pin"></i>
                                    <?= htmlspecialchars($event['venue_name']) ?>
                                    <?= $event['city'] ? ', ' . htmlspecialchars($event['city']) : '' ?>
                                </div>
                            <?php endif; ?>
                            
                            <p><?= htmlspecialchars(substr($event['description'], 0, 120)) ?>...</p>
                            
                            <div style="margin: 12px 0; display: flex; gap: 16px; flex-wrap: wrap; font-size: 0.9rem;">
                                <span style="color: #800020; font-weight: 600;">
                                    <i data-lucide="ticket" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle;"></i>
                                    <?= $event['available_seats'] ?> / <?= $event['capacity'] ?> available
                                </span>
                                <span style="color: #059669; font-weight: 600;">
                                    <i data-lucide="dollar-sign" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle;"></i>
                                    <?= $event['price'] > 0 ? '$' . number_format($event['price'], 2) : 'FREE' ?>
                                </span>
                            </div>
                            
                            <a href="../auth/index.php" class="event-link">
                                Login to Register
                                <i data-lucide="arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- No events message -->
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
                    <i data-lucide="calendar-x" style="width: 80px; height: 80px; color: #ccc; margin: 0 auto 20px;"></i>
                    <h3 style="color: #666; margin-bottom: 10px;">No Events Available</h3>
                    <p style="color: #999;">Check back soon for upcoming events!</p>
                </div>
            <?php endif; ?>
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