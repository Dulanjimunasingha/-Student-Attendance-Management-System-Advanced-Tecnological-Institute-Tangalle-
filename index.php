<?php
/**
 * Home Page
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home';

// Get announcements
$announcements = getAnnouncements(3, true);

$heroImage = '';
$heroTitle = 'Advanced Technological Institute of Tangalle';
$heroSubtitle = 'Attendance Management System';

try {
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('hero_image', 'hero_title', 'hero_subtitle')");
    $settings = $stmt->fetchAll();
    foreach ($settings as $s) {
        if ($s['setting_key'] === 'hero_image') $heroImage = $s['setting_value'];
        if ($s['setting_key'] === 'hero_title') $heroTitle = $s['setting_value'];
        if ($s['setting_key'] === 'hero_subtitle') $heroSubtitle = $s['setting_value'];
    }
} catch (Exception $e) {
    // Table doesn't exist, use defaults
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Attendance Management System for Advanced Technological Institute of Tangalle">
    <title><?php echo SITE_NAME; ?> - Attendance Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0a58ca;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --dark-bg: #1a1a2e;
            --light-bg: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--light-bg);
        }
        
        /* Navbar */
        .navbar {
            background: #fff !important;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--dark-bg) !important;
        }
        
        .navbar-brand i {
            color: var(--primary-color);
        }
        
        .navbar-nav .nav-link {
            color: #333;
            font-weight: 500;
            padding: 10px 18px;
            transition: color 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--primary-color);
        }
        
        .navbar-nav .btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        /* Hero Section */
        .hero-section {
            position: relative;
            height: 550px;
            min-height: 400px;
            max-height: 600px;
            overflow: hidden;
        }
        
        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .hero-background img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.85) 0%, rgba(22, 33, 62, 0.75) 50%, rgba(26, 26, 46, 0.85) 100%);
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .hero-content .hero-text {
            max-width: 800px;
        }
        
        .hero-section h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .hero-section .lead {
            font-size: 1.4rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 30px;
            font-weight: 400;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                height: 450px;
                min-height: 400px;
            }
            
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .hero-section .lead {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section {
                height: 400px;
            }
            
            .hero-section h1 {
                font-size: 1.6rem;
            }
            
            .hero-section .lead {
                font-size: 1rem;
            }
        }
        
        /* Login Cards */
        .login-cards {
            margin-top: -80px;
            position: relative;
            z-index: 10;
        }
        
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .login-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
        }
        
        .login-card .icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }
        
        .login-card.admin .icon {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .login-card.lecturer .icon {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .login-card.student .icon {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        
        .login-card h4 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .login-card .btn {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 500;
        }
        
        /* Features Section */
        .features-section {
            padding: 100px 0;
        }
        
        .section-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .section-subtitle {
            color: #666;
            margin-bottom: 50px;
        }
        
        .feature-card {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .feature-icon.blue {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        
        .feature-icon.green {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .feature-icon.purple {
            background: rgba(111, 66, 193, 0.1);
            color: #6f42c1;
        }
        
        .feature-icon.orange {
            background: rgba(253, 126, 20, 0.1);
            color: #fd7e14;
        }
        
        /* Stats Section */
        .stats-section {
            background: var(--dark-bg);
            padding: 80px 0;
        }
        
        .stat-item {
            text-align: center;
            color: #fff;
        }
        
        .stat-value {
            font-family: 'Poppins', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
        }
        
        /* Announcements */
        .announcements-section {
            padding: 80px 0;
            background: #fff;
        }
        
        .announcement-card {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .announcement-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .announcement-card.high {
            border-left-color: #dc3545;
        }
        
        .announcement-card.medium {
            border-left-color: #ffc107;
        }
        
        .announcement-card .date {
            font-size: 12px;
            color: #999;
        }
        
        /* Footer */
        footer {
            background: var(--dark-bg);
            color: #fff;
            padding: 60px 0 30px;
        }
        
        footer h5 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        footer a:hover {
            color: #fff;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 40px;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0;
            }
            
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .login-cards {
                margin-top: 40px;
            }
            
            .stat-value {
                font-size: 2rem;
            }
        }
        
        /* Meet Our Lecturers Section */
        .lecturers-section {
            padding: 100px 0;
            background: linear-gradient(180deg, #f8f9fa 0%, #fff 100%);
        }
        
        .lecturers-section .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .lecturers-section .section-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.5rem;
            color: #1a1a2e;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        
        .lecturers-section .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #0d6efd, #198754);
            border-radius: 2px;
        }
        
        .lecturers-section .section-subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-top: 20px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .lecturer-card {
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .lecturer-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
        }
        
        .lecturer-card .lecturer-image-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 25px;
        }
        
        .lecturer-card .lecturer-image {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.4s ease;
        }
        
        .lecturer-card:hover .lecturer-image {
            transform: scale(1.08);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }
        
        .lecturer-card .lecturer-image-placeholder {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            border: 4px solid #fff;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            font-size: 56px;
            font-weight: 700;
            color: #fff;
            transition: all 0.4s ease;
        }
        
        .lecturer-card:hover .lecturer-image-placeholder {
            transform: scale(1.08);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }
        
        .lecturer-card .lecturer-name {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.25rem;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .lecturer-card .lecturer-role {
            display: inline-block;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(25, 135, 84, 0.1) 100%);
            color: #0d6efd;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 12px;
        }
        
        .lecturer-card .lecturer-department {
            color: #666;
            font-size: 0.9rem;
        }
        
        .lecturer-card .lecturer-social {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .lecturer-card .lecturer-social a {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .lecturer-card .lecturer-social a:hover {
            background: #0d6efd;
            color: #fff;
            transform: translateY(-3px);
        }
        
        @media (max-width: 992px) {
            .lecturers-section {
                padding: 60px 0;
            }
            
            .lecturers-section .section-title {
                font-size: 2rem;
            }
            
            .lecturer-card {
                padding: 25px;
            }
            
            .lecturer-card .lecturer-image,
            .lecturer-card .lecturer-image-placeholder {
                width: 130px;
                height: 130px;
                font-size: 46px;
            }
        }
        
        @media (max-width: 576px) {
            .lecturers-section .section-title {
                font-size: 1.75rem;
            }
        }
        
        /* Student Profile Images */
        .student-profile-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .student-profile-image-sm {
            width: 40px;
            height: 40px;
        }
        
        .student-profile-image-lg {
            width: 120px;
            height: 120px;
            border: 4px solid #fff;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .student-profile-image-xl {
            width: 160px;
            height: 160px;
            border: 5px solid #fff;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }
        
        .profile-image-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 18px;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .profile-image-placeholder-sm {
            width: 40px;
            height: 40px;
            font-size: 14px;
        }
        
        .profile-image-placeholder-lg {
            width: 120px;
            height: 120px;
            font-size: 42px;
            border: 4px solid #fff;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .profile-image-placeholder-xl {
            width: 160px;
            height: 160px;
            font-size: 56px;
            border: 5px solid #fff;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/">
                <i class="fas fa-graduation-cap me-2"></i>ATIT
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#lecturers">Our Lecturers</a></li>
                    <li class="nav-item"><a class="nav-link" href="#announcements">Announcements</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item ms-3">
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="hero-background">
            <img src="<?php echo BASE_URL; ?>/assets/images/download (1).jpg" alt="ATIT Campus" onerror="this.style.display='none'">
        </div>
        <div class="hero-overlay"></div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Advanced Technological Institute of Tangalle</h1>
                    <p class="lead">Smart Attendance Management System</p>
                    <div class="mt-4">
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login Now
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Login Cards -->
    <section class="login-cards">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="login-card admin">
                        <div class="icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h4>Admin Portal</h4>
                        <p>Manage students, lecturers, courses, and view comprehensive attendance reports.</p>
                        <a href="<?php echo BASE_URL; ?>/auth/login.php?role=admin" class="btn btn-danger">
                            Admin Login
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="login-card lecturer">
                        <div class="icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h4>Lecturer Portal</h4>
                        <p>Mark attendance, record lessons, and track student performance in your courses.</p>
                        <a href="<?php echo BASE_URL; ?>/auth/login.php?role=lecturer" class="btn btn-success">
                            Lecturer Login
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="login-card student">
                        <div class="icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h4>Student Portal</h4>
                        <p>View your attendance records, track course progress, and check requirements.</p>
                        <a href="<?php echo BASE_URL; ?>/auth/login.php?role=student" class="btn btn-primary">
                            Student Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">System Features</h2>
                <p class="section-subtitle">Everything you need to manage institute attendance effectively</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>User Management</h5>
                        <p>Complete management of students, lecturers, and administrators with role-based access.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon green">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h5>Attendance Tracking</h5>
                        <p>Mark attendance with multiple status options: Present, Absent, Late, and Excused.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon purple">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h5>Analytics & Reports</h5>
                        <p>Visual analytics with Chart.js, plus PDF and Excel export for comprehensive reporting.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon orange">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h5>Notifications</h5>
                        <p>Announcements and alerts for students and staff to stay informed about important updates.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-value"><?php 
                            $db = getDB();
                            $stmt = $db->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
                            echo $stmt->fetch()['count'];
                        ?></div>
                        <div class="stat-label">Active Students</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-value"><?php 
                            $stmt = $db->query("SELECT COUNT(*) as count FROM lecturers WHERE status = 'active'");
                            echo $stmt->fetch()['count'];
                        ?></div>
                        <div class="stat-label">Lecturers</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-value"><?php 
                            $stmt = $db->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
                            echo $stmt->fetch()['count'];
                        ?></div>
                        <div class="stat-label">Active Courses</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-value"><?php 
                            $stmt = $db->query("SELECT COUNT(*) as count FROM attendance_records");
                            echo $stmt->fetch()['count'];
                        ?></div>
                        <div class="stat-label">Attendance Records</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Meet Our Lecturers Section -->
    <?php 
    $lecturers_stmt = $db->query("SELECT * FROM lecturers WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
    $homeLecturers = $lecturers_stmt->fetchAll();
    ?>
    <?php if (!empty($homeLecturers)): ?>
    <section class="lecturers-section" id="lecturers">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Meet Our Lecturers</h2>
                <p class="section-subtitle">Our dedicated team of experienced educators committed to shaping the future of technology and innovation.</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($homeLecturers as $lecturer): 
                    $lecturerImage = $lecturer['image_path'] ?? '';
                    $imageFullPath = !empty($lecturerImage) ? __DIR__ . '/' . ltrim($lecturerImage, '/') : '';
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="lecturer-card">
                        <div class="lecturer-image-wrapper">
                            <?php if (!empty($lecturerImage) && !empty($imageFullPath) && file_exists($imageFullPath)): ?>
                            <img src="<?php echo BASE_URL . '/' . ltrim($lecturerImage, '/'); ?>" alt="<?php echo htmlspecialchars($lecturer['full_name']); ?>" class="lecturer-image">
                            <?php else: ?>
                            <div class="lecturer-image-placeholder"><?php echo strtoupper(substr($lecturer['full_name'], 0, 1)); ?></div>
                            <?php endif; ?>
                        </div>
                        <h4 class="lecturer-name"><?php echo htmlspecialchars($lecturer['full_name']); ?></h4>
                        <span class="lecturer-role"><?php echo htmlspecialchars($lecturer['qualification'] ?: 'Lecturer'); ?></span>
                        <p class="lecturer-department"><?php echo htmlspecialchars($lecturer['department']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="<?php echo BASE_URL; ?>/auth/login.php?role=lecturer" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Lecturer Login
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Announcements Section -->
    <section class="announcements-section" id="announcements">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Latest Announcements</h2>
                <p class="section-subtitle">Stay updated with the latest news and notices</p>
            </div>
            
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <?php if (!empty($announcements)): ?>
                        <?php foreach ($announcements as $ann): ?>
                        <div class="announcement-card <?php echo $ann['priority']; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0"><?php echo htmlspecialchars($ann['title']); ?></h5>
                                <?php if ($ann['is_pinned']): ?>
                                <span class="badge bg-primary">Pinned</span>
                                <?php endif; ?>
                            </div>
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars(substr($ann['content'], 0, 200))); ?>...</p>
                            <div class="date">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo formatDate($ann['publish_date']); ?>
                                <?php if ($ann['expiry_date']): ?>
                                - Expires: <?php echo formatDate($ann['expiry_date']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-bullhorn fa-3x mb-3"></i>
                            <p>No announcements at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5><i class="fas fa-graduation-cap me-2"></i>ATIT Attendance</h5>
                    <p class="opacity-75">Advanced Technological Institute of Tangalle - Attendance Management System. Streamlining education through technology.</p>
                </div>
                <div class="col-lg-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled opacity-75">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>Advanced Technological Institute of Tangalle</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i>+94 47 123 4567</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i>info@atit.edu.lk</li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled opacity-75">
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/auth/login.php">Login Portal</a></li>
                        <li class="mb-2"><a href="#features">System Features</a></li>
                        <li class="mb-2"><a href="#announcements">Announcements</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom text-center">
                <p class="mb-0 opacity-75">&copy; <?php echo date('Y'); ?> Advanced Technological Institute of Tangalle. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>
