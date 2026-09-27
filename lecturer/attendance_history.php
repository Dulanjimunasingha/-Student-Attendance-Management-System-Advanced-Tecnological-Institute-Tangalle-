<?php
/**
 * Attendance History
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

$pageTitle = 'Attendance History';
 
$stmt = $db->prepare("SELECT * FROM lecturers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$lecturer = $stmt->fetch();
$lecturerId = $lecturer['id'];

// Filters
$courseFilter = sanitize($_GET['course'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');

$where = "WHERE ass.lecturer_id = ?";
$params = [$lecturerId];

if ($courseFilter) {
    $where .= " AND ass.course_id = ?";
    $params[] = $courseFilter;
}

if ($dateFrom) {
    $where .= " AND DATE(ass.session_date) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where .= " AND DATE(ass.session_date) <= ?";
    $params[] = $dateTo;
}

// Get sessions
$query = "
    SELECT ass.*, c.course_code, c.course_name,
           COUNT(ar.id) as total_students,
           SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
           SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
           SUM(CASE WHEN ar.attendance_status = 'late' THEN 1 ELSE 0 END) as late,
           SUM(CASE WHEN ar.attendance_status = 'excused' THEN 1 ELSE 0 END) as excused
    FROM attendance_sessions ass
    JOIN courses c ON ass.course_id = c.id
    LEFT JOIN attendance_records ar ON ass.id = ar.session_id
    " . $where . "
    GROUP BY ass.id
    ORDER BY ass.session_date DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

$courses = getLecturerCourses($lecturerId);
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
                <li class="nav-item"><a class="nav-link active" href="attendance_history.php"><i class="fas fa-history me-2"></i>Attendance History</a></li>
                <li class="nav-item"><a class="nav-link" href="student_search.php"><i class="fas fa-search me-2"></i>Student Search</a></li>
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
            <h5 class="mb-1"><i class="fas fa-history me-2"></i>View Attendance History</h5>
            <p class="text-muted mb-0"><?php echo count($sessions); ?> sessions recorded</p>
        </div>
        
        <!-- Filters -->
        <div class="px-4 mb-4">
            <div class="card">
                <div class="card-body py-3">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select" name="course">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $courseFilter == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['course_code']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" name="date_from" value="<?php echo $dateFrom; ?>" placeholder="From">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" name="date_to" value="<?php echo $dateTo; ?>" placeholder="To">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search me-1"></i>Filter</button>
                            <a href="attendance_history.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sessions Table -->
        <div class="px-4 mb-4">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Course</th>
                                    <th>Topic</th>
                                    <th class="text-center">Present</th>
                                    <th class="text-center">Absent</th>
                                    <th class="text-center">Late</th>
                                    <th class="text-center">Excused</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sessions)): ?>
                                <tr><td colspan="8" class="text-center py-5"><i class="fas fa-calendar-times fa-3x text-muted mb-3"></i><p class="text-muted">No attendance sessions found</p></td></tr>
                                <?php else: ?>
                                <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td><?php echo formatDate($session['session_date']); ?></td>
                                    <td><?php echo htmlspecialchars($session['course_code']); ?><br><small class="text-muted"><?php echo htmlspecialchars($session['course_name']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars(substr($session['lesson_topic'] ?? 'N/A', 0, 30)); ?></small></td>
                                    <td class="text-center"><span class="badge bg-success"><?php echo $session['present']; ?></span></td>
                                    <td class="text-center"><span class="badge bg-danger"><?php echo $session['absent']; ?></span></td>
                                    <td class="text-center"><span class="badge bg-warning text-dark"><?php echo $session['late']; ?></span></td>
                                    <td class="text-center"><span class="badge bg-info"><?php echo $session['excused']; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewSession(<?php echo $session['id']; ?>)"><i class="fas fa-eye"></i></button>
                                    </td>
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
    
    <!-- View Session Modal -->
    <div class="modal fade" id="viewSessionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-eye me-2"></i>Session Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body" id="sessionDetails">Loading...</div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script>
        async function viewSession(sessionId) {
            const modal = new bootstrap.Modal(document.getElementById('viewSessionModal'));
            modal.show();
            
            try {
                const response = await fetch(`../api/get_session_details.php?session_id=${sessionId}`);
                const data = await response.json();
                
                let html = `
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Course:</strong> ${data.course_code} - ${data.course_name}</div>
                        <div class="col-md-6"><strong>Date:</strong> ${data.session_date}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12"><strong>Topic:</strong> ${data.lesson_topic || 'N/A'}</div>
                    </div>
                    <div class="mb-3"><strong>Notes:</strong> ${data.notes || 'None'}</div>
                    <hr>
                    <h6>Student Attendance</h6>
                    <table class="table table-sm">
                        <thead><tr><th>Student ID</th><th>Name</th><th>Status</th></tr></thead>
                        <tbody>
                `;
                
                data.students.forEach(s => {
                    html += `<tr><td>${s.student_id}</td><td>${s.full_name}</td><td><span class="badge bg-${s.attendance_status === 'present' ? 'success' : (s.attendance_status === 'absent' ? 'danger' : (s.attendance_status === 'late' ? 'warning' : 'info'))}">${s.attendance_status}</span></td></tr>`;
                });
                
                html += '</tbody></table>';
                document.getElementById('sessionDetails').innerHTML = html;
            } catch (error) {
                document.getElementById('sessionDetails').innerHTML = '<p class="text-danger">Error loading session details</p>';
            }
        }
    </script>
</body>
</html>
