<?php
/**
 * Lecturer Dashboard
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('lecturer');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Ensure all required tables exist
$db = getDB();
ensureAuthSchema($db);
ensureStudentsTable($db);

$user = getCurrentUser();

// Check if password change is required
if (!empty($user['must_change_password'])) {
    $_SESSION['force_password_change'] = true;
    redirect(BASE_URL . '/lecturer/change_password.php');
}

$pageTitle = 'Lecturer Dashboard';
$db = getDB();

$user = getCurrentUser();
$stmt = $db->prepare("SELECT * FROM lecturers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$lecturer = $stmt->fetch();

if (!$lecturer) {
    setFlash('error', 'Lecturer profile not found');
    redirect(BASE_URL . '/auth/logout.php');
}

$lecturerId = $lecturer['id'];

// Get assigned courses
$courses = getLecturerCourses($lecturerId);

// Get recent attendance marked
$query = "
    SELECT ar.*, s.student_id, s.full_name, c.course_name, ass.session_date, ass.lesson_topic
    FROM attendance_records ar
    JOIN attendance_sessions ass ON ar.session_id = ass.id
    JOIN courses c ON ass.course_id = c.id
    JOIN students s ON ar.student_id = s.id
    WHERE ass.lecturer_id = ?
    ORDER BY ar.marked_at DESC
    LIMIT 10
";
$stmt = $db->prepare($query);
$stmt->execute([$lecturerId]);
$recentAttendance = $stmt->fetchAll();

// Get today's sessions
$stmt2 = $db->prepare("
    SELECT * FROM attendance_sessions 
    WHERE lecturer_id = ? AND session_date = CURDATE()
");
$stmt2->execute([$lecturerId]);
$todaySessions = $stmt2->fetchAll();

// Course statistics
$courseStats = [];
foreach ($courses as $course) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT ar.student_id) as students_count,
            COUNT(ar.id) as total_records,
            SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent
        FROM attendance_sessions ass
        LEFT JOIN attendance_records ar ON ass.id = ar.session_id
        WHERE ass.course_id = ?
    ");
    $stmt->execute([$course['id']]);
    $stats = $stmt->fetch();
    
    $percentage = $stats['total_records'] > 0 ? round($stats['present'] / $stats['total_records'] * 100, 1) : 0;
    $courseStats[$course['id']] = [
        'students' => $stats['students_count'],
        'total' => $stats['total_records'],
        'percentage' => $percentage
    ];
}

// Low attendance students in lecturer's courses
$query = "
    SELECT s.student_id, s.full_name, c.course_name,
           COUNT(ar.id) as total,
           SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
           ROUND((SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as percentage
    FROM attendance_records ar
    JOIN attendance_sessions ass ON ar.session_id = ass.id
    JOIN courses c ON ass.course_id = c.id
    JOIN students s ON ar.student_id = s.id
    WHERE ass.lecturer_id = ?
    GROUP BY s.id, c.id
    HAVING percentage < 75 AND total > 0
    ORDER BY percentage ASC
    LIMIT 5
";
$stmt3 = $db->prepare($query);
$stmt3->execute([$lecturerId]);
$lowAttendance = $stmt3->fetchAll();
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
        .stat-card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .card-header { background: #fff; border-bottom: 1px solid #eee; padding: 18px 25px; }
        .table thead th { background: #f8f9fa; border-bottom: 2px solid #dee2e6; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        
        /* Modern Profile Image Styles */
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .user-avatar-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            font-size: 16px;
        }
        
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand"><h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4><small>Lecturer Portal</small></div>
        <nav class="sidebar-menu mt-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="mark_attendance.php"><i class="fas fa-clipboard-check me-2"></i>Mark Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance_history.php"><i class="fas fa-history me-2"></i>Attendance History</a></li>
                <li class="nav-item"><a class="nav-link" href="student_search.php"><i class="fas fa-search me-2"></i>Student Search</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
    </aside>
    
    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div><h4 class="mb-0"><?php echo $pageTitle; ?></h4><small class="text-muted">Welcome, <?php echo htmlspecialchars($lecturer['full_name']); ?></small></div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted"><i class="fas fa-calendar me-1"></i><?php echo date('F d, Y'); ?></span>
                <?php 
                $lecturerImage = $lecturer['image_path'] ?? '';
                $imageFullPath = !empty($lecturerImage) ? __DIR__ . '/../' . ltrim($lecturerImage, '/') : '';
                if (!empty($lecturerImage) && !empty($imageFullPath) && file_exists($imageFullPath)): 
                ?>
                <img src="<?php echo BASE_URL . '/' . ltrim($lecturerImage, '/'); ?>" alt="" class="user-avatar">
                <?php else: ?>
                <div class="user-avatar-placeholder"><?php echo strtoupper(substr($lecturer['full_name'], 0, 1)); ?></div>
                <?php endif; ?>
            </div>
        </nav>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mx-4 mt-4"><?php echo htmlspecialchars($flash['message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <div class="page-content p-4">
            <!-- Quick Stats -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between"><div><div class="h3 mb-1"><?php echo count($courses); ?></div><small class="text-muted">My Courses</small></div><div class="text-primary"><i class="fas fa-book fa-2x opacity-50"></i></div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between"><div><div class="h3 mb-1"><?php echo count($todaySessions); ?></div><small class="text-muted">Today's Sessions</small></div><div class="text-success"><i class="fas fa-calendar-day fa-2x opacity-50"></i></div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between"><div><div class="h3 mb-1"><?php echo count($recentAttendance); ?></div><small class="text-muted">Recent Records</small></div><div class="text-info"><i class="fas fa-clipboard-list fa-2x opacity-50"></i></div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between"><div><div class="h3 mb-1"><?php echo count($lowAttendance); ?></div><small class="text-muted">At Risk Students</small></div><div class="text-danger"><i class="fas fa-exclamation-triangle fa-2x opacity-50"></i></div></div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-user me-2"></i>My Lecturer Profile</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3"><strong>Lecturer ID</strong><br><code><?php echo htmlspecialchars($lecturer['lecturer_id']); ?></code></div>
                        <div class="col-md-3"><strong>Full Name</strong><br><?php echo htmlspecialchars($lecturer['full_name']); ?></div>
                        <div class="col-md-3"><strong>Email</strong><br><?php echo htmlspecialchars($lecturer['email']); ?></div>
                        <div class="col-md-3"><strong>Phone</strong><br><?php echo htmlspecialchars($lecturer['phone'] ?? 'Not provided'); ?></div>
                        <div class="col-md-3"><strong>Department</strong><br><?php echo htmlspecialchars($lecturer['department'] ?? 'Not provided'); ?></div>
                        <div class="col-md-3"><strong>Qualification</strong><br><?php echo htmlspecialchars($lecturer['qualification'] ?? 'Not provided'); ?></div>
                        <div class="col-md-3"><strong>Status</strong><br><span class="badge bg-<?php echo $lecturer['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($lecturer['status']); ?></span></div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between"><span><i class="fas fa-book me-2"></i>My Courses</span><a href="mark_attendance.php" class="btn btn-sm btn-primary">Mark Attendance</a></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead><tr><th>Course</th><th>Code</th><th>Students</th><th>Sessions</th><th>Attendance %</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($courses as $course): $stats = $courseStats[$course['id']] ?? []; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                            <td><code><?php echo htmlspecialchars($course['course_code']); ?></code></td>
                                            <td><?php echo $stats['students'] ?? 0; ?></td>
                                            <td><?php echo $stats['total'] ?? 0; ?></td>
                                            <td><span class="badge bg-<?php echo ($stats['percentage'] ?? 0) >= 75 ? 'success' : 'danger'; ?>"><?php echo $stats['percentage'] ?? 0; ?>%</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Low Attendance Alerts</div>
                        <div class="card-body p-0">
                            <?php if (empty($lowAttendance)): ?>
                            <div class="text-center py-4"><i class="fas fa-check-circle fa-2x text-success mb-2"></i><p class="text-muted mb-0">All students are doing well</p></div>
                            <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($lowAttendance as $student): ?>
                                <div class="list-group-item"><div class="d-flex justify-content-between"><div><strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($student['course_name']); ?></small></div><span class="badge bg-danger"><?php echo $student['percentage']; ?>%</span></div></div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header"><i class="fas fa-history me-2"></i>Recent Attendance Records</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Date</th><th>Student</th><th>Course</th><th>Topic</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (empty($recentAttendance)): ?>
                                <tr><td colspan="5" class="text-center py-4"><i class="fas fa-clipboard fa-2x text-muted mb-2"></i><p class="text-muted">No attendance records yet</p></td></tr>
                                <?php else: ?>
                                <?php foreach ($recentAttendance as $record): ?>
                                <tr>
                                    <td><?php echo formatDate($record['session_date']); ?></td>
                                    <td><?php echo htmlspecialchars($record['full_name']); ?><br><code><?php echo htmlspecialchars($record['student_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($record['course_name']); ?></td>
                                    <td><small><?php echo htmlspecialchars(substr($record['lesson_topic'] ?? 'N/A', 0, 25)); ?></small></td>
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
