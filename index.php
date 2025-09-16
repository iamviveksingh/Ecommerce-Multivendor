<?php
session_start();
require_once 'config/database.php';

// Get some basic statistics for the landing page
$total_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetch_assoc()['count'];
$total_vendors = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'vendor'")->fetch_assoc()['count'];
$total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'buyer'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bazario - Premium Shopping Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <style>

        /* Page-specific styles */

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            color: white;
            text-align: center;
            padding: 120px 0 80px;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 4.5rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            text-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 1s ease-out;
            line-height: 1.1;
        }

        .brand-highlight {
            background: linear-gradient(45deg, var(--neon-accent), #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 3s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            color: var(--neon-accent);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease-out 0.2s both;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 3rem;
            animation: fadeInUp 1s ease-out 0.8s both;
        }

        .hero-stats .stat-item {
            text-align: center;
        }

        .hero-stats .stat-number {
            display: block;
            font-size: 2rem;
            font-weight: 800;
            color: var(--neon-accent);
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
        }

        .hero-stats .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
            font-weight: 500;
        }

        .hero-visual {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-card {
            position: absolute;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--neon-accent);
            font-size: 2rem;
            animation: float 6s ease-in-out infinite;
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.2);
        }

        .floating-card.card-1 {
            top: 20%;
            right: 15%;
            animation-delay: 0s;
        }

        .floating-card.card-2 {
            top: 40%;
            right: 5%;
            animation-delay: 1.5s;
        }

        .floating-card.card-3 {
            bottom: 30%;
            right: 20%;
            animation-delay: 3s;
        }

        .floating-card.card-4 {
            top: 60%;
            left: 10%;
            animation-delay: 4.5s;
        }

        .hero .lead {
            font-size: 1.5rem;
            margin-bottom: 3rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        .hero-buttons {
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Hero Buttons */
        .btn-hero {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
            display: inline-block;
        }

        .btn-primary-hero {
            background: var(--neon-accent);
            border: none;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
        }

        .btn-primary-hero:hover {
            background: #00b8e6;
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 212, 255, 0.4);
            color: white;
        }

        .btn-outline-hero {
            background: transparent;
            border: 2px solid white;
            color: white;
        }

        .btn-outline-hero:hover {
            background: white;
            color: var(--gradient-primary);
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 255, 255, 0.3);
        }

        /* Statistics Section */
        .stats-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 3rem 2rem;
            margin: -50px 0 4rem;
            position: relative;
            z-index: 3;
        }

        .stat-item {
            text-align: center;
            color: white;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--neon-accent);
            margin-bottom: 0.5rem;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Features Section */
        .features-section {
            padding: 6rem 0;
            background: rgba(255, 255, 255, 0.02);
            position: relative;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            color: white;
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .section-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.3rem;
            margin-bottom: 0;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .feature-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 3rem 2rem;
            text-align: center;
            color: white;
            transition: all 0.4s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .feature-card:hover::before {
            left: 100%;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            border-color: var(--neon-accent);
            box-shadow: 0 20px 50px rgba(0, 212, 255, 0.2);
        }

        .feature-icon {
            font-size: 4rem;
            color: var(--neon-accent);
            margin-bottom: 2rem;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .feature-description {
            opacity: 0.8;
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            background: var(--gradient-primary);
            padding: 6rem 0;
            text-align: center;
            color: white;
            position: relative;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
        }

        .cta-content {
            position: relative;
            z-index: 2;
        }

        .cta-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .cta-description {
            font-size: 1.4rem;
            margin-bottom: 3rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            margin-bottom: 3rem;
        }

        .cta-features {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }

        .cta-feature {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .cta-feature i {
            color: var(--success-color);
            font-size: 1.2rem;
        }

        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            color: white;
            padding: 3rem 0 2rem;
            border-top: 1px solid var(--glass-border);
        }

        .footer-content {
            text-align: center;
        }

        .footer-links {
            margin-bottom: 2rem;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            margin: 0 1rem;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: var(--neon-accent);
        }

        .social-links {
            margin-bottom: 2rem;
        }

        .social-link {
            display: inline-block;
            width: 50px;
            height: 50px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 50%;
            text-align: center;
            line-height: 50px;
            color: white;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: var(--neon-accent);
            border-color: var(--neon-accent);
            transform: translateY(-3px);
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 3rem;
            }
            
            .hero .lead {
                font-size: 1.2rem;
            }
            
            .btn-hero {
                display: block;
                margin: 0.5rem auto;
                max-width: 250px;
            }
            
            .hero-stats {
                flex-direction: column;
                gap: 1.5rem;
                margin-top: 2rem;
            }
            
            .hero-stats .stat-number {
                font-size: 1.8rem;
            }
            
            .stats-section {
                margin: -30px 1rem 3rem;
                padding: 2rem 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 2.5rem;
            }
            
            .cta-title {
                font-size: 2.5rem;
            }
            
            .cta-features {
                flex-direction: column;
                gap: 1rem;
            }
            
            .floating-card {
                display: none;
            }
        }

        /* Scroll Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg"></div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>Bazario
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="store.php">
                        <i class="fas fa-shopping-bag me-1"></i>Store
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] == 'buyer'): ?>
                            <a class="nav-link" href="buyer/dashboard.php">
                                <i class="fas fa-user me-1"></i>My Account
                            </a>
                        <?php elseif ($_SESSION['role'] == 'vendor'): ?>
                            <a class="nav-link" href="vendor/dashboard.php">
                                <i class="fas fa-store me-1"></i>Vendor
                            </a>
                        <?php elseif ($_SESSION['role'] == 'admin'): ?>
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-cog me-1"></i>Admin
                            </a>
                        <?php endif; ?>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    <?php else: ?>
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-star me-2"></i>Premium Marketplace
                </div>
                <h1>Welcome to <span class="brand-highlight">Bazario</span></h1>
                <p class="lead">Where exceptional products meet extraordinary experiences. Discover, shop, and sell with confidence in our curated marketplace.</p>
                <div class="hero-buttons">
                    <a href="store.php" class="btn btn-primary-hero btn-hero">
                        <i class="fas fa-shopping-cart me-2"></i>Explore Store
                    </a>
                    <a href="register.php" class="btn btn-outline-hero btn-hero">
                        <i class="fas fa-store me-2"></i>Start Selling
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?= number_format($total_products) ?>+</span>
                        <span class="stat-label">Products</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= number_format($total_vendors) ?>+</span>
                        <span class="stat-label">Vendors</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= number_format($total_customers) ?>+</span>
                        <span class="stat-label">Happy Customers</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="floating-card card-1">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="floating-card card-2">
                <i class="fas fa-gem"></i>
            </div>
            <div class="floating-card card-3">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <div class="floating-card card-4">
                <i class="fas fa-heart"></i>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <div class="container">
        <div class="stats-section">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($total_products) ?></div>
                        <div class="stat-label">Products Available</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($total_vendors) ?></div>
                        <div class="stat-label">Trusted Vendors</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($total_customers) ?></div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Why Choose <span class="brand-highlight">Bazario</span>?</h2>
                <p class="section-subtitle">Experience the future of e-commerce with our cutting-edge marketplace platform</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-shield-check"></i>
                        </div>
                        <h3 class="feature-title">Verified Excellence</h3>
                        <p class="feature-description">Every vendor undergoes rigorous verification. We ensure only the highest quality merchants join our marketplace, giving you peace of mind with every purchase.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3 class="feature-title">Lightning Fast</h3>
                        <p class="feature-description">From discovery to delivery, Bazario optimizes every step. Enjoy ultra-fast search, instant checkout, and rapid shipping from our global vendor network.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3 class="feature-title">Bank-Grade Security</h3>
                        <p class="feature-description">Your data and payments are protected by enterprise-level encryption. Shop with confidence knowing your information is always secure.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Community Driven</h3>
                        <p class="feature-description">Join a thriving community of buyers and sellers. Share reviews, discover trends, and connect with like-minded individuals.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <h3 class="feature-title">AI-Powered Discovery</h3>
                        <p class="feature-description">Our intelligent algorithms learn your preferences to surface products you'll love. Discover your next favorite item effortlessly.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h3 class="feature-title">Global Marketplace</h3>
                        <p class="feature-description">Access products from vendors worldwide. From local artisans to international brands, find everything in one place.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Experience <span class="brand-highlight">Bazario</span>?</h2>
                <p class="cta-description">Join our growing community of smart shoppers and innovative sellers. Your next great discovery awaits!</p>
                <div class="cta-buttons">
                    <a href="store.php" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                    </a>
                    <a href="register.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-store me-2"></i>Become a Seller
                    </a>
                </div>
                <div class="cta-features">
                    <div class="cta-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Free to join</span>
                    </div>
                    <div class="cta-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>No setup fees</span>
                    </div>
                    <div class="cta-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>24/7 support</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-links">
                    <a href="store.php" class="footer-link">Store</a>
                    <a href="register.php" class="footer-link">Become a Vendor</a>
                    <a href="login.php" class="footer-link">Login</a>
                    <a href="about.php" class="footer-link">About Us</a>
                    <a href="contact.php" class="footer-link">Contact</a>
                </div>
                
                <div class="social-links">
                    <a href="#" class="social-link">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
                
                <p class="mb-0">&copy; 2025 Bazario. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(0, 0, 0, 0.9)';
                navbar.style.backdropFilter = 'blur(20px)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.1)';
                navbar.style.backdropFilter = 'blur(20px)';
            }
        });

        // Scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Parallax effect for hero section
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            const rate = scrolled * -0.5;
            hero.style.transform = `translateY(${rate}px)`;
        });

        // Add floating animation to feature cards
        document.querySelectorAll('.feature-card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    </script>
</body>
</html>
