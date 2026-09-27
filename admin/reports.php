<?php
/**
 * Reports Page
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

$pageTitle = 'Reports';

$reportType = sanitize($_GET['type'] ?? 'overview');

// Get report data based on type
switch ($reportType) {
    case 'low_attendance':
        $title = 'Low Attendance Students Report';
        $students = $db->query("
            SELECT s.*, 
                   COUNT(ar.id) as total,
                   SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
                   SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
                   SUM(CASE WHEN ar.attendance_status = 'late' THEN 1 ELSE 0 END) as late,
                   SUM(CASE WHEN ar.attendance_status = 'excused' THEN 1 ELSE 0 END) as excused,
                   ROUND((SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as percentage
            FROM students s
            LEFT JOIN attendance_records ar ON s.id = ar.student_id
            GROUP BY s.id
            HAVING percentage < 75 AND total > 0
            ORDER BY percentage ASC
        ")->fetchAll();
        break;
        
    case 'student_attendance':
        $studentId = (int)($_GET['student_id'] ?? 0);
        $title = 'Student Attendance Report';
        $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        
        if ($student) {
            $stmt2 = $db->prepare("
                SELECT c.*, 
                       COUNT(ar.id) as total,
                       SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
                       SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
                       SUM(CASE WHEN ar.attendance_status = 'late' THEN 1 ELSE 0 END) as late,
                       SUM(CASE WHEN ar.attendance_status = 'excused' THEN 1 ELSE 0 END) as excused,
                       ROUND((SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as percentage
                FROM courses c
                LEFT JOIN attendance_sessions ass ON c.id = ass.course_id
                LEFT JOIN attendance_records ar ON ass.id = ar.session_id AND ar.student_id = ?
                LEFT JOIN enrollments e ON c.id = e.course_id AND e.student_id = ?
                WHERE e.student_id IS NOT NULL
                GROUP BY c.id
            ");
            $stmt2->execute([$studentId, $studentId]);
            $courses = $stmt2->fetchAll();
        } else {
            $courses = [];
        }
        break;
        
    case 'course_attendance':
        $courseId = (int)($_GET['course_id'] ?? 0);
        $title = 'Course Attendance Report';
        $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$courseId]);
        $course = $stmt->fetch();
        
        if ($course) {
            $stmt2 = $db->prepare("SELECT * FROM attendance_sessions WHERE course_id = ? ORDER BY session_date DESC");
            $stmt2->execute([$courseId]);
            $sessions = $stmt2->fetchAll();
            
            $stmt3 = $db->prepare("
            SELECT 
                COUNT(DISTINCT ar.student_id) as total_students,
                COUNT(ar.id) as total_records,
                SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN ar.attendance_status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN ar.attendance_status = 'excused' THEN 1 ELSE 0 END) as excused
            FROM attendance_records ar
            JOIN attendance_sessions ass ON ar.session_id = ass.id
            WHERE ass.course_id = ?
            ");
            $stmt3->execute([$courseId]);
            $stats = $stmt3->fetch();
        } else {
            $stats = null;
        }
        break;
        
    default:
        $title = 'Attendance Overview Report';
        $totalStudents = $db->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'")->fetch()['count'];
        $totalCourses = $db->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")->fetch()['count'];
        $totalSessions = $db->query("SELECT COUNT(*) as count FROM attendance_sessions")->fetch()['count'];
        $stats = $db->query("
            SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN attendance_status = 'excused' THEN 1 ELSE 0 END) as excused
            FROM attendance_records
        ")->fetch();
        
        $courseStats = $db->query("
            SELECT c.course_code, c.course_name,
                   COUNT(ar.id) as total,
                   SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
                   ROUND((SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 1) as percentage
            FROM courses c
            LEFT JOIN attendance_sessions ass ON c.id = ass.course_id
            LEFT JOIN attendance_records ar ON ass.id = ar.session_id
            WHERE c.status = 'active'
            GROUP BY c.id
            ORDER BY percentage DESC
        ")->fetchAll();
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .table thead th { background: #f8f9fa; border-bottom: 2px solid #dee2e6; font-weight: 600; font-size: 12px; text-transform: uppercase; color: #6c757d; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand"><h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4><small>Attendance System</small></div>
        <nav class="sidebar-menu mt-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="students.php"><i class="fas fa-user-graduate me-2"></i>Students</a></li>
                <li class="nav-item"><a class="nav-link" href="lecturers.php"><i class="fas fa-chalkboard-teacher me-2"></i>Lecturers</a></li>
                <li class="nav-item"><a class="nav-link" href="courses.php"><i class="fas fa-book me-2"></i>Courses</a></li>
                <li class="nav-item"><a class="nav-link" href="subjects.php"><i class="fas fa-book-open me-2"></i>Subjects</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-calendar-check me-2"></i>Attendance</a></li>
                <li class="nav-item"><a class="nav-link active" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="announcements.php"><i class="fas fa-bullhorn me-2"></i>Announcements</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
    </aside>
    
    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div><h4 class="mb-0"><?php echo $pageTitle; ?></h4></div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-primary"><i class="fas fa-print me-2"></i>Print</button>
                <button onclick="exportToCSV()" class="btn btn-success"><i class="fas fa-file-csv me-2"></i>Export CSV</button>
            </div>
        </nav>
        
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-1"><i class="fas fa-chart-bar me-2"></i><?php echo $title; ?></h5>
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Select Report</button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?type=overview">Overview Report</a></li>
                        <li><a class="dropdown-item" href="?type=low_attendance">Low Attendance Report</a></li>
                        <li><a class="dropdown-item" href="?type=student_attendance">Student Attendance Report</a></li>
                        <li><a class="dropdown-item" href="?type=course_attendance">Course Attendance Report</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="px-4 mb-4">
            <?php if ($reportType === 'overview'): ?>
            <!-- Overview Report -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-primary mb-1"><?php echo $totalStudents; ?></h3>
                        <p class="text-muted mb-0">Total Students</p>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-success mb-1"><?php echo $totalCourses; ?></h3>
                        <p class="text-muted mb-0">Active Courses</p>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-info mb-1"><?php echo $totalSessions; ?></h3>
                        <p class="text-muted mb-0">Sessions</p>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center"><div class="card-body py-4">
                        <h3 class="text-warning mb-1"><?php echo $stats['total_records']; ?></h3>
                        <p class="text-muted mb-0">Total Records</p>
                    </div></div>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="fas fa-chart-pie me-2"></i>Attendance Distribution</div>
                        <div class="card-body">
                            <canvas id="pieChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Attendance by Status</div>
                        <div class="card-body">
                            <canvas id="barChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header"><i class="fas fa-book me-2"></i>Course Attendance Summary</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="reportTable">
                            <thead>
                                <tr><th>Course</th><th>Total</th><th>Present</th><th>Percentage</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courseStats as $cs): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cs['course_code']); ?> - <?php echo htmlspecialchars($cs['course_name']); ?></td>
                                    <td><?php echo $cs['total']; ?></td>
                                    <td><?php echo $cs['present']; ?></td>
                                    <td><span class="badge bg-<?php echo $cs['percentage'] >= 75 ? 'success' : ($cs['percentage'] >= 50 ? 'warning' : 'danger'); ?>"><?php echo $cs['percentage']; ?>%</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php elseif ($reportType === 'low_attendance'): ?>
            <div class="card">
                <div class="card-header"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Students Below 75% Attendance</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="reportTable">
                            <thead>
                                <tr><th>Student ID</th><th>Name</th><th>Total</th><th>Present</th><th>Absent</th><th>Late</th><th>Percentage</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($s['student_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                                    <td><?php echo $s['total']; ?></td>
                                    <td class="text-success"><?php echo $s['present']; ?></td>
                                    <td class="text-danger"><?php echo $s['absent']; ?></td>
                                    <td class="text-warning"><?php echo $s['late']; ?></td>
                                    <td><span class="badge bg-danger"><?php echo $s['percentage']; ?>%</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script>
        // Charts for overview report
        <?php if ($reportType === 'overview'): ?>
        new Chart(document.getElementById('pieChart'), {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Excused'],
                datasets: [{
                    data: [<?php echo $stats['present']; ?>, <?php echo $stats['absent']; ?>, <?php echo $stats['late']; ?>, <?php echo $stats['excused']; ?>],
                    backgroundColor: ['#198754', '#dc3545', '#ffc107', '#0dcaf0']
                }]
            }
        });
        
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Excused'],
                datasets: [{
                    label: 'Count',
                    data: [<?php echo $stats['present']; ?>, <?php echo $stats['absent']; ?>, <?php echo $stats['late']; ?>, <?php echo $stats['excused']; ?>],
                    backgroundColor: ['#198754', '#dc3545', '#ffc107', '#0dcaf0']
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });
        <?php endif; ?>
        
        function exportToCSV() {
            const table = document.getElementById('reportTable');
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const cols = row.querySelectorAll('td, th');
                const rowData = Array.from(cols).map(col => '"' + col.innerText.replace(/"/g, '""') + '"').join(',');
                csv.push(rowData);
            });
            
            const blob = new Blob(['\ufeff' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'attendance_report_<?php echo date('Ymd'); ?>.csv';
            link.click();
        }
    </script>
</body>
</html>
