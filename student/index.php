<?php
/**
 * Student Dashboard
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireStudentLogin();

$pageTitle = 'Student Dashboard';
$db = getDB();

// Get student data from session
$student = [
    'id' => $_SESSION['student_id'],
    'student_id' => $_SESSION['student_number'],
    'full_name' => $_SESSION['student_name'],
    'email' => $_SESSION['student_email'] ?? '',
    'image_path' => $_SESSION['student_image'] ?? '',
    'batch' => ''
];

$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['student_id']]);
$studentData = $stmt->fetch();
if ($studentData) {
    $student = array_merge($student, $studentData);
}

if (!$student || !$student['id']) {
    setFlash('error', 'Student profile not found');
    redirect(BASE_URL . '/auth/logout.php');
}

$studentId = $student['id'];

// Get enrolled courses with attendance
$query = "
    SELECT c.*, 
           COUNT(ar.id) as total,
           SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
           SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
           SUM(CASE WHEN ar.attendance_status = 'late' THEN 1 ELSE 0 END) as late,
           SUM(CASE WHEN ar.attendance_status = 'excused' THEN 1 ELSE 0 END) as excused,
           ROUND((SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as percentage
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN attendance_sessions ass ON c.id = ass.course_id
    LEFT JOIN attendance_records ar ON ass.id = ar.session_id AND ar.student_id = ?
    WHERE e.student_id = ? AND c.status = 'active'
    GROUP BY c.id
";
$stmt = $db->prepare($query);
$stmt->execute([$studentId, $studentId]);
$courses = $stmt->fetchAll();

// Overall stats
$totalSessions = 0;
$totalPresent = 0;
$totalAbsent = 0;
$totalLate = 0;
$totalExcused = 0;
$atRisk = 0;

foreach ($courses as $course) {
    $totalSessions += $course['total'];
    $totalPresent += $course['present'];
    $totalAbsent += $course['absent'];
    $totalLate += $course['late'];
    $totalExcused += $course['excused'];
    if ($course['percentage'] < $course['min_attendance_percentage']) {
        $atRisk++;
    }
}

$overallPercentage = $totalSessions > 0 ? round(($totalPresent + $totalLate * 0.5 + $totalExcused) / $totalSessions * 100, 1) : 0;

// Recent announcements
$announcements = getAnnouncements(3, true);

// Recent attendance
$query = "
    SELECT ar.*, c.course_code, c.course_name, ass.session_date, ass.lesson_topic
    FROM attendance_records ar
    JOIN attendance_sessions ass ON ar.session_id = ass.id
    JOIN courses c ON ass.course_id = c.id
    WHERE ar.student_id = ?
    ORDER BY ass.session_date DESC
    LIMIT 5
";
$stmt = $db->prepare($query);
$stmt->execute([$studentId]);
$recentAttendance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0d6efd; --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background: #f5f6fa; }
        .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: #fff; z-index: 1000; }
        .sidebar-brand { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand h4 { color: #fff; font-weight: 700; margin: 0; }
        .sidebar-menu .nav-link { color: rgba(255,255,255,0.7); padding: 14px 20px; margin: 3px 10px; border-radius: 8px; transition: all 0.3s; }
        .sidebar-menu .nav-link:hover, .sidebar-menu .nav-link.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar-menu .nav-link.active { background: var(--primary); }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .top-navbar { background: #fff; padding: 15px 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
        .page-header { padding: 25px; background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%); color: #fff; margin: 20px 25px; border-radius: 12px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .card-header { background: #fff; border-bottom: 1px solid #eee; padding: 18px 25px; }
        .table thead th { background: #f8f9fa; border-bottom: 2px solid #dee2e6; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        
        /* Modern Profile Image Styles */
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .user-avatar-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fff 0%, #e0e0e0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--primary);
            font-size: 16px;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand"><h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4><small>Student Portal</small></div>
        <nav class="sidebar-menu mt-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-calendar-check me-2"></i>My Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
    </aside>
    
    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div><h4 class="mb-0"><?php echo $pageTitle; ?></h4></div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted"><i class="fas fa-calendar me-1"></i><?php echo date('F d, Y'); ?></span>
                <?php 
                $studentImage = $student['image_path'] ?? '';
                $imageFullPath = !empty($studentImage) ? __DIR__ . '/../' . ltrim($studentImage, '/') : '';
                if (!empty($studentImage) && !empty($imageFullPath) && file_exists($imageFullPath)): 
                ?>
                <img src="<?php echo BASE_URL . '/' . ltrim($studentImage, '/'); ?>" alt="" class="user-avatar">
                <?php else: ?>
                <div class="user-avatar-placeholder"><?php echo strtoupper(substr($student['full_name'], 0, 1)); ?></div>
                <?php endif; ?>
            </div>
        </nav>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mx-4 mt-4"><?php echo htmlspecialchars($flash['message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <!-- Welcome Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Welcome, <?php echo htmlspecialchars($student['full_name']); ?>!</h4>
                    <p class="mb-0 opacity-75"><i class="fas fa-id-card me-2"></i><?php echo htmlspecialchars($student['student_id']); ?> | Batch <?php echo htmlspecialchars($student['batch']); ?></p>
                </div>
                <div class="text-end">
                    <div class="h3 mb-0"><?php echo $overallPercentage; ?>%</div>
                    <small class="opacity-75">Overall Attendance</small>
                </div>
            </div>
        </div>
        
        <div class="px-4 mb-4">
            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-success mb-1"><?php echo $totalPresent; ?></h3>
                        <small class="text-muted">Present</small>
                    </div></div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-danger mb-1"><?php echo $totalAbsent; ?></h3>
                        <small class="text-muted">Absent</small>
                    </div></div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-warning mb-1"><?php echo $totalLate; ?></h3>
                        <small class="text-muted">Late</small>
                    </div></div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="<?php echo $atRisk > 0 ? 'text-danger' : 'text-success'; ?> mb-1"><?php echo $atRisk; ?></h3>
                        <small class="text-muted">Courses At Risk</small>
                    </div></div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Course Attendance -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between"><span><i class="fas fa-book me-2"></i>My Courses</span><a href="attendance.php" class="btn btn-sm btn-primary">View Details</a></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead><tr><th>Course</th><th>Classes</th><th>Present</th><th>Percentage</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($course['course_code']); ?><br><small class="text-muted"><?php echo htmlspecialchars($course['course_name']); ?></small></td>
                                            <td><?php echo $course['total']; ?></td>
                                            <td><?php echo $course['present']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height: 6px; min-width: 60px;">
                                                        <div class="progress-bar bg-<?php echo $course['percentage'] >= $course['min_attendance_percentage'] ? 'success' : 'danger'; ?>" style="width: <?php echo min(100, $course['percentage']); ?>%"></div>
                                                    </div>
                                                    <strong><?php echo $course['percentage']; ?>%</strong>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-<?php echo $course['percentage'] >= $course['min_attendance_percentage'] ? 'success' : 'danger'; ?>"><?php echo $course['percentage'] >= $course['min_attendance_percentage'] ? 'Completed' : 'At Risk'; ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Announcements -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><i class="fas fa-bullhorn me-2"></i>Announcements</div>
                        <div class="card-body p-0">
                            <?php if (empty($announcements)): ?>
                            <div class="text-center py-4"><p class="text-muted mb-0">No announcements</p></div>
                            <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($announcements as $ann): ?>
                                <div class="list-group-item">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($ann['title']); ?></h6>
                                    <p class="mb-1 small text-muted"><?php echo nl2br(htmlspecialchars(substr($ann['content'], 0, 100))); ?>...</p>
                                    <small class="text-muted"><?php echo formatDate($ann['publish_date']); ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Attendance -->
            <div class="card mt-4">
                <div class="card-header"><i class="fas fa-history me-2"></i>Recent Attendance</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Date</th><th>Course</th><th>Topic</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (empty($recentAttendance)): ?>
                                <tr><td colspan="4" class="text-center py-4"><p class="text-muted mb-0">No attendance records yet</p></td></tr>
                                <?php else: ?>
                                <?php foreach ($recentAttendance as $record): ?>
                                <tr>
                                    <td><?php echo formatDate($record['session_date']); ?></td>
                                    <td><?php echo htmlspecialchars($record['course_code']); ?></td>
                                    <td><small><?php echo htmlspecialchars(substr($record['lesson_topic'] ?? 'N/A', 0, 30)); ?></small></td>
                                    <td><span class="badge bg-<?php echo $record['attendance_status'] === 'present' ? 'success' : ($record['attendance_status'] === 'absent' ? 'danger' : ($record['attendance_status'] === 'late' ? 'warning' : 'info')); ?>"><?php echo ucfirst($record['attendance_status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</body>
</html>
