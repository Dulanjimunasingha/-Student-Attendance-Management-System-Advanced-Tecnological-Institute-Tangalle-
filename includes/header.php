<?php
/**
 * Header Template
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Attendance Management System for Advanced Technological Institute of Tangalle">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0a58ca;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
            --sidebar-width: 260px;
            --navbar-height: 60px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--dark-bg) 0%, #1a1a2e 100%);
            color: #fff;
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand h4 {
            margin: 0;
            font-weight: 600;
            color: #fff;
        }
        
        .sidebar-brand small {
            color: rgba(255,255,255,0.6);
            font-size: 11px;
        }
        
        .sidebar-menu {
            padding: 15px 0;
        }
        
        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 12px 20px;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .sidebar-menu .nav-link.active {
            background: var(--primary-color);
        }
        
        .sidebar-menu .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        .sidebar-submenu {
            margin-left: 32px;
            font-size: 13px;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        
        .top-navbar {
            background: #fff;
            height: var(--navbar-height);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .navbar-toggle {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--dark-bg);
            cursor: pointer;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .page-header {
            background: #fff;
            padding: 20px 25px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-bg);
        }
        
        .page-header .breadcrumb {
            margin: 5px 0 0 0;
            font-size: 13px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-bg);
        }
        
        .stat-card .stat-label {
            font-size: 13px;
            color: var(--secondary-color);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: var(--light-bg);
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            color: var(--secondary-color);
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 5px 10px;
            font-weight: 500;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            border-radius: 4px;
        }
        
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4>
            <small>Attendance System</small>
        </div>
        
        <nav class="sidebar-menu">
            <ul class="nav flex-column">
                <?php
                $currentPage = basename($_SERVER['PHP_SELF'], '.php');
                $currentDir = basename(dirname($_SERVER['PHP_SELF']));
                ?>
                
                <?php if (hasRole('admin')): ?>
                <!-- Admin Menu -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'index' && $currentDir == 'admin') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin/index.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'students') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin/students.php">
                        <i class="fas fa-user-graduate"></i>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'lecturers') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin/lecturers.php">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Lecturers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'courses') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin/courses.php">
                        <i class="fas fa-book"></i>
                        <span>Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'attendance') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin/attendance.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'reports') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin/reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'announcements') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/admin/announcements.php">
                        <i class="fas fa-bullhorn"></i>
                        <span>Announcements</span>
                    </a>
                </li>
                <?php elseif (hasRole('lecturer')): ?>
                <!-- Lecturer Menu -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'index' && $currentDir == 'lecturer') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/lecturer/index.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'mark_attendance') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/lecturer/mark_attendance.php">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Mark Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'attendance_history') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/lecturer/attendance_history.php">
                        <i class="fas fa-history"></i>
                        <span>Attendance History</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'student_search') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/lecturer/student_search.php">
                        <i class="fas fa-search"></i>
                        <span>Student Search</span>
                    </a>
                </li>
                <?php elseif (hasRole('student')): ?>
                <!-- Student Menu -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'index' && $currentDir == 'student') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/student/index.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'attendance') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/student/attendance.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>My Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'profile') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/student/profile.php">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <button class="navbar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="d-flex align-items-center">
                <div class="user-dropdown dropdown">
                    <div class="d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php 
                            $user = getCurrentUser();
                            echo strtoupper(substr($user['username'], 0, 1));
                            ?>
                        </div>
                        <div class="d-none d-md-block">
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <small class="d-block text-muted"><?php echo htmlspecialchars($user['role_name']); ?></small>
                        </div>
                        <i class="fas fa-chevron-down small text-muted"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Page Content -->
        <div class="page-content">
    <?php endif; ?>
