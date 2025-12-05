<?php
// index.php - Smart Classroom Landing Page
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#F8FAFC">
    <title>Smart Classroom — Attendance & Grade Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global-theme.css" rel="stylesheet">
    <style>
        body {
            transition: background-color 0.3s, color 0.3s;
        }
        .dark body {
            background-color: #0F172A;
            color: #E2E8F0;
        }
        .hero {
            min-height: 90vh;
            background: linear-gradient(135deg, #1561AD 0%, #4D774E 100%);
            color: #fff;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        .dark .hero {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,106.7C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') bottom center no-repeat;
            background-size: cover;
        }
        .hero .btn-cta {
            padding: 14px 32px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .hero .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .feature-card {
            transition: transform 0.3s, background-color 0.3s, box-shadow 0.3s;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        .dark .feature-card {
            background-color: #1E293B;
            color: #E2E8F0;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        .feature-icon {
            font-size: 3rem;
            color: #1561AD;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .dark .feature-icon {
            color: #FBA92C;
        }
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }
        .navbar {
            background-color: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
        }
        .dark .navbar {
            background-color: rgba(30, 41, 59, 0.95) !important;
        }
        .navbar-brand {
            color: #1561AD !important;
        }
        .nav-link {
            color: #1561AD !important;
        }
        .dark .navbar-brand,
        .dark .nav-link {
            color: #FFFFFF !important;
        }
        .dark .bg-light {
            background-color: #0F172A !important;
        }
        .dark .card {
            background-color: #1E293B;
            color: #E2E8F0;
        }
        .dark .text-muted {
            color: #94A3B8 !important;
        }
        .dark footer {
            background-color: #020617 !important;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1561AD;
        }
        .dark .stat-number {
            color: #FBA92C;
        }
        @keyframes bounce-in {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
        .animate-bounce-in {
            animation: bounce-in 0.6s ease-out;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-graduation-cap text-primary"></i> Smart Classroom
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <!-- Theme Toggle Button -->
                        <button 
                            id="themeToggle" 
                            class="btn btn-sm btn-outline-secondary rounded-circle p-2 ms-2"
                            aria-label="Toggle theme"
                            style="width: 40px; height: 40px;"
                        >
                            <i id="themeIcon" class="fas fa-moon"></i>
                        </button>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-primary ms-2" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="register.php">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold mb-4 animate-bounce-in">Smart Classroom Management System</h1>
                    <p class="lead mb-4">QR-based attendance tracking, real-time SMS notifications, comprehensive grade management, and professional analytics for modern educational institutions.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="register.php" class="btn btn-cta btn-light btn-lg">
                            <i class="fas fa-rocket"></i> Get Started Free
                        </a>
                        <a href="#features" class="btn btn-cta btn-outline-light btn-lg">
                            <i class="fas fa-info-circle"></i> Learn More
                        </a>
                    </div>
                    <div class="mt-4">
                        <small class="text-white-50">
                            <i class="fas fa-check-circle"></i> No credit card required
                            <span class="mx-2">•</span>
                            <i class="fas fa-check-circle"></i> Free forever
                        </small>
                    </div>
                </div>
                <div class="col-lg-6 text-center d-none d-lg-block">
                    <div class="position-relative float-animation">
                        <i class="fas fa-qrcode" style="font-size: 18rem; opacity: 0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Powerful Features</h2>
                <p class="lead text-muted">Everything you need to manage your classroom efficiently</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <i class="fas fa-qrcode feature-icon"></i>
                        <h4 class="mb-3">QR Code Attendance</h4>
                        <p class="text-muted">Fast and secure QR code scanning for time-in and time-out with automatic logging and real-time SMS notifications to parents.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <i class="fas fa-chart-line feature-icon"></i>
                        <h4 class="mb-3">Grade Management</h4>
                        <p class="text-muted">Teachers can input grades per subject and quarter. Advisors can monitor student performance and export comprehensive reports.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <i class="fas fa-file-export feature-icon"></i>
                        <h4 class="mb-3">Reports & Analytics</h4>
                        <p class="text-muted">Dynamic charts, export to CSV/PDF, and professional dashboards with real-time insights for teachers and administrators.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <i class="fas fa-mobile-alt feature-icon"></i>
                        <h4 class="mb-3">SMS Notifications</h4>
                        <p class="text-muted">Automatic SMS alerts to parents when students check in or out, with retry mechanism for failed deliveries.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <i class="fas fa-users-cog feature-icon"></i>
                        <h4 class="mb-3">Role-Based Access</h4>
                        <p class="text-muted">Secure access control for Admin, Advisor, Teacher, and Student roles with customized dashboards for each.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <i class="fas fa-shield-alt feature-icon"></i>
                        <h4 class="mb-3">Secure & Reliable</h4>
                        <p class="text-muted">Built with security best practices, encrypted passwords, and comprehensive error logging for peace of mind.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-4 fw-bold mb-4">Why Choose Smart Classroom?</h2>
                    <p class="lead mb-4">Our system is designed to streamline classroom management and improve communication between schools and parents.</p>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Easy to use interface</strong> - Intuitive design for all users
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Real-time tracking</strong> - Instant attendance updates
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Comprehensive reporting</strong> - Detailed analytics and insights
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Mobile-friendly</strong> - Works on all devices
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Secure and reliable</strong> - Enterprise-grade security
                        </li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="card p-4 shadow-lg">
                        <h4 class="mb-4 text-center">Quick Stats</h4>
                        <div class="row text-center">
                            <div class="col-6 mb-4">
                                <div class="stat-number">99.9%</div>
                                <p class="text-muted mb-0">Uptime</p>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="stat-number">24/7</div>
                                <p class="text-muted mb-0">Support</p>
                            </div>
                            <div class="col-6">
                                <div class="stat-number">1000+</div>
                                <p class="text-muted mb-0">Schools</p>
                            </div>
                            <div class="col-6">
                                <div class="stat-number">50K+</div>
                                <p class="text-muted mb-0">Students</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="display-4 fw-bold mb-4">Ready to Get Started?</h2>
            <p class="lead mb-4">Join thousands of schools already using Smart Classroom</p>
            <a href="register.php" class="btn btn-light btn-lg px-5">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <p class="mt-3 mb-0">
                <small>Already have an account? <a href="login.php" class="text-white text-decoration-underline">Login here</a></small>
            </p>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Get In Touch</h2>
                <p class="lead text-muted">Have questions? We're here to help!</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <i class="fas fa-envelope feature-icon"></i>
                        <h5 class="mb-3">Email Us</h5>
                        <p class="text-muted">support@smartclassroom.com</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <i class="fas fa-phone feature-icon"></i>
                        <h5 class="mb-3">Call Us</h5>
                        <p class="text-muted">+1 (555) 123-4567</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 p-4 text-center">
                        <i class="fas fa-map-marker-alt feature-icon"></i>
                        <h5 class="mb-3">Visit Us</h5>
                        <p class="text-muted">123 Education St, City</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h5><i class="fas fa-graduation-cap"></i> Smart Classroom</h5>
                    <p class="text-muted mb-2">Modern classroom management for modern schools</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1">&copy; <?php echo date('Y'); ?> Smart Classroom. All rights reserved.</p>
                    <p class="text-muted mb-0">
                        <a href="#" class="text-muted text-decoration-none">Privacy Policy</a> • 
                        <a href="#" class="text-muted text-decoration-none">Terms of Service</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme-toggle.js"></script>
</body>
</html>
