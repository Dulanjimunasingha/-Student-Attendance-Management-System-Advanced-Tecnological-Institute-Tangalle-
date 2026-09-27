<?php
/**
 * Student Management
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

$pageTitle = 'Student Management';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $fullName = sanitize($_POST['full_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $address = sanitize($_POST['address']);
                $batch = sanitize($_POST['batch']);
                $academicYear = (int)$_POST['academic_year'];
                $dateOfBirth = $_POST['date_of_birth'] ?? null;
                $nic = sanitize($_POST['nic'] ?? '');
                $verificationCode = sanitize($_POST['verification_code'] ?? '');
                
                $studentId = generateStudentId($batch);
                
                // Use manually entered verification code or auto-generate
                $verificationField = '';
                if (!empty($verificationCode) && strlen($verificationCode) === 4) {
                    $verificationField = $verificationCode;
                } elseif (!empty($dateOfBirth)) {
                    $dobParts = explode('-', $dateOfBirth);
                    if (count($dobParts) === 3) {
                        $verificationField = substr($dobParts[2] . $dobParts[1], -4);
                    }
                } elseif (!empty($phone) && strlen($phone) >= 4) {
                    $verificationField = substr($phone, -4);
                } elseif (!empty($nic) && strlen($nic) >= 4) {
                    $verificationField = substr($nic, -4);
                }
                
                $imagePath = null;
                if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/students/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['student_image']['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array($ext, $allowedExts)) {
                        $newFileName = $studentId . '_' . time() . '.' . $ext;
                        $targetPath = $uploadDir . $newFileName;
                        if (move_uploaded_file($_FILES['student_image']['tmp_name'], $targetPath)) {
                            $imagePath = 'uploads/students/' . $newFileName;
                        }
                    }
                }
                
                try {
                    $stmt = $db->prepare("
                        INSERT INTO students (student_id, full_name, email, phone, address, batch, academic_year, status, date_of_birth, nic, verification_field, image_path) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?)
                    ");
                    $stmt->execute([$studentId, $fullName, $email, $phone, $address, $batch, $academicYear, $dateOfBirth, $nic, $verificationField, $imagePath]);
                    $newStudentDbId = $db->lastInsertId();
                    
                    // Assign courses if selected
                    if (isset($_POST['courses']) && is_array($_POST['courses'])) {
                        foreach ($_POST['courses'] as $courseId) {
                            $stmt = $db->prepare("INSERT INTO enrollments (student_id, course_id, enrolled_date, completion_status) VALUES (?, ?, CURDATE(), 'in_progress')");
                            $stmt->execute([$newStudentDbId, $courseId]);
                        }
                    }
                    
                    logActivity($_SESSION['user_id'], 'Student Added', "Added student: $studentId - $fullName");
                    setFlash('success', 'Student added successfully!');
                } catch (PDOException $e) {
                    setFlash('error', 'Failed to add student: ' . $e->getMessage());
                }
                redirect('students.php');
                break;
                
            case 'update':
                $id = (int)$_POST['id'];
                $fullName = sanitize($_POST['full_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $address = sanitize($_POST['address']);
                $batch = sanitize($_POST['batch']);
                $academicYear = (int)$_POST['academic_year'];
                $status = sanitize($_POST['status']);
                $verificationCode = sanitize($_POST['verification_code'] ?? '');
                
                try {
                    if (!empty($verificationCode) && strlen($verificationCode) === 4) {
                        $stmt = $db->prepare("
                            UPDATE students 
                            SET full_name = ?, email = ?, phone = ?, address = ?, batch = ?, academic_year = ?, status = ?, verification_field = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$fullName, $email, $phone, $address, $batch, $academicYear, $status, $verificationCode, $id]);
                    } else {
                        $stmt = $db->prepare("
                            UPDATE students 
                            SET full_name = ?, email = ?, phone = ?, address = ?, batch = ?, academic_year = ?, status = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$fullName, $email, $phone, $address, $batch, $academicYear, $status, $id]);
                    }
                    
                    logActivity($_SESSION['user_id'], 'Student Updated', "Updated student ID: $id");
                    setFlash('success', 'Student updated successfully!');
                } catch (PDOException $e) {
                    setFlash('error', 'Failed to update student');
                }
                redirect('students.php');
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    // Get student info first
                    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
                    $stmt->execute([$id]);
                    $student = $stmt->fetch();
                    
                    if ($student) {
                        // Delete attendance records
                        $stmt = $db->prepare("DELETE FROM attendance_records WHERE student_id = ?");
                        $stmt->execute([$id]);
                        
                        // Delete enrollments
                        $stmt = $db->prepare("DELETE FROM enrollments WHERE student_id = ?");
                        $stmt->execute([$id]);
                        
                        // Delete student
                        $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
                        $stmt->execute([$id]);
                        
                        logActivity($_SESSION['user_id'], 'Student Deleted', "Deleted student: " . $student['student_id']);
                        setFlash('success', 'Student deleted successfully!');
                    }
                } catch (PDOException $e) {
                    setFlash('error', 'Failed to delete student');
                }
                redirect('students.php');
                break;
                
            case 'assign_courses':
                $studentId = (int)$_POST['student_id'];
                $courseIds = $_POST['course_ids'] ?? [];
                
                // Remove existing enrollments
                $stmt = $db->prepare("DELETE FROM enrollments WHERE student_id = ?");
                $stmt->execute([$studentId]);
                
                // Add new enrollments
                foreach ($courseIds as $courseId) {
                    $stmt = $db->prepare("INSERT INTO enrollments (student_id, course_id, enrolled_date, completion_status) VALUES (?, ?, CURDATE(), 'in_progress')");
                    $stmt->execute([$studentId, $courseId]);
                }
                
                logActivity($_SESSION['user_id'], 'Courses Assigned', "Assigned courses to student ID: $studentId");
                setFlash('success', 'Courses assigned successfully!');
                redirect('students.php');
                break;
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Search
$search = sanitize($_GET['search'] ?? '');
$batchFilter = sanitize($_GET['batch'] ?? '');

// Build query
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (s.student_id LIKE ? OR s.full_name LIKE ? OR s.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($batchFilter) {
    $where .= " AND s.batch = ?";
    $params[] = $batchFilter;
}

// Count total
$countSql = "SELECT COUNT(*) as count FROM students s $where";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalStudents = $stmt->fetch()['count'];

// Get students with attendance stats
$sql = "
    SELECT s.*, 
           COUNT(DISTINCT ar.id) as total_attendance,
           SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
           SUM(CASE WHEN ar.attendance_status = 'late' THEN 1 ELSE 0 END) as late_count
    FROM students s
    LEFT JOIN attendance_records ar ON s.id = ar.student_id
    $where
    GROUP BY s.id
    ORDER BY s.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $db->prepare($sql);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get all batches
$batches = $db->query("SELECT DISTINCT batch FROM students ORDER BY batch DESC")->fetchAll();

// Get all courses for assignment
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
        :root {
            --primary: #0d6efd;
            --sidebar-width: 260px;
        }
        
        body { font-family: 'Inter', sans-serif; background: #f5f6fa; }
        
        .sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: #fff; z-index: 1000;
        }
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
        .progress { height: 6px; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
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
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="students.php"><i class="fas fa-user-graduate me-2"></i>Students</a></li>
                <li class="nav-item"><a class="nav-link" href="lecturers.php"><i class="fas fa-chalkboard-teacher me-2"></i>Lecturers</a></li>
                <li class="nav-item"><a class="nav-link" href="courses.php"><i class="fas fa-book me-2"></i>Courses</a></li>
                <li class="nav-item"><a class="nav-link" href="subjects.php"><i class="fas fa-book-open me-2"></i>Subjects</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-calendar-check me-2"></i>Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="announcements.php"><i class="fas fa-bullhorn me-2"></i>Announcements</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div><h4 class="mb-0"><?php echo $pageTitle; ?></h4></div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </div>
        </nav>
        
        <!-- Flash Messages -->
        <?php 
        $flash = getFlash();
        if ($flash): 
        ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mx-4 mt-4">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Manage Students</h5>
                    <p class="text-muted mb-0">Total: <?php echo $totalStudents; ?> students</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-2"></i>Add Student
                </button>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="px-4 mb-4">
            <div class="card">
                <div class="card-body py-3">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search by ID, Name, or Email" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="batch">
                                <option value="">All Batches</option>
                                <?php foreach ($batches as $b): ?>
                                <option value="<?php echo htmlspecialchars($b['batch']); ?>" <?php echo $batchFilter === $b['batch'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['batch']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary me-2"><i class="fas fa-search me-1"></i>Search</button>
                            <a href="students.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Students Table -->
        <div class="px-4 mb-4">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Batch</th>
                                    <th>Attendance</th>
                                    <th>Percentage</th>
                                    <th>Verify</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No students found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($students as $student): 
                                    $percentage = $student['total_attendance'] > 0 ? round(($student['present_count'] + $student['late_count'] * 0.5) / $student['total_attendance'] * 100, 1) : 0;
                                    $imagePath = $student['image_path'] ?? '';
                                ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $imagePath = $student['image_path'] ?? '';
                                        $imageFullPath = !empty($imagePath) ? __DIR__ . '/../' . ltrim($imagePath, '/') : '';
                                        if (!empty($imagePath) && !empty($imageFullPath) && file_exists($imageFullPath)): 
                                        ?>
                                        <img src="<?php echo BASE_URL . '/' . ltrim($imagePath, '/'); ?>" alt="" class="rounded-circle" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #dee2e6; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        <?php else: ?>
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; font-size: 16px; font-weight: 600; border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                            <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($student['student_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($student['batch']); ?></span></td>
                                    <td>
                                        <small>
                                            <span class="text-success">P:<?php echo $student['present_count']; ?></span> / 
                                            <span class="text-danger">A:<?php echo $student['absent_count']; ?></span> / 
                                            <span class="text-warning">L:<?php echo $student['late_count']; ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; min-width: 60px;">
                                                <div class="progress-bar bg-<?php echo $percentage >= 75 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger'); ?>" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <span class="fw-bold <?php echo $percentage >= 75 ? 'text-success' : ($percentage >= 50 ? 'text-warning' : 'text-danger'); ?>"><?php echo $percentage; ?>%</span>
                                        </div>
                                    </td>
                                    <td><code class="text-primary"><?php echo htmlspecialchars($student['verification_field'] ?? '----'); ?></code></td>
                                    <td><span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($student['status']); ?></span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="assignCourses(<?php echo $student['id']; ?>)" title="Assign Courses">
                                                <i class="fas fa-book"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $student['id']; ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalStudents > $perPage): ?>
                    <div class="px-4 py-3 border-top">
                        <?php echo generatePagination('students.php', $page, ceil($totalStudents / $perPage), ['search' => $search, 'batch' => $batchFilter]); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email (Optional)</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="add_phone" placeholder="e.g., 0771234567" onkeyup="generateVerification('add')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth" id="add_dob" onchange="generateVerification('add')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIC Number</label>
                                <input type="text" class="form-control" name="nic" id="add_nic" placeholder="e.g., 200012345678" onkeyup="generateVerification('add')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Verification Code (Last 4 digits)</label>
                                <input type="text" class="form-control" name="verification_code" id="add_verification_code" placeholder="4 digits" maxlength="4" pattern="[0-9]{4}" required>
                                <small class="text-muted">Used for student login verification</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Batch</label>
                                <input type="text" class="form-control" name="batch" value="2024" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Academic Year</label>
                                <select class="form-select" name="academic_year" required>
                                    <option value="1">Year 1</option>
                                    <option value="2">Year 2</option>
                                    <option value="3">Year 3</option>
                                    <option value="4">Year 4</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Student Photo</label>
                                <input type="file" class="form-control" name="student_image" accept="image/*">
                                <small class="text-muted">Allowed: JPG, PNG, GIF</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Assign Courses</label>
                                <div class="row">
                                    <?php foreach ($courses as $course): ?>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="courses[]" value="<?php echo $course['id']; ?>" id="course<?php echo $course['id']; ?>">
                                            <label class="form-check-label" for="course<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['course_code']); ?> - <?php echo htmlspecialchars($course['course_name']); ?></label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="edit_phone" onkeyup="generateVerification('edit')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Verification Code (Last 4 digits)</label>
                                <input type="text" class="form-control" name="verification_code" id="edit_verification_code" maxlength="4" pattern="[0-9]{4}">
                                <small class="text-muted">Auto-generated from phone/NIC/DOB</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Batch</label>
                                <input type="text" class="form-control" name="batch" id="edit_batch" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Academic Year</label>
                                <select class="form-select" name="academic_year" id="edit_academic_year" required>
                                    <option value="1">Year 1</option>
                                    <option value="2">Year 2</option>
                                    <option value="3">Year 3</option>
                                    <option value="4">Year 4</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="graduated">Graduated</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" id="edit_address" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Assign Courses Modal -->
    <div class="modal fade" id="assignCoursesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-book me-2"></i>Assign Courses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="assign_courses">
                        <input type="hidden" name="student_id" id="assign_student_id">
                        <p class="text-muted mb-3">Select courses for this student:</p>
                        <div class="row" id="coursesList">
                            <?php foreach ($courses as $course): ?>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input course-checkbox" type="checkbox" name="course_ids[]" value="<?php echo $course['id']; ?>">
                                    <label class="form-check-label"><?php echo htmlspecialchars($course['course_code']); ?> - <?php echo htmlspecialchars($course['course_name']); ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Courses</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function generateVerification(prefix) {
            const phone = document.getElementById(prefix + '_phone')?.value || '';
            const nic = document.getElementById(prefix + '_nic')?.value || '';
            const dob = document.getElementById(prefix + '_dob')?.value || '';
            const verField = document.getElementById(prefix + '_verification_code');
            
            if (!verField) return;
            
            let code = '';
            if (phone.length >= 4) {
                code = phone.slice(-4);
            } else if (nic.length >= 4) {
                code = nic.slice(-4);
            } else if (dob) {
                const dobParts = dob.split('-');
                if (dobParts.length === 3) {
                    code = dobParts[2] + dobParts[1];
                }
            }
            
            if (code && code.length === 4 && /^\d{4}$/.test(code)) {
                verField.value = code;
            }
        }
        
        function editStudent(student) {
            document.getElementById('edit_id').value = student.id;
            document.getElementById('edit_full_name').value = student.full_name;
            document.getElementById('edit_email').value = student.email || '';
            document.getElementById('edit_phone').value = student.phone || '';
            document.getElementById('edit_verification_code').value = student.verification_field || '';
            document.getElementById('edit_batch').value = student.batch;
            document.getElementById('edit_academic_year').value = student.academic_year;
            document.getElementById('edit_status').value = student.status;
            document.getElementById('edit_address').value = student.address || '';
            
            new bootstrap.Modal(document.getElementById('editStudentModal')).show();
        }
        
        function assignCourses(studentId) {
            document.getElementById('assign_student_id').value = studentId;
            
            // Reset checkboxes
            document.querySelectorAll('.course-checkbox').forEach(cb => cb.checked = false);
            
            // Fetch current enrollments
            fetch(`../api/get_enrollments.php?student_id=${studentId}`)
                .then(r => r.json())
                .then(data => {
                    data.forEach(courseId => {
                        const cb = document.querySelector(`.course-checkbox[value="${courseId}"]`);
                        if (cb) cb.checked = true;
                    });
                });
            
            new bootstrap.Modal(document.getElementById('assignCoursesModal')).show();
        }
        
        function confirmDelete(id) {
            Swal.fire({
                title: 'Delete Student?',
                text: 'This will permanently delete the student and all their records.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then(result => {
                if (result.isConfirmed) {
                    document.getElementById('delete_id').value = id;
                    document.getElementById('deleteForm').submit();
                }
            });
        }
    </script>
</body>
</html>
