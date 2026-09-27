<?php
/**
 * Course Management
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

$pageTitle = 'Course Management';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
            $courseCode = sanitize($_POST['course_code']);
            $courseName = sanitize($_POST['course_name']);
            $description = sanitize($_POST['description']);
            $department = sanitize($_POST['department']);
            $credits = (int)$_POST['credits'];
            $minAttendance = (float)$_POST['min_attendance_percentage'];
            $startDate = sanitize($_POST['start_date']);
            $endDate = sanitize($_POST['end_date']);
            
            try {
                $stmt = $db->prepare("
                    INSERT INTO courses (course_code, course_name, description, department, credits, min_attendance_percentage, start_date, end_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([$courseCode, $courseName, $description, $department, $credits, $minAttendance, $startDate, $endDate]);
                
                logActivity($_SESSION['user_id'], 'Course Added', "Added course: $courseCode - $courseName");
                setFlash('success', 'Course added successfully!');
            } catch (PDOException $e) {
                setFlash('error', 'Failed to add course');
            }
            redirect('courses.php');
            break;
            
        case 'update':
            $id = (int)$_POST['id'];
            $courseCode = sanitize($_POST['course_code']);
            $courseName = sanitize($_POST['course_name']);
            $description = sanitize($_POST['description']);
            $department = sanitize($_POST['department']);
            $credits = (int)$_POST['credits'];
            $minAttendance = (float)$_POST['min_attendance_percentage'];
            $startDate = sanitize($_POST['start_date']);
            $endDate = sanitize($_POST['end_date']);
            $status = sanitize($_POST['status']);
            
            try {
                $stmt = $db->prepare("
                    UPDATE courses 
                    SET course_code = ?, course_name = ?, description = ?, department = ?, credits = ?, 
                        min_attendance_percentage = ?, start_date = ?, end_date = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$courseCode, $courseName, $description, $department, $credits, $minAttendance, $startDate, $endDate, $status, $id]);
                
                logActivity($_SESSION['user_id'], 'Course Updated', "Updated course ID: $id");
                setFlash('success', 'Course updated successfully!');
            } catch (PDOException $e) {
                setFlash('error', 'Failed to update course');
            }
            redirect('courses.php');
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            try {
                $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->execute([$id]);
                
                logActivity($_SESSION['user_id'], 'Course Deleted', "Deleted course ID: $id");
                setFlash('success', 'Course deleted successfully!');
            } catch (PDOException $e) {
                setFlash('error', 'Failed to delete course');
            }
            redirect('courses.php');
            break;
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Search & Filter
$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');

$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (c.course_code LIKE ? OR c.course_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter) {
    $where .= " AND c.status = ?";
    $params[] = $statusFilter;
}

$countSql = "SELECT COUNT(*) as count FROM courses c $where";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalCourses = $stmt->fetch()['count'];

$sql = "
    SELECT c.*, 
           COUNT(DISTINCT e.student_id) as students_count,
           COUNT(DISTINCT lca.lecturer_id) as lecturers_count,
           COUNT(DISTINCT ass.id) as sessions_count
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN lecturer_course_assignments lca ON c.id = lca.course_id
    LEFT JOIN attendance_sessions ass ON c.id = ass.course_id
    $where
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$courses = $stmt->fetchAll();
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
                <li class="nav-item"><a class="nav-link active" href="courses.php"><i class="fas fa-book me-2"></i>Courses</a></li>
                <li class="nav-item"><a class="nav-link" href="subjects.php"><i class="fas fa-book-open me-2"></i>Subjects</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-calendar-check me-2"></i>Attendance</a></li>
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
                <div><h5 class="mb-1">Manage Courses</h5><p class="text-muted mb-0">Total: <?php echo $totalCourses; ?> courses</p></div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="fas fa-plus me-2"></i>Add Course</button>
            </div>
        </div>
        
        <div class="px-4 mb-4">
            <div class="card">
                <div class="card-body py-3">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="search" placeholder="Search by code or name" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary me-2"><i class="fas fa-search me-1"></i>Search</button>
                            <a href="courses.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="px-4 mb-4">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Course Name</th>
                                    <th>Department</th>
                                    <th>Credits</th>
                                    <th>Min. Attendance</th>
                                    <th>Students</th>
                                    <th>Sessions</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($courses)): ?>
                                <tr><td colspan="9" class="text-center py-5"><i class="fas fa-book fa-3x text-muted mb-3"></i><p class="text-muted">No courses found</p></td></tr>
                                <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($course['course_code']); ?></code></td>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($course['department']); ?></span></td>
                                    <td><?php echo $course['credits']; ?></td>
                                    <td><?php echo $course['min_attendance_percentage']; ?>%</td>
                                    <td><span class="badge bg-secondary"><?php echo $course['students_count']; ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo $course['sessions_count']; ?></span></td>
                                    <td><span class="badge bg-<?php echo $course['status'] === 'active' ? 'success' : ($course['status'] === 'inactive' ? 'secondary' : 'primary'); ?>"><?php echo ucfirst($course['status']); ?></span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $course['id']; ?>)"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalCourses > $perPage): ?>
                    <div class="px-4 py-3 border-top"><?php echo generatePagination('courses.php', $page, ceil($totalCourses / $perPage), ['search' => $search, 'status' => $statusFilter]); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Course</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Course Code</label><input type="text" class="form-control" name="course_code" required placeholder="e.g., CS101">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Course Name</label><input type="text" class="form-control" name="course_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Information Technology">Information Technology</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Physics">Physics</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Credits</label><input type="number" class="form-control" name="credits" value="3" min="1" max="10" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Min. Attendance %</label><input type="number" class="form-control" name="min_attendance_percentage" value="75" min="0" max="100" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label><input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label><input type="date" class="form-control" name="end_date" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Course Modal -->
    <div class="modal fade" id="editCourseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Course</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update"><input type="hidden" name="id" id="edit_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Course Code</label><input type="text" class="form-control" name="course_code" id="edit_course_code" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Course Name</label><input type="text" class="form-control" name="course_name" id="edit_course_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department" id="edit_department" required>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Information Technology">Information Technology</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Physics">Physics</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Credits</label><input type="number" class="form-control" name="credits" id="edit_credits" min="1" max="10" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Min. Attendance %</label><input type="number" class="form-control" name="min_attendance_percentage" id="edit_min_attendance" min="0" max="100" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label><input type="date" class="form-control" name="start_date" id="edit_start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label><input type="date" class="form-control" name="end_date" id="edit_end_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label><textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function editCourse(course) {
            document.getElementById('edit_id').value = course.id;
            document.getElementById('edit_course_code').value = course.course_code;
            document.getElementById('edit_course_name').value = course.course_name;
            document.getElementById('edit_department').value = course.department;
            document.getElementById('edit_credits').value = course.credits;
            document.getElementById('edit_min_attendance').value = course.min_attendance_percentage;
            document.getElementById('edit_start_date').value = course.start_date;
            document.getElementById('edit_end_date').value = course.end_date;
            document.getElementById('edit_status').value = course.status;
            document.getElementById('edit_description').value = course.description || '';
            new bootstrap.Modal(document.getElementById('editCourseModal')).show();
        }
        
        function confirmDelete(id) {
            Swal.fire({ title: 'Delete Course?', text: 'This will permanently delete the course and all related records.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete' })
                .then(result => { if (result.isConfirmed) { document.getElementById('delete_id').value = id; document.getElementById('deleteForm').submit(); } });
        }
    </script>
</body>
</html>
