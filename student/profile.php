<?php
/**
 * Student Profile
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireStudentLogin();

$pageTitle = 'My Profile';
$db = getDB();

// Get student from session
$student = [
    'id' => $_SESSION['student_id'],
    'student_id' => $_SESSION['student_number'],
    'full_name' => $_SESSION['student_name'],
    'email' => $_SESSION['student_email'] ?? '',
    'batch' => ''
];

$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['student_id']]);
$studentData = $stmt->fetch();
if ($studentData) {
    $student = array_merge($student, $studentData);
}

// Get enrolled courses
$query = "
    SELECT c.course_code, c.course_name 
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ? AND c.status = 'active'
";
$stmt = $db->prepare($query);
$stmt->execute([$student['id']]);
$courses = $stmt->fetchAll();

// Get total attendance stats
$query2 = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent
    FROM attendance_records
    WHERE student_id = ?
";
$stmt2 = $db->prepare($query2);
$stmt2->execute([$student['id']]);
$stats = $stmt2->fetch();

$percentage = $stats['total'] > 0 ? round($stats['present'] / $stats['total'] * 100, 1) : 0;
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
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        
        /* Modern Profile Image Styles */
        .profile-image-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-image-xl {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }
        
        .profile-image-placeholder-xl {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 56px;
            font-weight: 700;
            color: #fff;
            border: 5px solid #fff;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }
        
        .profile-status-dot {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #198754;
            border: 4px solid #fff;
        }
        
        .profile-header-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .profile-name {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.75rem;
            color: #1a1a2e;
            margin-top: 20px;
            margin-bottom: 8px;
        }
        
        .profile-id {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(25, 135, 84, 0.1) 100%);
            color: #0d6efd;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .profile-stat-item {
            text-align: center;
        }
        
        .profile-stat-value {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .profile-stat-label {
            font-size: 0.85rem;
            color: #666;
            margin-top: 4px;
        }
        
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand"><h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4><small>Student Portal</small></div>
        <nav class="sidebar-menu mt-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-calendar-check me-2"></i>My Attendance</a></li>
                <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
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
            <h5 class="mb-1"><i class="fas fa-id-card me-2"></i>Student Information</h5>
        </div>
        
        <div class="px-4 mb-4">
            <div class="row g-4">
                <!-- Profile Card -->
                <div class="col-lg-4">
                    <div class="profile-header-card">
                        <div class="profile-image-container">
                            <?php 
                            $studentImage = $student['image_path'] ?? '';
                            $imageFullPath = !empty($studentImage) ? __DIR__ . '/../' . ltrim($studentImage, '/') : '';
                            if (!empty($studentImage) && !empty($imageFullPath) && file_exists($imageFullPath)): 
                            ?>
                            <img src="<?php echo BASE_URL . '/' . ltrim($studentImage, '/'); ?>" alt="<?php echo htmlspecialchars($student['full_name']); ?>" class="profile-image-xl">
                            <?php else: ?>
                            <div class="profile-image-placeholder-xl"><?php echo strtoupper(substr($student['full_name'], 0, 1)); ?></div>
                            <?php endif; ?>
                            <span class="profile-status-dot"></span>
                        </div>
                        <h3 class="profile-name"><?php echo htmlspecialchars($student['full_name']); ?></h3>
                        <span class="profile-id"><?php echo htmlspecialchars($student['student_id']); ?></span>
                        <div class="mt-3">
                            <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'secondary'; ?> fs-6"><?php echo ucfirst($student['status'] ?? 'Active'); ?></span>
                        </div>
                        <div class="profile-stats">
                            <div class="profile-stat-item">
                                <div class="profile-stat-value"><?php echo (int)($stats['total'] ?? 0); ?></div>
                                <div class="profile-stat-label">Total Sessions</div>
                            </div>
                            <div class="profile-stat-item">
                                <div class="profile-stat-value"><?php echo $stats['present']; ?></div>
                                <div class="profile-stat-label">Present</div>
                            </div>
                            <div class="profile-stat-item">
                                <div class="profile-stat-value"><?php echo $percentage; ?>%</div>
                                <div class="profile-stat-label">Attendance</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Details Card -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><i class="fas fa-user me-2"></i>Personal Information</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Full Name</label>
                                    <p class="mb-0 fw-medium"><?php echo htmlspecialchars($student['full_name']); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Student ID</label>
                                    <p class="mb-0 fw-medium"><code><?php echo htmlspecialchars($student['student_id']); ?></code></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Email</label>
                                    <p class="mb-0 fw-medium"><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Phone</label>
                                    <p class="mb-0 fw-medium"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Batch</label>
                                    <p class="mb-0 fw-medium"><?php echo htmlspecialchars($student['batch']); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Academic Year</label>
                                    <p class="mb-0 fw-medium">Year <?php echo $student['academic_year']; ?></p>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="text-muted small">Address</label>
                                    <p class="mb-0 fw-medium"><?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enrolled Courses -->
                    <div class="card mt-4">
                        <div class="card-header"><i class="fas fa-book me-2"></i>Enrolled Courses</div>
                        <div class="card-body">
                            <?php if (empty($courses)): ?>
                            <p class="text-muted mb-0">No courses enrolled yet.</p>
                            <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($courses as $course): ?>
                                <div class="col-md-6">
                                    <div class="p-2 bg-light rounded">
                                        <strong><?php echo htmlspecialchars($course['course_code']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($course['course_name']); ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</body>
</html>
