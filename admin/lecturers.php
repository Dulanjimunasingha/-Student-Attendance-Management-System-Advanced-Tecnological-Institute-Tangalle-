<?php
/**
 * Lecturer Management
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Ensure all required tables exist
$db = getDB();
ensureAuthSchema($db);
ensureStudentsTable($db);

$pageTitle = 'Lecturer Management';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $fullName = trim($_POST['full_name'] ?? '');
                $email = strtolower(trim($_POST['email'] ?? ''));
                $phone = trim($_POST['phone'] ?? '');
                $department = trim($_POST['department'] ?? '');
                $qualification = trim($_POST['qualification'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $department === '') {
                    setFlash('error', 'Please provide a valid name, email, and department');
                    redirect('lecturers.php');
                }
                
                if ($password === '' || $confirmPassword === '') {
                    setFlash('error', 'Password is required');
                    redirect('lecturers.php');
                }
                
                if ($password !== $confirmPassword) {
                    setFlash('error', 'Passwords do not match');
                    redirect('lecturers.php');
                }
                
                if (strlen($password) < 6) {
                    setFlash('error', 'Password must be at least 6 characters');
                    redirect('lecturers.php');
                }
                
                $lecturerId = generateLecturerId();
                $usernameBase = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '.', $fullName), '.'));
                $username = $usernameBase !== '' ? $usernameBase : strtolower($lecturerId);
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $imagePath = null;
                if (isset($_FILES['lecturer_image']) && $_FILES['lecturer_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/lecturers/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['lecturer_image']['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($ext, $allowedExts)) {
                        $newFileName = $lecturerId . '_' . time() . '.' . $ext;
                        $targetPath = $uploadDir . $newFileName;
                        if (move_uploaded_file($_FILES['lecturer_image']['tmp_name'], $targetPath)) {
                            $imagePath = 'uploads/lecturers/' . $newFileName;
                        }
                    }
                }
                
                $transactionStarted = false;
                try {
                    $db->beginTransaction();
                    $transactionStarted = true;

                    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn()) {
                        throw new RuntimeException('A user with this email already exists');
                    }

                    $usernameCandidate = $username;
                    $suffix = 1;
                    do {
                        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                        $stmt->execute([$usernameCandidate]);
                        $usernameExists = (bool)$stmt->fetchColumn();
                        if ($usernameExists) {
                            $usernameCandidate = $username . $suffix;
                            $suffix++;
                        }
                    } while ($usernameExists);
                    $username = $usernameCandidate;

                    $stmt = $db->query("SELECT id FROM roles WHERE role_slug = 'lecturer' LIMIT 1");
                    $lecturerRoleId = $stmt->fetchColumn();
                    if (!$lecturerRoleId) {
                        throw new RuntimeException('Lecturer role is not configured');
                    }

                    $stmt = $db->prepare("
                        INSERT INTO users (username, email, password, role_id, status, must_change_password) 
                        VALUES (?, ?, ?, ?, 'active', FALSE)
                    ");
                    $stmt->execute([$username, $email, $hashedPassword, $lecturerRoleId]);
                    $userId = $db->lastInsertId();
                    
                    $stmt = $db->prepare("
                        INSERT INTO lecturers (lecturer_id, full_name, email, phone, department, qualification, status, user_id, image_path) 
                        VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?)
                    ");
                    $stmt->execute([$lecturerId, $fullName, $email, $phone, $department, $qualification, $userId, $imagePath]);
                    
                    $db->commit();
                    $transactionStarted = false;
                    logActivity($_SESSION['user_id'], 'Lecturer Added', "Added lecturer: $lecturerId - $fullName");
                    setFlash('success', 'Lecturer added successfully!');
                } catch (PDOException | RuntimeException $e) {
                    if ($transactionStarted && $db->inTransaction()) {
                        $db->rollBack();
                    }
                    if ($imagePath !== null) {
                        $uploadedImage = __DIR__ . '/../' . $imagePath;
                        if (is_file($uploadedImage)) {
                            unlink($uploadedImage);
                        }
                    }
                    $message = $e->getMessage();
                    if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
                        $message = 'This email or lecturer ID is already in use. Please use different details.';
                    }
                    setFlash('error', $message);
                }
                redirect('lecturers.php');
                break;
                
            case 'update':
                $id = (int)$_POST['id'];
                $fullName = trim($_POST['full_name'] ?? '');
                $email = strtolower(trim($_POST['email'] ?? ''));
                $phone = trim($_POST['phone'] ?? '');
                $department = trim($_POST['department'] ?? '');
                $qualification = trim($_POST['qualification'] ?? '');
                $status = trim($_POST['status'] ?? '');

                if ($id < 1 || $fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                    $department === '' || !in_array($status, ['active', 'inactive'], true)) {
                    setFlash('error', 'Please provide valid lecturer details');
                    redirect('lecturers.php');
                }
                
                $imagePath = null;
                $keepExisting = true;
                $oldImagePath = null;
                
                if (isset($_FILES['lecturer_image']) && $_FILES['lecturer_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/lecturers/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['lecturer_image']['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    $stmt = $db->prepare("SELECT lecturer_id, image_path FROM lecturers WHERE id = ?");
                    $stmt->execute([$id]);
                    $oldLecturer = $stmt->fetch();
                    if (!$oldLecturer) {
                        setFlash('error', 'Lecturer not found');
                        redirect('lecturers.php');
                    }
                    
                    if (in_array($ext, $allowedExts)) {
                        $newFileName = $oldLecturer['lecturer_id'] . '_' . time() . '.' . $ext;
                        $targetPath = $uploadDir . $newFileName;
                        if (move_uploaded_file($_FILES['lecturer_image']['tmp_name'], $targetPath)) {
                            $imagePath = 'uploads/lecturers/' . $newFileName;
                            $keepExisting = false;
                            $oldImagePath = $oldLecturer['image_path'];
                        }
                    }
                }
                
                $transactionStarted = false;
                try {
                    $db->beginTransaction();
                    $transactionStarted = true;

                    $stmt = $db->prepare("
                        SELECT l.user_id
                        FROM lecturers l
                        WHERE l.id = ?
                        FOR UPDATE
                    ");
                    $stmt->execute([$id]);
                    $lecturer = $stmt->fetch();
                    if (!$lecturer) {
                        throw new RuntimeException('Lecturer not found');
                    }

                    if ($lecturer['user_id']) {
                        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
                        $stmt->execute([$email, $lecturer['user_id']]);
                        if ($stmt->fetchColumn()) {
                            throw new RuntimeException('A user with this email already exists');
                        }
                    }

                    if ($keepExisting) {
                        $stmt = $db->prepare("
                            UPDATE lecturers 
                            SET full_name = ?, email = ?, phone = ?, department = ?, qualification = ?, status = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$fullName, $email, $phone, $department, $qualification, $status, $id]);
                    } else {
                        $stmt = $db->prepare("
                            UPDATE lecturers 
                            SET full_name = ?, email = ?, phone = ?, department = ?, qualification = ?, status = ?, image_path = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$fullName, $email, $phone, $department, $qualification, $status, $imagePath, $id]);
                    }

                    if ($lecturer['user_id']) {
                        $stmt = $db->prepare("UPDATE users SET email = ?, status = ? WHERE id = ?");
                        $stmt->execute([$email, $status, $lecturer['user_id']]);
                    }
                    
                    $db->commit();
                    $transactionStarted = false;
                    if (!$keepExisting && !empty($oldImagePath)) {
                        $oldImage = __DIR__ . '/../' . ltrim($oldImagePath, '/');
                        if (is_file($oldImage)) {
                            unlink($oldImage);
                        }
                    }
                    logActivity($_SESSION['user_id'], 'Lecturer Updated', "Updated lecturer ID: $id");
                    setFlash('success', 'Lecturer updated successfully!');
                } catch (PDOException | RuntimeException $e) {
                    if ($transactionStarted && $db->inTransaction()) {
                        $db->rollBack();
                    }
                    if (!$keepExisting && $imagePath !== null) {
                        $newImage = __DIR__ . '/../' . ltrim($imagePath, '/');
                        if (is_file($newImage)) {
                            unlink($newImage);
                        }
                    }
                    setFlash('error', $e->getMessage());
                }
                redirect('lecturers.php');
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $db->prepare("SELECT * FROM lecturers WHERE id = ?");
                    $stmt->execute([$id]);
                    $lecturer = $stmt->fetch();
                    
                    if ($lecturer) {
                        $stmt = $db->prepare("DELETE FROM lecturer_course_assignments WHERE lecturer_id = ?");
                        $stmt->execute([$id]);
                        
                        $stmt = $db->prepare("DELETE FROM lecturers WHERE id = ?");
                        $stmt->execute([$id]);
                        
                        if ($lecturer['user_id']) {
                            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                            $stmt->execute([$lecturer['user_id']]);
                        }
                        
                        logActivity($_SESSION['user_id'], 'Lecturer Deleted', "Deleted lecturer: " . $lecturer['lecturer_id']);
                        setFlash('success', 'Lecturer deleted successfully!');
                    }
                } catch (PDOException $e) {
                    setFlash('error', 'Failed to delete lecturer');
                }
                redirect('lecturers.php');
                break;
                
            case 'assign_courses':
                $lecturerId = (int)$_POST['lecturer_id'];
                $courseIds = $_POST['course_ids'] ?? [];
                
                $stmt = $db->prepare("DELETE FROM lecturer_course_assignments WHERE lecturer_id = ?");
                $stmt->execute([$lecturerId]);
                
                foreach ($courseIds as $courseId) {
                    $stmt = $db->prepare("INSERT INTO lecturer_course_assignments (lecturer_id, course_id, assigned_date) VALUES (?, ?, CURDATE())");
                    $stmt->execute([$lecturerId, $courseId]);
                }
                
                logActivity($_SESSION['user_id'], 'Courses Assigned', "Assigned courses to lecturer ID: $lecturerId");
                setFlash('success', 'Courses assigned successfully!');
                redirect('lecturers.php');
                break;
                
            case 'reset_password':
                $id = (int)$_POST['id'];
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                $forceChange = isset($_POST['force_change']) ? true : false;
                
                if (empty($newPassword) || empty($confirmPassword)) {
                    setFlash('error', 'Password is required');
                    redirect('lecturers.php');
                }
                
                if ($newPassword !== $confirmPassword) {
                    setFlash('error', 'Passwords do not match');
                    redirect('lecturers.php');
                }
                
                if (strlen($newPassword) < 6) {
                    setFlash('error', 'Password must be at least 6 characters');
                    redirect('lecturers.php');
                }
                
                try {
                    $stmt = $db->prepare("SELECT user_id FROM lecturers WHERE id = ?");
                    $stmt->execute([$id]);
                    $lecturer = $stmt->fetch();
                    
                    if ($lecturer && $lecturer['user_id']) {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET password = ?, must_change_password = ?, failed_login_attempts = 0, lock_until = NULL WHERE id = ?");
                        $stmt->execute([$hashedPassword, $forceChange ? TRUE : FALSE, $lecturer['user_id']]);
                        
                        logActivity($_SESSION['user_id'], 'Password Reset', "Reset password for lecturer ID: $id");
                        setFlash('success', 'Password reset successfully!' . ($forceChange ? ' Lecturer will be forced to change password on next login.' : ''));
                    }
                } catch (PDOException $e) {
                    setFlash('error', 'Failed to reset password');
                }
                redirect('lecturers.php');
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
$deptFilter = sanitize($_GET['department'] ?? '');

$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (l.lecturer_id LIKE ? OR l.full_name LIKE ? OR l.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($deptFilter) {
    $where .= " AND l.department = ?";
    $params[] = $deptFilter;
}

$countSql = "SELECT COUNT(*) as count FROM lecturers l $where";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalLecturers = $stmt->fetch()['count'];

$sql = "
    SELECT l.*, COUNT(DISTINCT lca.course_id) as courses_count
    FROM lecturers l
    LEFT JOIN lecturer_course_assignments lca ON l.id = lca.lecturer_id
    $where
    GROUP BY l.id
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$lecturers = $stmt->fetchAll();

$departments = $db->query("SELECT DISTINCT department FROM lecturers WHERE department IS NOT NULL ORDER BY department")->fetchAll();
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
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand"><h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4><small>Attendance System</small></div>
        <nav class="sidebar-menu mt-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="students.php"><i class="fas fa-user-graduate me-2"></i>Students</a></li>
                <li class="nav-item"><a class="nav-link active" href="lecturers.php"><i class="fas fa-chalkboard-teacher me-2"></i>Lecturers</a></li>
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
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
        </nav>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mx-4 mt-4">
            <?php echo htmlspecialchars($flash['message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div><h5 class="mb-1">Manage Lecturers</h5><p class="text-muted mb-0">Total: <?php echo $totalLecturers; ?> lecturers</p></div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLecturerModal"><i class="fas fa-plus me-2"></i>Add Lecturer</button>
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
                            <select class="form-select" name="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $d): ?>
                                <option value="<?php echo htmlspecialchars($d['department']); ?>" <?php echo $deptFilter === $d['department'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['department']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary me-2"><i class="fas fa-search me-1"></i>Search</button>
                            <a href="lecturers.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Lecturers Table -->
        <div class="px-4 mb-4">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Lecturer ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Qualification</th>
                                    <th>Courses</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lecturers)): ?>
                                <tr><td colspan="9" class="text-center py-5"><i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i><p class="text-muted">No lecturers found</p></td></tr>
                                <?php else: ?>
                                <?php foreach ($lecturers as $lecturer): 
                                    $lecturerImage = $lecturer['image_path'] ?? '';
                                ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $lecturerImage = $lecturer['image_path'] ?? '';
                                        $imageFullPath = !empty($lecturerImage) ? __DIR__ . '/../' . ltrim($lecturerImage, '/') : '';
                                        if (!empty($lecturerImage) && !empty($imageFullPath) && file_exists($imageFullPath)): 
                                        ?>
                                        <img src="<?php echo BASE_URL . '/' . ltrim($lecturerImage, '/'); ?>" alt="" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #dee2e6;">
                                        <?php else: ?>
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 14px; font-weight: 600;">
                                            <?php echo strtoupper(substr($lecturer['full_name'], 0, 1)); ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($lecturer['lecturer_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($lecturer['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lecturer['email']); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($lecturer['department']); ?></span></td>
                                    <td><?php echo htmlspecialchars($lecturer['qualification']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $lecturer['courses_count']; ?> courses</span></td>
                                    <td><span class="badge bg-<?php echo $lecturer['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($lecturer['status']); ?></span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editLecturer(<?php echo htmlspecialchars(json_encode($lecturer)); ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-outline-warning" onclick="resetPassword(<?php echo $lecturer['id']; ?>)" title="Reset Password"><i class="fas fa-key"></i></button>
                                            <button class="btn btn-outline-success" onclick="assignCourses(<?php echo $lecturer['id']; ?>)" title="Assign Courses"><i class="fas fa-book"></i></button>
                                            <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $lecturer['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalLecturers > $perPage): ?>
                    <div class="px-4 py-3 border-top"><?php echo generatePagination('lecturers.php', $page, ceil($totalLecturers / $perPage), ['search' => $search, 'department' => $deptFilter]); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Lecturer Modal -->
    <div class="modal fade" id="addLecturerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Lecturer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label><input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label><input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label><input type="text" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Information Technology">Information Technology</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Physics">Physics</option>
                                    <option value="Engineering">Engineering</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Qualification</label><input type="text" class="form-control" name="qualification" placeholder="e.g., PhD in Computer Science">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label><input type="password" class="form-control" name="password" required minlength="6" placeholder="At least 6 characters">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label><input type="password" class="form-control" name="confirm_password" required minlength="6" placeholder="Confirm password">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" name="lecturer_image" accept="image/*">
                                <small class="text-muted">Allowed: JPG, PNG, GIF, WebP</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Lecturer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Lecturer Modal -->
    <div class="modal fade" id="editLecturerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Lecturer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update"><input type="hidden" name="id" id="edit_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label><input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label><input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label><input type="text" class="form-control" name="phone" id="edit_phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department" id="edit_department" required>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Information Technology">Information Technology</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Physics">Physics</option>
                                    <option value="Engineering">Engineering</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Qualification</label><input type="text" class="form-control" name="qualification" id="edit_qualification">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" name="lecturer_image" accept="image/*">
                                <small class="text-muted">Leave empty to keep existing photo. Allowed: JPG, PNG, GIF, WebP</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Lecturer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Assign Courses Modal -->
    <div class="modal fade" id="assignCoursesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-book me-2"></i>Assign Courses</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="assign_courses"><input type="hidden" name="lecturer_id" id="assign_lecturer_id">
                        <p class="text-muted mb-3">Select courses for this lecturer:</p>
                        <div class="row" id="lecturerCoursesList">
                            <?php foreach ($courses as $course): ?>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input lecturer-course-checkbox" type="checkbox" name="course_ids[]" value="<?php echo $course['id']; ?>">
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
    
    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" id="reset_lecturer_id">
                        <div class="mb-3">
                            <label class="form-label">New Password</label><input type="password" class="form-control" name="new_password" id="reset_new_password" required minlength="6" placeholder="At least 6 characters">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label><input type="password" class="form-control" name="confirm_password" id="reset_confirm_password" required minlength="6" placeholder="Confirm password">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="force_change" value="1" id="force_change">
                            <label class="form-check-label" for="force_change">Force lecturer to change password on next login</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-key me-2"></i>Reset Password</button>
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
        function editLecturer(lecturer) {
            document.getElementById('edit_id').value = lecturer.id;
            document.getElementById('edit_full_name').value = lecturer.full_name;
            document.getElementById('edit_email').value = lecturer.email || '';
            document.getElementById('edit_phone').value = lecturer.phone || '';
            document.getElementById('edit_department').value = lecturer.department;
            document.getElementById('edit_qualification').value = lecturer.qualification || '';
            document.getElementById('edit_status').value = lecturer.status;
            new bootstrap.Modal(document.getElementById('editLecturerModal')).show();
        }
        
        function assignCourses(lecturerId) {
            document.getElementById('assign_lecturer_id').value = lecturerId;
            document.querySelectorAll('.lecturer-course-checkbox').forEach(cb => cb.checked = false);
            fetch(`../api/get_lecturer_courses.php?lecturer_id=${lecturerId}`)
                .then(r => r.json())
                .then(data => { data.forEach(id => { const cb = document.querySelector(`.lecturer-course-checkbox[value="${id}"]`); if (cb) cb.checked = true; }); });
            new bootstrap.Modal(document.getElementById('assignCoursesModal')).show();
        }
        
        function resetPassword(lecturerId) {
            document.getElementById('reset_lecturer_id').value = lecturerId;
            document.getElementById('reset_new_password').value = '';
            document.getElementById('reset_confirm_password').value = '';
            document.getElementById('force_change').checked = false;
            new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        }
        
        function confirmDelete(id) {
            Swal.fire({ title: 'Delete Lecturer?', text: 'This will permanently delete the lecturer.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete' })
                .then(result => { if (result.isConfirmed) { document.getElementById('delete_id').value = id; document.getElementById('deleteForm').submit(); } });
        }
    </script>
</body>
</html>
