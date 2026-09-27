<?php
/**
 * Student Attendance Details
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireStudentLogin();

$pageTitle = 'My Attendance';
$db = getDB();
 
// Get student from session
$student = [
    'id' => $_SESSION['student_id'],
    'student_id' => $_SESSION['student_number'],
    'full_name' => $_SESSION['student_name']
];

$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['student_id']]);
$studentData = $stmt->fetch();
if ($studentData) {
    $student = array_merge($student, $studentData);
}
$studentId = $student['id'];

// Get courses with detailed attendance
$query = "
    SELECT c.*, 
           COUNT(ar.id) as total,
           SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
           SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
           SUM(CASE WHEN ar.attendance_status = 'late' THEN 1 ELSE 0 END) as late,
           SUM(CASE WHEN ar.attendance_status = 'excused' THEN 1 ELSE 0 END) as excused
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

// Calculate percentages
foreach ($courses as &$course) {
    $course['percentage'] = $course['total'] > 0 ? round(($course['present'] + $course['late'] * 0.5 + $course['excused']) / $course['total'] * 100, 1) : 0;
}
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
        .page-header { padding: 25px; background: #fff; margin: 20px 25px; border-radius: 12px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .card-header { background: #fff; border-bottom: 1px solid #eee; padding: 18px 25px; }
        .table thead th { background: #f8f9fa; border-bottom: 2px solid #dee2e6; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .progress { height: 10px; border-radius: 5px; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand"><h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4><small>Student Portal</small></div>
        <nav class="sidebar-menu mt-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="attendance.php"><i class="fas fa-calendar-check me-2"></i>My Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
    </aside>
    
    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div><h4 class="mb-0"><?php echo $pageTitle; ?></h4></div>
            <div class="user-avatar"><?php echo strtoupper(substr($student['full_name'], 0, 1)); ?></div>
        </nav>
        
        <div class="page-header">
            <h5 class="mb-1"><i class="fas fa-chart-line me-2"></i>Course-wise Attendance Details</h5>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($student['student_id']); ?> - <?php echo htmlspecialchars($student['full_name']); ?></p>
        </div>
        
        <div class="px-4 mb-4">
            <?php foreach ($courses as $course): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?php echo htmlspecialchars($course['course_code']); ?></strong> - <?php echo htmlspecialchars($course['course_name']); ?>
                        <br><small class="text-muted">Minimum Required: <?php echo $course['min_attendance_percentage']; ?>%</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-<?php echo $course['percentage'] >= $course['min_attendance_percentage'] ? 'success' : 'danger'; ?> fs-6">
                            <?php echo $course['percentage']; ?>%
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Attendance Progress</span>
                            <span class="fw-bold"><?php echo $course['percentage']; ?>%</span>
                        </div>
                        <div class="progress" style="height: 15px;">
                            <div class="progress-bar bg-<?php echo $course['percentage'] >= $course['min_attendance_percentage'] ? 'success' : 'danger'; ?>" 
                                 style="width: <?php echo min(100, $course['percentage']); ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- Stats Row -->
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-2">
                            <div class="p-3 bg-light rounded">
                                <h4 class="mb-0 text-primary"><?php echo $course['total']; ?></h4>
                                <small class="text-muted">Total Classes</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="p-3 bg-success bg-opacity-10 rounded">
                                <h4 class="mb-0 text-success"><?php echo $course['present']; ?></h4>
                                <small class="text-muted">Present</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="p-3 bg-danger bg-opacity-10 rounded">
                                <h4 class="mb-0 text-danger"><?php echo $course['absent']; ?></h4>
                                <small class="text-muted">Absent</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="p-3 bg-warning bg-opacity-10 rounded">
                                <h4 class="mb-0 text-warning"><?php echo $course['late']; ?></h4>
                                <small class="text-muted">Late</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="p-3 bg-info bg-opacity-10 rounded">
                                <h4 class="mb-0 text-info"><?php echo $course['excused']; ?></h4>
                                <small class="text-muted">Excused</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="p-3 bg-<?php echo $course['percentage'] >= $course['min_attendance_percentage'] ? 'success' : 'danger'; ?> bg-opacity-10 rounded">
                                <h4 class="mb-0 text-<?php echo $course['percentage'] >= $course['min_attendance_percentage'] ? 'success' : 'danger'; ?>">
                                    <?php echo $course['percentage'] >= $course['min_attendance_percentage'] ? '✓' : '✗'; ?>
                                </h4>
                                <small class="text-muted"><?php echo $course['percentage'] >= $course['min_attendance_percentage'] ? 'Eligible' : 'At Risk'; ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($course['percentage'] < $course['min_attendance_percentage']): ?>
                    <div class="alert alert-danger mt-3 mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Your attendance in this course is below the required minimum (<?php echo $course['min_attendance_percentage']; ?>%). 
                        You need <?php echo round($course['min_attendance_percentage'] - $course['percentage'], 1); ?>% more to meet the requirement.
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Great!</strong> Your attendance meets the required minimum. Keep it up!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($courses)): ?>
            <div class="card text-center py-5">
                <i class="fas fa-book fa-4x text-muted mb-3"></i>
                <h5>No Courses Enrolled</h5>
                <p class="text-muted">You are not enrolled in any courses yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</body>
</html>
