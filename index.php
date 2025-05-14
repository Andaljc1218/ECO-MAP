<?php
session_start();
require_once "config/database.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoMap - Environmental Waste Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <!-- Hero Section -->
        <section class="hero-section text-center d-flex align-items-center justify-content-center" style="min-height: 80vh; background: linear-gradient(rgba(34,40,49,0.7),rgba(34,40,49,0.7)), url('assets/images/hero-bg.jpg') center/cover no-repeat;">
            <div class="container d-flex flex-column align-items-center justify-content-center">
                <div class="glass-card p-5 mb-4" style="max-width: 600px;">
                    <h1 class="display-3 fw-bold text-white mb-3">Welcome to <span style="color:var(--eco-green)">EcoMap</span></h1>
                    <p class="lead text-white mb-4">Making waste management smarter and more efficient for our community.</p>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-success btn-lg me-2 glow-btn">Get Started</a>
                        <a href="#features" class="btn btn-outline-success btn-lg glow-btn">Learn More</a>
                    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="btn btn-success btn-lg me-2 glow-btn">Go to Dashboard</a>
                        <a href="#features" class="btn btn-outline-success btn-lg glow-btn">Explore Features</a>
                    <?php else: ?>
                        <a href="profile.php" class="btn btn-success btn-lg me-2 glow-btn">My Profile</a>
                        <a href="#features" class="btn btn-outline-success btn-lg glow-btn">Explore Features</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Wavy Divider -->
        <svg class="section-divider" viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="var(--light-gray)" d="M0,0 C480,60 960,0 1440,60 L1440,60 L0,60 Z"></path></svg>

        <!-- Mission/About Section -->
        <section class="py-5 bg-white">
            <div class="container text-center">
                <h2 class="section-heading mb-3">Our Mission</h2>
                <p class="fs-5 mx-auto" style="max-width: 700px;">To empower communities with technology for sustainable waste management, education, and safety. EcoMap brings together smart scheduling, real-time tracking, and educational resources to make a cleaner, greener future possible for everyone.</p>
            </div>
        </section>

        <!-- Features Overview Section -->
        <section id="features" class="py-5 bg-light">
            <div class="container">
                <h2 class="section-heading text-center mb-5">Features Overview</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-md-4 col-lg-3 fade-in">
                        <div class="card h-100 text-center p-3 glass-card">
                            <div class="icon-bg"><i class="bi bi-book"></i></div>
                            <h5>Educational Resources</h5>
                            <p class="mb-0">Access guides, presentations, and videos about waste management.</p>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3 fade-in">
                        <div class="card h-100 text-center p-3 glass-card">
                            <div class="icon-bg" style="background: linear-gradient(135deg, var(--sky-blue) 60%, var(--eco-green) 100%);"><i class="bi bi-calendar-event"></i></div>
                            <h5>Pickup Schedule</h5>
                            <p class="mb-0">View and search for upcoming waste collection times in your area.</p>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3 fade-in">
                        <div class="card h-100 text-center p-3 glass-card">
                            <div class="icon-bg"><i class="bi bi-geo-alt"></i></div>
                            <h5>Pickup Points</h5>
                            <p class="mb-0">Find and suggest waste drop-off locations on an interactive map.</p>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3 fade-in">
                        <div class="card h-100 text-center p-3 glass-card">
                            <div class="icon-bg" style="background: linear-gradient(135deg, var(--sun-yellow) 60%, var(--eco-green) 100%);"><i class="bi bi-robot"></i></div>
                            <h5>Ecobot</h5>
                            <p class="mb-0">Get instant answers to your waste management questions with our AI chatbot.</p>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3 fade-in">
                        <div class="card h-100 text-center p-3 glass-card">
                            <div class="icon-bg" style="background: linear-gradient(135deg, var(--sky-blue) 60%, var(--sun-yellow) 100%);"><i class="bi bi-geo"></i></div>
                            <h5>Life360 Integration</h5>
                            <p class="mb-0">Stay safe and connected with real-time location sharing for drivers and users.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Wavy Divider -->
        <svg class="section-divider" viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="var(--white)" d="M0,0 C480,60 960,0 1440,60 L1440,60 L0,60 Z"></path></svg>

        <!-- Map Section -->
        <div id="map" class="map-container"></div>

        <!-- Footer -->
        <footer class="bg-dark text-light py-4">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <h5>EcoMap</h5>
                        <p>Making waste management smarter and more efficient.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5>Contact Us</h5>
                        <p>Email: info@ecomap.com<br>Phone: (123) 456-7890</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/main.js"></script>
    <style>
    body {
        min-height: 100vh;
        position: relative;
        padding-bottom: 120px;
    }
    footer.fixed-footer {
        position: fixed;
        left: 0;
        bottom: 0;
        width: 100%;
        z-index: 100;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
    }
    </style>
</body>
</html> 