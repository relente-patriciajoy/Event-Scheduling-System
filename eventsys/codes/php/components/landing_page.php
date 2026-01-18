<?php

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
    <meta name="description" content="CCF B1G - Join our vibrant sports community. Experience faith, fellowship, and fun!">
    <meta name="keywords" content="CCF, B1G, Sports Events, Christian Fellowship, Philippines">
    
    <title>CCF B1G - Be One With God | Sports & Fellowship</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../../assets/ccf-b1g-favicon.png">
    <link rel="apple-touch-icon" href="../../assets/ccf-b1g-favicon.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/landing_page.css">

    <style>
        /* Additional inline styles for enhanced visuals */
        .event-info-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            background: rgba(139, 0, 0, 0.05);
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--maroon);
            margin: 0.3rem;
            transition: all 0.3s ease;
        }

        .event-info-badge:hover {
            background: var(--maroon);
            color: white;
            transform: translateY(-2px);
        }

        .event-badges-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .price-free {
            color: #059669;
        }

        .price-paid {
            color: var(--maroon);
        }

        .seats-low {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
        }

        .no-events-container {
            grid-column: 1 / -1;
            text-align: center;
            padding: 5rem 2rem;
            background: rgba(139, 0, 0, 0.02);
            border-radius: 25px;
            border: 2px dashed rgba(139, 0, 0, 0.2);
        }

        .no-events-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.5rem;
            color: rgba(139, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav id="navbar">
        <div class="logo">
            <img src="../../assets/ccf-b1g-favicon.png" alt="CCF B1G Logo" class="logo-image">
            <span>CCF B1G</span>
        </div>
        <ul class="nav-links">
            <li><a href="#home" class="active-link">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#events">Events</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        <a href="../auth/index.php" class="cta-button">Login / Register</a>
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <i data-lucide="menu"></i>
        </button>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <!-- Floating Sports Icons -->
        <div class="floating-sports">
            <div class="floating-icon">⚽</div>
            <div class="floating-icon">🏀</div>
            <div class="floating-icon">🏐</div>
            <div class="floating-icon">🎾</div>
            <div class="floating-icon">⚾</div>
            <div class="floating-icon">🏃</div>
        </div>

        <div class="hero-content">
            <div class="hero-text">
                <h1>Be One With <span class="highlight">God</span></h1>
                <p>Join our vibrant CCF B1G community! Experience authentic fellowship, spiritual growth, and exciting sports events. Let's grow together in faith and fun!</p>
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
                    <p>To make disciples who make disciples for Christ. We're committed to helping every believer grow in their relationship with God through fellowship, sports, and community service.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <h2 class="section-title">Why Join CCF B1G?</h2>
        <p class="section-subtitle">Experience faith, fellowship & fun!</p>
        <div class="about-content">
            <div class="about-card">
                <div class="about-icon">
                    <i data-lucide="users" style="width: 40px; height: 40px;"></i>
                </div>
                <h3>Community</h3>
                <p>Connect with passionate believers who support each other in faith and life. Build lasting friendships through sports and fellowship!</p>
            </div>
            <div class="about-card">
                <div class="about-icon">
                    <i data-lucide="book-open" style="width: 40px; height: 40px;"></i>
                </div>
                <h3>Bible Study</h3>
                <p>Dive deeper into God's Word through engaging discussions and meaningful studies that transform lives.</p>
            </div>
            <div class="about-card">
                <div class="about-icon">
                    <i data-lucide="heart" style="width: 40px; height: 40px;"></i>
                </div>
                <h3>Service</h3>
                <p>Make a real difference through sports outreach, community programs, and various ministries that impact lives.</p>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section class="events" id="events">
        <h2 class="section-title">Upcoming Events</h2>
        <p class="section-subtitle">Join us for exciting activities! Login to register</p>
        
        <div class="events-grid">
            <?php if ($events_result && $events_result->num_rows > 0): ?>
                <?php while ($event = $events_result->fetch_assoc()):
                    $available = $event['available_seats'];
                    $capacity = $event['capacity'];
                    $percentage = ($available / $capacity) * 100;
                    $isLowSeats = $percentage < 30;
                ?>
                    <div class="event-card">
                        <div class="event-image">
                            <i data-lucide="trophy" style="width: 70px; height: 70px;"></i>
                        </div>
                        <div class="event-content">
                            <h3><?= htmlspecialchars($event['title']) ?></h3>
                            
                            <div class="event-date">
                                <i data-lucide="clock" style="width: 18px; height: 18px;"></i>
                                <?= date('F j, Y • g:i A', strtotime($event['start_time'])) ?>
                            </div>
                            
                            <?php if ($event['venue_name']): ?>
                                <div class="event-date">
                                    <i data-lucide="map-pin" style="width: 18px; height: 18px;"></i>
                                    <?= htmlspecialchars($event['venue_name']) ?>
                                    <?= $event['city'] ? ', ' . htmlspecialchars($event['city']) : '' ?>
                                </div>
                            <?php endif; ?>
                            
                            <p><?= htmlspecialchars(substr($event['description'], 0, 100)) ?>...</p>
                            
                            <div class="event-badges-container">
                                <span class="event-info-badge <?= $isLowSeats ? 'seats-low' : '' ?>">
                                    <i data-lucide="users" style="width: 16px; height: 16px;"></i>
                                    <?= $available ?> / <?= $capacity ?> seats
                                </span>
                                <span class="event-info-badge <?= $event['price'] > 0 ? 'price-paid' : 'price-free' ?>">
                                    <i data-lucide="ticket" style="width: 16px; height: 16px;"></i>
                                    <?= $event['price'] > 0 ? '₱' . number_format($event['price'], 2) : 'FREE' ?>
                                </span>
                            </div>
                            
                            <a href="../auth/index.php" class="event-link">
                                Register Now
                                <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-events-container">
                    <div class="no-events-icon">
                        <i data-lucide="calendar-off" style="width: 100px; height: 100px;"></i>
                    </div>
                    <h3 style="color: var(--maroon); font-size: 2rem; margin-bottom: 1rem;">No Events Available</h3>
                    <p style="color: var(--gray); font-size: 1.1rem; max-width: 500px; margin: 0 auto;">
                        We're planning something awesome! Check back soon for exciting upcoming events and activities.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>CCF B1G</h3>
                <p>Christ Commission Fellowship - Be One with God. A vibrant community of believers passionate about faith, fellowship, and sports!</p>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Facebook">
                        <i data-lucide="facebook"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Instagram">
                        <i data-lucide="instagram"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Twitter">
                        <i data-lucide="twitter"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Email">
                        <i data-lucide="mail"></i>
                    </a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <p>
                    <a href="#home">Home</a><br>
                    <a href="#about">About Us</a><br>
                    <a href="#events">Events</a><br>
                    <a href="../auth/index.php">Login / Register</a><br>
                    <a href="#contact">Contact</a>
                </p>
            </div>
            <div class="footer-section">
                <h3>Get In Touch</h3>
                <p>
                    <strong>Email:</strong> info@ccfb1g.ph<br>
                    <strong>Phone:</strong> (02) 8772 3035<br>
                    <strong>Address:</strong> 3F CCF Building<br>
                    Madrigal Business Park<br>
                    Muntinlupa, Metro Manila
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> CCF B1G. All rights reserved. Made with ❤️ for God's glory</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        function toggleMobileMenu() {
            navLinks.classList.toggle('active');
            const icon = mobileMenuBtn.querySelector('i');
            if (navLinks.classList.contains('active')) {
                icon.setAttribute('data-lucide', 'x');
            } else {
                icon.setAttribute('data-lucide', 'menu');
            }
            lucide.createIcons();
        }

        mobileMenuBtn.addEventListener('click', toggleMobileMenu);

        // Close mobile menu when clicking a link
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    navLinks.classList.remove('active');
                    const icon = mobileMenuBtn.querySelector('i');
                    icon.setAttribute('data-lucide', 'menu');
                    lucide.createIcons();
                }
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('nav') && navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
                const icon = mobileMenuBtn.querySelector('i');
                icon.setAttribute('data-lucide', 'menu');
                lucide.createIcons();
            }
        });

        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const navHeight = document.querySelector('nav').offsetHeight;
                    const targetPosition = target.offsetTop - navHeight;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Update active link on scroll
        const sections = document.querySelectorAll('section[id]');
        const navLinksAll = document.querySelectorAll('.nav-links a');

        function updateActiveLink() {
            let current = 'home';
            const scrollPos = window.pageYOffset + 100;

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });

            navLinksAll.forEach(link => {
                link.classList.remove('active-link');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active-link');
                }
            });
        }

        window.addEventListener('scroll', updateActiveLink);
        updateActiveLink();
    </script>
</body>
</html>