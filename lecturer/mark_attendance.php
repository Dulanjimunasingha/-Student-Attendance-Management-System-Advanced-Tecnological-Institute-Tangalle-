<?php
/**
 * Mark Attendance
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

$pageTitle = 'Mark Attendance';
 
$user = getCurrentUser();
$stmt = $db->prepare("SELECT * FROM lecturers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$lecturer = $stmt->fetch();
$lecturerId = $lecturer['id'];

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
    $courseId = (int)$_POST['course_id'];
    $sessionDate = sanitize($_POST['session_date']);
    $lessonTopic = sanitize($_POST['lesson_topic']);
    $notes = sanitize($_POST['notes']);
    $attendance = $_POST['attendance'] ?? [];
    
    try {
        // Check if session exists
        $stmt = $db->prepare("SELECT id FROM attendance_sessions WHERE course_id = ? AND session_date = ?");
        $stmt->execute([$courseId, $sessionDate]);
        $existingSession = $stmt->fetch();
        
        if ($existingSession) {
            $sessionId = $existingSession['id'];
            // Update session
            $stmt = $db->prepare("UPDATE attendance_sessions SET lesson_topic = ?, notes = ? WHERE id = ?");
            $stmt->execute([$lessonTopic, $notes, $sessionId]);
            
            // Delete existing records
            $stmt = $db->prepare("DELETE FROM attendance_records WHERE session_id = ?");
            $stmt->execute([$sessionId]);
        } else {
            // Create new session
            $stmt = $db->prepare("INSERT INTO attendance_sessions (course_id, lecturer_id, session_date, lesson_topic, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$courseId, $lecturerId, $sessionDate, $lessonTopic, $notes]);
            $sessionId = $db->lastInsertId();
        }
        
        // Insert attendance records
        $stmt = $db->prepare("INSERT INTO attendance_records (session_id, student_id, attendance_status, marked_by) VALUES (?, ?, ?, ?)");
        foreach ($attendance as $studentId => $status) {
            $stmt->execute([$sessionId, $studentId, $status, $_SESSION['user_id']]);
        }
        
        logActivity($_SESSION['user_id'], 'Attendance Marked', "Marked attendance for course ID: $courseId on $sessionDate");
        setFlash('success', 'Attendance marked successfully!');
        redirect('mark_attendance.php');
    } catch (PDOException $e) {
        setFlash('error', 'Failed to mark attendance: ' . $e->getMessage());
    }
}

// Get assigned courses
$courses = getLecturerCourses($lecturerId);

$selectedCourse = isset($_POST['course_id']) ? (int)$_POST['course_id'] : (isset($_GET['course']) ? (int)$_GET['course'] : 0);
$selectedDate = isset($_POST['session_date']) ? $_POST['session_date'] : (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'));
$students = [];
$existingRecords = [];

if ($selectedCourse) {
    // Get students enrolled in selected course
    $stmt = $db->prepare("
        SELECT s.*, e.enrolled_date 
        FROM students s 
        JOIN enrollments e ON s.id = e.student_id 
        WHERE e.course_id = ? AND s.status = 'active'
        ORDER BY s.full_name
    ");
    $stmt->execute([$selectedCourse]);
    $students = $stmt->fetchAll();
    
    // Get existing records for this session
    $stmt = $db->prepare("
        SELECT ar.*, ar.student_id as sid 
        FROM attendance_records ar 
        JOIN attendance_sessions ass ON ar.session_id = ass.id 
        WHERE ass.course_id = ? AND ass.session_date = ?
    ");
    $stmt->execute([$selectedCourse, $selectedDate]);
    $existingRecords = $stmt->fetchAll(PDO::FETCH_UNIQUE);
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
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .attendance-btn { min-width: 90px; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand"><h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4><small>Lecturer Portal</small></div>
        <nav class="sidebar-menu mt-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="mark_attendance.php"><i class="fas fa-clipboard-check me-2"></i>Mark Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance_history.php"><i class="fas fa-history me-2"></i>Attendance History</a></li>
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
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mx-4 mt-4"><?php echo htmlspecialchars($flash['message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <div class="page-header">
            <h5 class="mb-1"><i class="fas fa-clipboard-check me-2"></i>Mark Student Attendance</h5>
            <p class="text-muted mb-0">Select a course and date to mark attendance</p>
        </div>
        
        <div class="px-4 mb-4">
            <!-- Course & Date Selection -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="POST" class="row g-3" id="selectionForm">
                        <input type="hidden" name="action" value="select_course">
                        <div class="col-md-6">
                            <label class="form-label">Select Course</label>
                            <select class="form-select" name="course_id" id="courseSelect" required onchange="document.getElementById('selectionForm').submit()">
                                <option value="">-- Select Course --</option>
                                <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo $selectedCourse == $course['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($course['course_code']); ?> - <?php echo htmlspecialchars($course['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Session Date</label>
                            <input type="date" class="form-control" name="session_date" value="<?php echo $selectedDate; ?>" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Load</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($selectedCourse && !empty($students)): ?>
            <!-- Attendance Form -->
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="action" value="mark_attendance">
                <input type="hidden" name="course_id" value="<?php echo $selectedCourse; ?>">
                <input type="hidden" name="session_date" value="<?php echo $selectedDate; ?>">
                
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <i class="fas fa-info-circle me-2"></i>Lesson Details
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Topic / Lesson Taught Today *</label>
                                <input type="text" class="form-control" name="lesson_topic" placeholder="Enter the topic covered today" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Notes (Optional)</label>
                                <input type="text" class="form-control" name="notes" placeholder="Any additional notes">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-users me-2"></i>Students (<?php echo count($students); ?>)</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-success" onclick="selectAll('present')">All Present</button>
                            <button type="button" class="btn btn-outline-danger" onclick="selectAll('absent')">All Absent</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th class="text-center">Attendance Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): 
                                        $currentStatus = $existingRecords[$student['id']]['attendance_status'] ?? 'present';
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><code><?php echo htmlspecialchars($student['student_id']); ?></code></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="present_<?php echo $student['id']; ?>" value="present" <?php echo $currentStatus === 'present' ? 'checked' : ''; ?> autocomplete="off">
                                                <label class="btn btn-outline-success btn-sm attendance-btn" for="present_<?php echo $student['id']; ?>"><i class="fas fa-check me-1"></i>Present</label>
                                                
                                                <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="absent_<?php echo $student['id']; ?>" value="absent" <?php echo $currentStatus === 'absent' ? 'checked' : ''; ?> autocomplete="off">
                                                <label class="btn btn-outline-danger btn-sm attendance-btn" for="absent_<?php echo $student['id']; ?>"><i class="fas fa-times me-1"></i>Absent</label>
                                                
                                                <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="late_<?php echo $student['id']; ?>" value="late" <?php echo $currentStatus === 'late' ? 'checked' : ''; ?> autocomplete="off">
                                                <label class="btn btn-outline-warning btn-sm attendance-btn" for="late_<?php echo $student['id']; ?>"><i class="fas fa-clock me-1"></i>Late</label>
                                                
                                                <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="excused_<?php echo $student['id']; ?>" value="excused" <?php echo $currentStatus === 'excused' ? 'checked' : ''; ?> autocomplete="off">
                                                <label class="btn btn-outline-info btn-sm attendance-btn" for="excused_<?php echo $student['id']; ?>"><i class="fas fa-sticky-note me-1"></i>Excused</label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <div class="text-muted"><i class="fas fa-info-circle me-1"></i>Select the appropriate attendance status for each student</div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Save Attendance</button>
                        </div>
                    </div>
                </div>
            </form>
            <?php elseif ($selectedCourse && empty($students)): ?>
            <div class="card text-center py-5">
                <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                <h5>No Students Enrolled</h5>
                <p class="text-muted">There are no students enrolled in this course yet.</p>
            </div>
            <?php else: ?>
            <div class="card text-center py-5">
                <i class="fas fa-hand-pointer fa-4x text-primary mb-3"></i>
                <h5>Select a Course</h5>
                <p class="text-muted">Please select a course from the dropdown above to mark attendance.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script>
        function selectAll(status) {
            const buttons = document.querySelectorAll(`input[name^="attendance"]`);
            buttons.forEach(btn => {
                if (btn.value === status) {
                    btn.checked = true;
                }
            });
        }
    </script>
</body>
</html>
