<?php
/**
 * Admin Dashboard
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Ensure all required tables exist
$db = getDB();
ensureAuthSchema($db);
ensureStudentsTable($db);

$pageTitle = 'Admin Dashboard';

// Get statistics
$db = getDB();

// Safe defaults (allow dashboard to render even if schema is partially imported)
$totalStudents = 0;
$totalLecturers = 0;
$totalCourses = 0;
$totalSessions = 0;
$attendanceStats = [];

try {
    // Total counts
    $totalStudents = (int)$db->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'")->fetch()['count'];
    $totalLecturers = (int)$db->query("SELECT COUNT(*) as count FROM lecturers WHERE status = 'active'")->fetch()['count'];
    $totalCourses = (int)$db->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")->fetch()['count'];
    $totalSessions = (int)$db->query("SELECT COUNT(*) as count FROM attendance_sessions")->fetch()['count'];

    // Attendance stats
    $attendanceStats = $db->query("
        SELECT attendance_status, COUNT(*) as count 
        FROM attendance_records 
        GROUP BY attendance_status
    ")->fetchAll();
} catch (PDOException $e) {
    // Keep defaults when tables are missing.
}

$presentCount = 0;
$absentCount = 0;
$lateCount = 0;
$excusedCount = 0;

foreach ($attendanceStats as $stat) {
    switch ($stat['attendance_status']) {
        case 'present': $presentCount = $stat['count']; break;
        case 'absent': $absentCount = $stat['count']; break;
        case 'late': $lateCount = $stat['count']; break;
        case 'excused': $excusedCount = $stat['count']; break;
    }
}

$totalAttendance = $presentCount + $absentCount + $lateCount + $excusedCount;
$overallPercentage = $totalAttendance > 0 ? round(($presentCount + $lateCount * 0.5 + $excusedCount) / $totalAttendance * 100, 1) : 0;

// Low attendance students (below 75%)
$lowAttendanceStudents = [];

// Recent attendance
$recentAttendance = [];

try {
    $lowAttendanceStudents = $db->query("
        SELECT s.student_id, s.full_name, s.batch,
               COUNT(ar.id) as total,
               SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
               ROUND((SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as percentage
        FROM students s
        JOIN attendance_records ar ON s.id = ar.student_id
        GROUP BY s.id
        HAVING percentage < 75
        ORDER BY percentage ASC
        LIMIT 5
    ")->fetchAll();

    $recentAttendance = $db->query("
        SELECT ar.*, s.student_id, s.full_name, c.course_name, ass.session_date
        FROM attendance_records ar
        JOIN students s ON ar.student_id = s.id
        JOIN attendance_sessions ass ON ar.session_id = ass.id
        JOIN courses c ON ass.course_id = c.id
        ORDER BY ar.marked_at DESC
        LIMIT 10
    ")->fetchAll();
} catch (PDOException $e) {
    // Keep empty arrays when tables are missing.
}

// Recent activities
$recentActivities = getRecentActivities(8);

// Course attendance overview
$courseStats = [];
try {
    $courseStats = $db->query("
        SELECT c.course_name, c.course_code,
               COUNT(DISTINCT ar.student_id) as students_count,
               COUNT(ar.id) as total_records,
               SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present
        FROM courses c
        LEFT JOIN attendance_sessions ass ON c.id = ass.course_id
        LEFT JOIN attendance_records ar ON ass.id = ar.session_id
        WHERE c.status = 'active'
        GROUP BY c.id
        ORDER BY students_count DESC
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    // Keep empty array when tables are missing.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0a58ca;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --dark-bg: #212529;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f6fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand h4 {
            color: #fff;
            font-weight: 700;
            margin: 0;
        }
        
        .sidebar-brand small {
            color: rgba(255,255,255,0.6);
            font-size: 11px;
        }
        
        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 14px 20px;
            margin: 3px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .sidebar-menu .nav-link.active {
            background: var(--primary-color);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        
        .top-navbar {
            background: #fff;
            padding: 15px 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
        
        .page-header {
            padding: 25px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
        
        .stat-card .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 18px 25px;
            font-weight: 600;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: #6c757d;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, #0a58ca 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .badge-attendance {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4>
            <small>Attendance System</small>
        </div>
        
        <nav class="sidebar-menu mt-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="students.php">
                        <i class="fas fa-user-graduate me-2"></i>Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="lecturers.php">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Lecturers
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link" href="courses.php"><i class="fas fa-book me-2"></i>Courses</a></li>
                <li class="nav-item"><a class="nav-link" href="subjects.php"><i class="fas fa-book-open me-2"></i>Subjects</a></li>
                <li class="nav-item">
                    <a class="nav-link" href="attendance.php">
                        <i class="fas fa-calendar-check me-2"></i>Attendance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="announcements.php">
                        <i class="fas fa-bullhorn me-2"></i>Announcements
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
                <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted"><i class="fas fa-calendar me-1"></i> <?php echo date('F d, Y'); ?></span>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
            </div>
        </nav>
        
        <!-- Page Content -->
        <div class="page-content p-4">
            <!-- Flash Message -->
            <?php 
            $flash = getFlash();
            if ($flash): 
            ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?php echo $totalStudents; ?></div>
                                <div class="stat-label">Total Students</div>
                            </div>
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="students.php" class="text-primary small">View All <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?php echo $totalLecturers; ?></div>
                                <div class="stat-label">Total Lecturers</div>
                            </div>
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="lecturers.php" class="text-success small">View All <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?php echo $totalCourses; ?></div>
                                <div class="stat-label">Active Courses</div>
                            </div>
                            <div class="stat-icon bg-info bg-opacity-10 text-info">
                                <i class="fas fa-book"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="courses.php" class="text-info small">View All <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?php echo $totalSessions; ?></div>
                                <div class="stat-label">Attendance Sessions</div>
                            </div>
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="attendance.php" class="text-warning small">View All <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-chart-pie me-2"></i>Attendance Overview</span>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-percentage me-2"></i>Attendance Rate
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div class="text-center">
                                <div class="position-relative d-inline-block">
                                    <canvas id="percentageChart" width="180" height="180"></canvas>
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <div class="stat-value text-primary"><?php echo $overallPercentage; ?>%</div>
                                        <small class="text-muted">Overall</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tables Row -->
            <div class="row g-4">
                <!-- Low Attendance Students -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Low Attendance Students</span>
                            <a href="reports.php?type=low_attendance" class="btn btn-sm btn-outline-danger">View Report</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($lowAttendanceStudents)): ?>
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Attendance</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lowAttendanceStudents as $student): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($student['student_id']); ?></code></td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height: 6px; min-width: 60px;">
                                                        <div class="progress-bar bg-danger" style="width: <?php echo $student['percentage']; ?>%"></div>
                                                    </div>
                                                    <span class="text-danger fw-bold"><?php echo $student['percentage']; ?>%</span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-danger">At Risk</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <p class="text-muted">All students have good attendance!</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-history me-2"></i>Recent Activity
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($recentActivities)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentActivities as $activity): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($activity['action']); ?></p>
                                        </div>
                                        <small class="text-muted"><?php echo formatDate($activity['created_at'], 'h:i A'); ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No recent activity</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Course Stats -->
            <div class="row g-4 mt-2">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-book me-2"></i>Course Attendance Summary
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Course Code</th>
                                            <th>Course Name</th>
                                            <th>Students</th>
                                            <th>Total Records</th>
                                            <th>Present</th>
                                            <th>Attendance %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courseStats as $course): 
                                            $percentage = $course['total_records'] > 0 ? round($course['present'] / $course['total_records'] * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($course['course_code']); ?></code></td>
                                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                            <td><?php echo $course['students_count']; ?></td>
                                            <td><?php echo $course['total_records']; ?></td>
                                            <td><?php echo $course['present']; ?></td>
                                            <td>
                                                <span class="badge-attendance <?php echo $percentage >= 75 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger'); ?>">
                                                    <?php echo $percentage; ?>%
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Attendance Pie Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'bar',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Excused'],
                datasets: [{
                    label: 'Attendance Records',
                    data: [<?php echo $presentCount; ?>, <?php echo $absentCount; ?>, <?php echo $lateCount; ?>, <?php echo $excusedCount; ?>],
                    backgroundColor: [
                        'rgba(25, 135, 84, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(13, 202, 240, 0.8)'
                    ],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Percentage Doughnut Chart
        const percentageCtx = document.getElementById('percentageChart').getContext('2d');
        new Chart(percentageCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [<?php echo $overallPercentage; ?>, <?php echo 100 - $overallPercentage; ?>],
                    backgroundColor: [
                        '#0d6efd',
                        '#e9ecef'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '75%',
                responsive: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });
    </script>
</body>
</html>
