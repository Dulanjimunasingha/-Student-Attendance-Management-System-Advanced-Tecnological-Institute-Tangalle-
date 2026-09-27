<?php
/**
 * Attendance Management
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

$pageTitle = 'Attendance Records';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_attendance') {
        $recordId = (int)$_POST['record_id'];
        $status = sanitize($_POST['status']);
        
        $stmt = $db->prepare("UPDATE attendance_records SET attendance_status = ? WHERE id = ?");
        $stmt->execute([$status, $recordId]);
        
        logActivity($_SESSION['user_id'], 'Attendance Updated', "Updated attendance record ID: $recordId to $status");
        setFlash('success', 'Attendance updated successfully!');
        redirect('attendance.php');
    }
    
    if ($_POST['action'] === 'delete_session') {
        $sessionId = (int)$_POST['session_id'];
        
        $stmt = $db->prepare("DELETE FROM attendance_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        
        logActivity($_SESSION['user_id'], 'Session Deleted', "Deleted attendance session ID: $sessionId");
        setFlash('success', 'Attendance session deleted successfully!');
        redirect('attendance.php');
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$courseFilter = sanitize($_GET['course'] ?? '');
$dateFilter = sanitize($_GET['date'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$where = "WHERE 1=1";
$params = [];

if ($courseFilter) {
    $where .= " AND c.id = ?";
    $params[] = $courseFilter;
}

if ($dateFilter) {
    $where .= " AND DATE(ass.session_date) = ?";
    $params[] = $dateFilter;
}

if ($statusFilter) {
    $where .= " AND ar.attendance_status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $where .= " AND (s.student_id LIKE ? OR s.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count
$countSql = "
    SELECT COUNT(*) as count 
    FROM attendance_records ar
    JOIN attendance_sessions ass ON ar.session_id = ass.id
    JOIN courses c ON ass.course_id = c.id
    JOIN students s ON ar.student_id = s.id
    $where
";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetch()['count'];

// Get records
$sql = "
    SELECT ar.*, ar.id as record_id, s.student_id, s.full_name, c.course_code, c.course_name, ass.session_date, ass.lesson_topic
    FROM attendance_records ar
    JOIN attendance_sessions ass ON ar.session_id = ass.id
    JOIN courses c ON ass.course_id = c.id
    JOIN students s ON ar.student_id = s.id
    $where
    ORDER BY ass.session_date DESC, s.full_name ASC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get filters
$courses = $db->query("SELECT * FROM courses WHERE status = 'active' ORDER BY course_name")->fetchAll();
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
        .table thead th { background: #f8f9fa; border-bottom: 2px solid #dee2e6; font-weight: 600; font-size: 12px; text-transform: uppercase; color: #6c757d; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .status-badge { padding: 5px 10px; border-radius: 6px; font-weight: 500; }
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
                <li class="nav-item"><a class="nav-link active" href="attendance.php"><i class="fas fa-calendar-check me-2"></i>Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="announcements.php"><i class="fas fa-bullhorn me-2"></i>Announcements</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
    </aside>
    
    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div><h4 class="mb-0"><?php echo $pageTitle; ?></h4></div>
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
        </nav>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mx-4 mt-4"><?php echo htmlspecialchars($flash['message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div><h5 class="mb-1">View & Manage Attendance</h5><p class="text-muted mb-0">Total: <?php echo $totalRecords; ?> records</p></div>
                <a href="reports.php" class="btn btn-success"><i class="fas fa-download me-2"></i>Export Report</a>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="px-4 mb-4">
            <div class="card">
                <div class="card-body py-3">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Search student ID or name" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="course">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $courseFilter == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['course_code']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" name="date" value="<?php echo $dateFilter; ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="present" <?php echo $statusFilter === 'present' ? 'selected' : ''; ?>>Present</option>
                                <option value="absent" <?php echo $statusFilter === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                <option value="late" <?php echo $statusFilter === 'late' ? 'selected' : ''; ?>>Late</option>
                                <option value="excused" <?php echo $statusFilter === 'excused' ? 'selected' : ''; ?>>Excused</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary me-2"><i class="fas fa-search me-1"></i>Filter</button>
                            <a href="attendance.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Records Table -->
        <div class="px-4 mb-4">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Lesson Topic</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($records)): ?>
                                <tr><td colspan="7" class="text-center py-5"><i class="fas fa-calendar-times fa-3x text-muted mb-3"></i><p class="text-muted">No attendance records found</p></td></tr>
                                <?php else: ?>
                                <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo formatDate($record['session_date']); ?></td>
                                    <td><code><?php echo htmlspecialchars($record['student_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                    <td><small><?php echo htmlspecialchars($record['course_code']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars(substr($record['lesson_topic'] ?? 'N/A', 0, 30)); ?></small></td>
                                    <td>
                                        <span class="status-badge bg-<?php 
                                            echo $record['attendance_status'] === 'present' ? 'success text-white' : 
                                                 ($record['attendance_status'] === 'absent' ? 'danger text-white' : 
                                                  ($record['attendance_status'] === 'late' ? 'warning text-dark' : 'info text-white'));
                                        ?>">
                                            <?php echo ucfirst($record['attendance_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editAttendance(<?php echo $record['record_id']; ?>, '<?php echo $record['attendance_status']; ?>')"><i class="fas fa-edit"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalRecords > $perPage): ?>
                    <div class="px-4 py-3 border-top"><?php echo generatePagination('attendance.php', $page, ceil($totalRecords / $perPage), $_GET); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Attendance Modal -->
    <div class="modal fade" id="editAttendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_attendance">
                        <input type="hidden" name="record_id" id="edit_record_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                                <option value="excused">Excused</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script>
        function editAttendance(recordId, currentStatus) {
            document.getElementById('edit_record_id').value = recordId;
            document.getElementById('edit_status').value = currentStatus;
            new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
        }
    </script>
</body>
</html>
