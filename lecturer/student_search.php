<?php
/**
 * Student Search
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('lecturer');

// Ensure all required tables exist
$db = getDB();
ensureAuthSchema($db);
ensureStudentsTable($db);

$pageTitle = 'Student Search'; 

$stmt = $db->prepare("SELECT * FROM lecturers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$lecturer = $stmt->fetch();
$lecturerId = $lecturer['id'];

$search = sanitize($_GET['search'] ?? '');
$student = null;
$courses = [];
$attendanceStats = [];

if ($search) {
    // Search by student ID or name
    $stmt = $db->prepare("
        SELECT s.*, 
               COUNT(ar.id) as total,
               SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
               SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
               SUM(CASE WHEN ar.attendance_status = 'late' THEN 1 ELSE 0 END) as late,
               SUM(CASE WHEN ar.attendance_status = 'excused' THEN 1 ELSE 0 END) as excused
        FROM students s
        LEFT JOIN attendance_records ar ON s.id = ar.student_id
        WHERE s.student_id LIKE ? OR s.full_name LIKE ?
        GROUP BY s.id
        LIMIT 1
    ");
    $stmt->execute(["%$search%", "%$search%"]);
    $student = $stmt->fetch();
    
    if ($student) {
        // Get enrolled courses for this student
        $stmt = $db->prepare("
            SELECT c.*, 
                   COUNT(ar.id) as total,
                   SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
                   SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
                   ROUND((SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as percentage
            FROM courses c
            JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN attendance_sessions ass ON c.id = ass.course_id
            LEFT JOIN attendance_records ar ON ass.id = ar.session_id AND ar.student_id = ?
            WHERE e.student_id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$student['id'], $student['id']]);
        $courses = $stmt->fetchAll();
        
        // Overall attendance
        $overallPercentage = $student['total'] > 0 ? round(($student['present'] + $student['late'] * 0.5) / $student['total'] * 100, 1) : 0;
    }
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
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand"><h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4><small>Lecturer Portal</small></div>
        <nav class="sidebar-menu mt-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="mark_attendance.php"><i class="fas fa-clipboard-check me-2"></i>Mark Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance_history.php"><i class="fas fa-history me-2"></i>Attendance History</a></li>
                <li class="nav-item"><a class="nav-link active" href="student_search.php"><i class="fas fa-search me-2"></i>Student Search</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
    </aside>
    
    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div><h4 class="mb-0"><?php echo $pageTitle; ?></h4></div>
            <div class="user-avatar"><?php echo strtoupper(substr($lecturer['full_name'], 0, 1)); ?></div>
        </nav>
        
        <div class="page-header">
            <h5 class="mb-1"><i class="fas fa-search me-2"></i>Search Student by ID</h5>
            <p class="text-muted mb-0">Enter student ID or name to view attendance details</p>
        </div>
        
        <div class="px-4 mb-4">
            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-10">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Enter Student ID or Name" value="<?php echo htmlspecialchars($search); ?>" autofocus>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Search</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($student): ?>
            <!-- Student Details -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-user me-2"></i>Student Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;"><?php echo strtoupper(substr($student['full_name'], 0, 1)); ?></div>
                            <h5><?php echo htmlspecialchars($student['full_name']); ?></h5>
                            <code><?php echo htmlspecialchars($student['student_id']); ?></code>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></p>
                                    <p><strong>Batch:</strong> <?php echo htmlspecialchars($student['batch']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Address:</strong> <?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></p>
                                    <p><strong>Status:</strong> <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($student['status']); ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Overall Attendance -->
            <?php 
            $overallPercentage = $student['total'] > 0 ? round(($student['present'] + $student['late'] * 0.5) / $student['total'] * 100, 1) : 0;
            ?>
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-primary mb-1"><?php echo $overallPercentage; ?>%</h3>
                        <small class="text-muted">Overall Attendance</small>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-success mb-1"><?php echo $student['present']; ?></h3>
                        <small class="text-muted">Present</small>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-danger mb-1"><?php echo $student['absent']; ?></h3>
                        <small class="text-muted">Absent</small>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-warning mb-1"><?php echo $student['late']; ?></h3>
                        <small class="text-muted">Late</small>
                    </div></div>
                </div>
            </div>
            
            <!-- Course-wise Attendance -->
            <div class="card">
                <div class="card-header"><i class="fas fa-book me-2"></i>Course-wise Attendance</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Course</th><th>Code</th><th>Total</th><th>Present</th><th>Absent</th><th>Percentage</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($course['course_code']); ?></code></td>
                                    <td><?php echo $course['total']; ?></td>
                                    <td class="text-success"><?php echo $course['present']; ?></td>
                                    <td class="text-danger"><?php echo $course['absent']; ?></td>
                                    <td><strong><?php echo $course['percentage']; ?>%</strong></td>
                                    <td><span class="badge bg-<?php echo $course['percentage'] >= 75 ? 'success' : 'danger'; ?>"><?php echo $course['percentage'] >= 75 ? 'Completed' : 'At Risk'; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php elseif ($search): ?>
            <div class="card text-center py-5">
                <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                <h5>Student Not Found</h5>
                <p class="text-muted">No student found with ID or name: "<?php echo htmlspecialchars($search); ?>"</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</body>
</html>
