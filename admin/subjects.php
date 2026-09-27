<?php
/**
 * Subject Management
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */ 

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$db = getDB();

// =====================================================
// ENSURE SUBJECTS TABLE EXISTS
// =====================================================
try {
    $db->query("SELECT 1 FROM subjects LIMIT 1");
} catch (PDOException $e) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `subjects` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `subject_code` VARCHAR(50) NOT NULL UNIQUE,
            `subject_name` VARCHAR(200) NOT NULL,
            `description` TEXT,
            `course_id` INT NOT NULL,
            `lecturer_id` INT,
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB");
        
        $db->exec("CREATE INDEX idx_subjects_course ON subjects(course_id)");
        $db->exec("CREATE INDEX idx_subjects_lecturer ON subjects(lecturer_id)");
        $db->exec("CREATE INDEX idx_subjects_status ON subjects(status)");
        
        setFlash('info', 'Subjects table created automatically.');
    } catch (PDOException $createError) {
        die("Error creating subjects table: " . $createError->getMessage());
    }
}

// Ensure other required tables
ensureAuthSchema($db);
ensureStudentsTable($db);

$pageTitle = 'Subject Management';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
            $subjectCode = strtoupper(trim($_POST['subject_code'] ?? ''));
            $subjectName = trim($_POST['subject_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $courseId = (int)($_POST['course_id'] ?? 0);
            $lecturerId = !empty($_POST['lecturer_id']) ? (int)$_POST['lecturer_id'] : null;

            if ($subjectCode === '' || $subjectName === '' || $courseId < 1) {
                setFlash('error', 'Subject code, subject name, and course are required.');
                redirect('subjects.php');
            }
            
            try {
                $stmt = $db->prepare("SELECT id FROM subjects WHERE subject_code = ? LIMIT 1");
                $stmt->execute([$subjectCode]);
                if ($stmt->fetchColumn()) {
                    throw new RuntimeException('A subject with this code already exists.');
                }

                $stmt = $db->prepare("
                    INSERT INTO subjects (subject_code, subject_name, description, course_id, lecturer_id, status) 
                    VALUES (?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([$subjectCode, $subjectName, $description, $courseId, $lecturerId]);
                
                logActivity($_SESSION['user_id'], 'Subject Added', "Added subject: $subjectCode - $subjectName");
                setFlash('success', 'Subject added successfully!');
            } catch (PDOException | RuntimeException $e) {
                $message = $e->getMessage();
                if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
                    $message = 'A subject with this code already exists.';
                }
                setFlash('error', $message);
            }
            redirect('subjects.php');
            break;
            
        case 'update':
            $id = (int)$_POST['id'];
            $subjectCode = sanitize($_POST['subject_code']);
            $subjectName = sanitize($_POST['subject_name']);
            $description = sanitize($_POST['description']);
            $courseId = (int)$_POST['course_id'];
            $lecturerId = !empty($_POST['lecturer_id']) ? (int)$_POST['lecturer_id'] : null;
            $status = sanitize($_POST['status']);
            
            try {
                $stmt = $db->prepare("
                    UPDATE subjects 
                    SET subject_code = ?, subject_name = ?, description = ?, course_id = ?, lecturer_id = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$subjectCode, $subjectName, $description, $courseId, $lecturerId, $status, $id]);
                
                logActivity($_SESSION['user_id'], 'Subject Updated', "Updated subject ID: $id");
                setFlash('success', 'Subject updated successfully!');
            } catch (PDOException $e) {
                setFlash('error', 'Failed to update subject: ' . $e->getMessage());
            }
            redirect('subjects.php');
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            try {
                $stmt = $db->prepare("DELETE FROM subjects WHERE id = ?");
                $stmt->execute([$id]);
                
                logActivity($_SESSION['user_id'], 'Subject Deleted', "Deleted subject ID: $id");
                setFlash('success', 'Subject deleted successfully!');
            } catch (PDOException $e) {
                setFlash('error', 'Failed to delete subject: ' . $e->getMessage());
            }
            redirect('subjects.php');
            break;
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Search & Filter
$search = sanitize($_GET['search'] ?? '');
$courseFilter = isset($_GET['course']) ? (int)$_GET['course'] : '';

$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (s.subject_code LIKE ? OR s.subject_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($courseFilter) {
    $where .= " AND s.course_id = ?";
    $params[] = $courseFilter;
}

$countSql = "SELECT COUNT(*) as count FROM subjects s $where";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalSubjects = $stmt->fetch()['count'];

$sql = "
    SELECT s.*, 
           c.course_name, c.course_code as c_code,
           l.full_name as lecturer_name
    FROM subjects s
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN lecturers l ON s.lecturer_id = l.id
    $where
    ORDER BY s.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$subjects = $stmt->fetchAll();

// Fetch courses and lecturers for dropdowns
$coursesList = $db->query("SELECT id, course_code, course_name FROM courses ORDER BY course_code")->fetchAll();
$lecturersList = $db->query("SELECT id, full_name FROM lecturers ORDER BY full_name")->fetchAll();
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
                <li class="nav-item"><a class="nav-link" href="courses.php"><i class="fas fa-book me-2"></i>Courses</a></li>
                <li class="nav-item"><a class="nav-link active" href="subjects.php"><i class="fas fa-book-open me-2"></i>Subjects</a></li>
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
                <div><h5 class="mb-1">Manage Subjects</h5><p class="text-muted mb-0">Total: <?php echo $totalSubjects; ?> subjects</p></div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal"><i class="fas fa-plus me-2"></i>Add Subject</button>
            </div>
        </div>
        
        <div class="px-4 mb-4">
            <div class="card">
                <div class="card-body py-3">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="search" placeholder="Search by code or name" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="course">
                                <option value="">All Courses</option>
                                <?php foreach($coursesList as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $courseFilter == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary me-2"><i class="fas fa-search me-1"></i>Search</button>
                            <a href="subjects.php" class="btn btn-outline-secondary">Reset</a>
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
                                    <th>Subject Name</th>
                                    <th>Linked Course</th>
                                    <th>Lecturer</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subjects)): ?>
                                <tr><td colspan="6" class="text-center py-5"><i class="fas fa-book-open fa-3x text-muted mb-3"></i><p class="text-muted">No subjects found</p></td></tr>
                                <?php else: ?>
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($subject['subject_code']); ?></code></td>
                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    <td>
                                        <?php if($subject['c_code']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($subject['c_code']); ?></span>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($subject['course_name']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($subject['lecturer_name']): ?>
                                            <?php echo htmlspecialchars($subject['lecturer_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-<?php echo $subject['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($subject['status']); ?></span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick='editSubject(<?php echo json_encode($subject, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $subject['id']; ?>)"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalSubjects > $perPage): ?>
                    <div class="px-4 py-3 border-top"><?php echo generatePagination('subjects.php', $page, ceil($totalSubjects / $perPage), ['search' => $search, 'course' => $courseFilter]); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Subject Code</label><input type="text" class="form-control" name="subject_code" required placeholder="e.g., PRG101">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Subject Name</label><input type="text" class="form-control" name="subject_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Course</label>
                                <select class="form-select" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach($coursesList as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lecturer (Optional)</label>
                                <select class="form-select" name="lecturer_id">
                                    <option value="">Select Lecturer</option>
                                    <?php foreach($lecturersList as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update"><input type="hidden" name="id" id="edit_id">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Subject Code</label><input type="text" class="form-control" name="subject_code" id="edit_subject_code" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Subject Name</label><input type="text" class="form-control" name="subject_name" id="edit_subject_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Course</label>
                                <select class="form-select" name="course_id" id="edit_course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach($coursesList as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lecturer (Optional)</label>
                                <select class="form-select" name="lecturer_id" id="edit_lecturer_id">
                                    <option value="">Select Lecturer</option>
                                    <?php foreach($lecturersList as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label><textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Subject</button>
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
        function editSubject(subject) {
            document.getElementById('edit_id').value = subject.id;
            document.getElementById('edit_subject_code').value = subject.subject_code;
            document.getElementById('edit_subject_name').value = subject.subject_name;
            document.getElementById('edit_course_id').value = subject.course_id;
            document.getElementById('edit_lecturer_id').value = subject.lecturer_id || '';
            document.getElementById('edit_status').value = subject.status;
            document.getElementById('edit_description').value = subject.description || '';
            new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
        }
        
        function confirmDelete(id) {
            Swal.fire({ title: 'Delete Subject?', text: 'This will permanently delete the subject and related records.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete' })
                .then(result => { if (result.isConfirmed) { document.getElementById('delete_id').value = id; document.getElementById('deleteForm').submit(); } });
        }
    </script>
</body>
</html>