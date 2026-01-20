<?php
include('../../includes/db.php');

// Fetch upcoming events
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
    <meta name="theme-color" content="#8B0000">
    <meta name="description" content="CCF B1G Sports - Unite. Compete. Inspire. Join the most energetic Christian sports community!">
    
    <title>CCF B1G Sports - Unite. Compete. Inspire.</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="../../manifest.json">

    <!-- Favicons -->
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
</head>
<body>
    <!-- Navigation -->
    <nav id="navbar">
        <div class="logo">
            <img src="../../assets/ccf-b1g-favicon.png" alt="CCF B1G Logo" class="logo-image">
            <span>B1G SPORTS</span>
        </div>

        <ul class="nav-links" id="navLinks">
            <li><a href="#home" class="active-link">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#events">Events</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>

        <a href="../auth/index.php" class="cta-button">Join Now</a>

        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle menu">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </nav>

    <!-- Navigation Overlay -->
    <div class="nav-overlay" id="navOverlay"></div>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="floating-sports">
            <div class="floating-icon">⚽</div>
            <div class="floating-icon">🏀</div>
            <div class="floating-icon">🏐</div>
            <div class="floating-icon">⚾</div>
            <div class="floating-icon">🎾</div>
            <div class="floating-icon">🏃</div>
        </div>

        <div class="hero-content">
            <div class="hero-text">
                <span class="sports-badge">⚡ CCF B1G SPORTS</span>
                <h1>Unite. <span class="highlight">Compete.</span> Inspire.</h1>
                <p>Experience the thrill of competitive sports while growing in faith! Join CCF B1G Sports - where passion meets purpose, and every game is an opportunity to glorify God.</p>

                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Active Players</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Events Yearly</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">10+</span>
                        <span class="stat-label">Sports</span>
                    </div>
                </div>

                <div class="hero-buttons">
                    <a href="#events" class="primary-btn">
                        View Events
                        <i data-lucide="zap" style="width: 20px; height: 20px;"></i>
                    </a>
                    <a href="#about" class="secondary-btn">
                        Learn More
                        <i data-lucide="arrow-right" style="width: 20px; height: 20px;"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Sports Values Section -->
    <section class="sports-values" id="about">
        <div class="section-header">
            <span class="section-tag">OUR DNA</span>
            <h2 class="section-title">What Makes Us <span class="highlight">Different</span></h2>
            <p class="section-subtitle">More than just sports - it's a movement of faith, excellence, and community</p>
        </div>

        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i data-lucide="trophy" style="width: 40px; height: 40px; color: white;"></i>
                </div>
                <h3>Competitive Excellence</h3>
                <p>We bring our A-game to every match. Train hard, play harder, and push your limits while honoring God through sportsmanship and dedication.</p>
            </div>

            <div class="value-card">
                <div class="value-icon">
                    <i data-lucide="users" style="width: 40px; height: 40px; color: white;"></i>
                </div>
                <h3>Brotherhood & Unity</h3>
                <p>Build lifelong friendships with teammates who share your passion. Together we're stronger, on and off the court.</p>
            </div>

            <div class="value-card">
                <div class="value-icon">
                    <i data-lucide="heart" style="width: 40px; height: 40px; color: white;"></i>
                </div>
                <h3>Faith in Action</h3>
                <p>Sports as ministry. Every game, every practice is an opportunity to demonstrate Christ's love and excellence to the world.</p>
            </div>

            <div class="value-card">
                <div class="value-icon">
                    <i data-lucide="target" style="width: 40px; height: 40px; color: white;"></i>
                </div>
                <h3>Skill Development</h3>
                <p>Level up your game with professional coaching, strategic training, and access to top-tier facilities and equipment.</p>
            </div>

            <div class="value-card">
                <div class="value-icon">
                    <i data-lucide="zap" style="width: 40px; height: 40px; color: white;"></i>
                </div>
                <h3>High Energy Culture</h3>
                <p>Experience the adrenaline rush of competition in an atmosphere that's electric, encouraging, and uplifting.</p>
            </div>

            <div class="value-card">
                <div class="value-icon">
                    <i data-lucide="globe" style="width: 40px; height: 40px; color: white;"></i>
                </div>
                <h3>Community Impact</h3>
                <p>Use sports to make a difference. From outreach programs to charity tournaments, we play with purpose.</p>
            </div>
        </div>
    </section>

    <section class="sports-highlights" id="highlights">
      <div class="section-header">
          <span class="section-tag">EVENT GALLERY</span>
          <h2 class="section-title">Event <span class="highlight">Highlights</span></h2>
          <p class="section-subtitle">Showcasing successful events powered by our platform</p>
      </div>

      <div class="highlights-container">
          <!-- Main Featured Highlight -->
          <div class="highlight-featured" id="featuredHighlight">
              <div class="highlight-image-wrapper">
                  <img src="../../assets/highlights/soccer-sport.jpg" alt="Sports Fest" class="highlight-image">
                  <div class="highlight-overlay">
                      <div class="highlight-play-btn">
                          <i data-lucide="play" style="width: 40px; height: 40px;"></i>
                      </div>
                  </div>
              </div>
              <div class="highlight-info">
                  <h3 class="highlight-title">Sports Fest</h3>
                  <div class="highlight-meta">
                      <span class="highlight-date">
                          <i data-lucide="calendar" style="width: 16px; height: 16px;"></i>
                          July 27, 2025
                      </span>
                      <span class="highlight-attendees">
                          <i data-lucide="users" style="width: 16px; height: 16px;"></i>
                          45 Attendees
                      </span>
                  </div>
              </div>
          </div>

          <!-- Highlight Thumbnails -->
          <div class="highlights-grid">
              <div class="highlight-thumb active" data-index="0">
                  <img src="../../assets/highlights/soccer-sport.jpg" alt="Library Leadership">
                  <div class="thumb-overlay">
                      <span class="thumb-number">1</span>
                  </div>
              </div>
              <div class="highlight-thumb" data-index="1">
                  <img src="../../assets/highlights/volleyball-sport.jpg" alt="Tech Innovation Summit">
                  <div class="thumb-overlay">
                      <span class="thumb-number">2</span>
                  </div>
              </div>
              <div class="highlight-thumb" data-index="2">
                  <img src="../../assets/highlights/badminton-sport.jpg" alt="Community Outreach">
                  <div class="thumb-overlay">
                      <span class="thumb-number">3</span>
                  </div>
              </div>
              <div class="highlight-thumb" data-index="3">
                  <img src="../../assets/highlights/pickleball-sport.jpg" alt="Annual Sports Tournament">
                  <div class="thumb-overlay">
                      <span class="thumb-number">4</span>
                  </div>
              </div>
          </div>

          <!-- Navigation Arrows -->
          <div class="highlights-navigation">
              <button class="nav-arrow prev" id="prevHighlight" aria-label="Previous highlight">
                  <i data-lucide="chevron-left"></i>
              </button>
              <button class="nav-arrow next" id="nextHighlight" aria-label="Next highlight">
                  <i data-lucide="chevron-right"></i>
              </button>
          </div>
      </div>
    </section>

    <!-- Events Section -->
    <section class="events" id="events">
        <div class="section-header">
            <span class="section-tag">GET IN THE GAME</span>
            <h2 class="section-title">Upcoming <span class="highlight">Events</span></h2>
            <p class="section-subtitle">Register now and secure your spot in the action!</p>
        </div>
        
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
                            <i data-lucide="trophy" style="width: 80px; height: 80px;"></i>
                        </div>
                        <div class="event-content">
                            <h3><?= htmlspecialchars($event['title']) ?></h3>
                            
                            <div class="event-date">
                                <i data-lucide="calendar" style="width: 18px; height: 18px;"></i>
                                <?= date('F j, Y • g:i A', strtotime($event['start_time'])) ?>
                            </div>
                            
                            <?php if ($event['venue_name']): ?>
                                <div class="event-date">
                                    <i data-lucide="map-pin" style="width: 18px; height: 18px;"></i>
                                    <?= htmlspecialchars($event['venue_name']) ?>
                                    <?php if ($event['city']): ?>
                                        , <?= htmlspecialchars($event['city']) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <p><?= htmlspecialchars(substr($event['description'], 0, 120)) ?>...</p>
                            
                            <div class="event-badges-container">
                                <span class="event-info-badge <?= $isLowSeats ? 'seats-low' : '' ?>">
                                    <i data-lucide="users" style="width: 16px; height: 16px;"></i>
                                    <?= $available ?> / <?= $capacity ?> slots
                                </span>
                                <span class="event-info-badge <?= $event['price'] > 0 ? 'price-paid' : 'price-free' ?>">
                                    <i data-lucide="tag" style="width: 16px; height: 16px;"></i>
                                    <?= $event['price'] > 0 ? '₱' . number_format($event['price'], 2) : 'FREE' ?>
                                </span>
                            </div>
                            
                            <a href="../auth/index.php" class="event-link">
                                Register Now
                                <i data-lucide="arrow-right" style="width: 20px; height: 20px;"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-events-container">
                    <i data-lucide="calendar-off" style="width: 100px; height: 100px; color: rgba(139, 0, 0, 0.3);"></i>
                    <h3 style="color: var(--maroon); font-size: 2rem; margin: 1rem 0;">No Events Yet</h3>
                    <p style="color: var(--gray); font-size: 1.1rem; max-width: 500px; margin: 0 auto;">
                        We're cooking up something epic! Check back soon for amazing sports events and tournaments.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>CCF B1G SPORTS</h3>
                <p>Be One with God through sports. We're a dynamic community of Christian athletes dedicated to excellence, unity, and making an eternal impact through competitive sports.</p>
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
                    <a href="#" class="social-link" aria-label="Mail">
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
                    <a href="../auth/index.php">Join Now</a><br>
                    <a href="#contact">Contact</a>
                </p>
            </div>

            <div class="footer-section">
                <h3>Get In Touch</h3>
                <p>
                    <strong>Email:</strong> sports@ccfb1g.ph<br>
                    <strong>Phone:</strong> (02) 8772 3035<br>
                    <strong>Address:</strong> 3F CCF Building<br>
                    Madrigal Business Park<br>
                    Muntinlupa, Metro Manila
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> CCF B1G Sports. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');
        const navOverlay = document.getElementById('navOverlay');
        const navbar = document.getElementById('navbar');

        function toggleMenu() {
            mobileMenuBtn.classList.toggle('active');
            navLinks.classList.toggle('active');
            navOverlay.classList.toggle('active');
            document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
        }

        mobileMenuBtn.addEventListener('click', toggleMenu);
        navOverlay.addEventListener('click', toggleMenu);

        // Close menu when clicking a link
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                if (navLinks.classList.contains('active')) {
                    toggleMenu();
                }
            });
        });

        // Navbar scroll effect
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            if (currentScroll > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            lastScroll = currentScroll;
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const navHeight = navbar.offsetHeight;
                    const targetPosition = target.offsetTop - navHeight;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Active link highlighting
        const sections = document.querySelectorAll('section[id]');
        const navLinksAll = document.querySelectorAll('.nav-links a');

        function updateActiveLink() {
            let current = 'home';
            const scrollPos = window.pageYOffset + 150;

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

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.value-card, .event-card').forEach(el => {
            observer.observe(el);
        });

        // Sports Highlights Carousel
        const highlights = [
            {
                image: '../../assets/highlights/soccer-sport.jpg',
                title: 'Sports Tournament',
                date: 'July 27, 2025',
                attendees: 85
            },
            {
                image: '../../assets/highlights/volleyball-sport.jpg',
                title: 'Volleyball Championship',
                date: 'June 15, 2025',
                attendees: 60
            },
            {
                image: '../../assets/highlights/badminton-sport.jpg',
                title: 'Sports Tournament',
                date: 'July 27, 2025',
                attendees: 85
            },
            {
                image: '../../assets/highlights/pickleball-sport.jpg',
                title: 'Sports Tournament',
                date: 'July 27, 2025',
                attendees: 85
            }
        ];

        let currentHighlightIndex = 0;

        function updateFeaturedHighlight(index) {
            const featured = document.getElementById('featuredHighlight');
            const highlight = highlights[index];

            // Add fade out effect
            featured.style.opacity = '0';

            setTimeout(() => {
                // Update content
                featured.querySelector('.highlight-image').src = highlight.image;
                featured.querySelector('.highlight-image').alt = highlight.title;
                featured.querySelector('.highlight-title').textContent = highlight.title;
                featured.querySelector('.highlight-date').innerHTML = `
                    <i data-lucide="calendar" style="width: 16px; height: 16px;"></i>
                    ${highlight.date}
                `;
                featured.querySelector('.highlight-attendees').innerHTML = `
                    <i data-lucide="users" style="width: 16px; height: 16px;"></i>
                    ${highlight.attendees} Attendees
                `;

                // Reinitialize icons
                lucide.createIcons();

                // Fade in
                featured.style.opacity = '1';
            }, 300);

            // Update active thumbnail
            document.querySelectorAll('.highlight-thumb').forEach((thumb, idx) => {
                thumb.classList.toggle('active', idx === index);
            });

            currentHighlightIndex = index;
        }

        // Thumbnail click handlers
        document.querySelectorAll('.highlight-thumb').forEach(thumb => {
            thumb.addEventListener('click', () => {
                const index = parseInt(thumb.dataset.index);
                updateFeaturedHighlight(index);
            });
        });

        // Navigation arrows
        document.getElementById('prevHighlight').addEventListener('click', () => {
            const newIndex = (currentHighlightIndex - 1 + highlights.length) % highlights.length;
            updateFeaturedHighlight(newIndex);
        });

        document.getElementById('nextHighlight').addEventListener('click', () => {
            const newIndex = (currentHighlightIndex + 1) % highlights.length;
            updateFeaturedHighlight(newIndex);
        });

        // Auto-play carousel (optional)
        let autoplayInterval;

        function startAutoplay() {
            autoplayInterval = setInterval(() => {
                const newIndex = (currentHighlightIndex + 1) % highlights.length;
                updateFeaturedHighlight(newIndex);
            }, 5000); // Change every 5 seconds
        }

        function stopAutoplay() {
            clearInterval(autoplayInterval);
        }

        // Start autoplay on load
        startAutoplay();

        // Pause autoplay when user interacts
        const highlightsContainer = document.querySelector('.sports-highlights');
        highlightsContainer.addEventListener('mouseenter', stopAutoplay);
        highlightsContainer.addEventListener('mouseleave', startAutoplay);
    </script>
</body>
</html>