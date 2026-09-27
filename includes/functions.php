<?php
/**
 * Helper Functions
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

/**
 * Format date with time
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($datetime));
}

/**
 * Calculate attendance percentage
 */
function calculateAttendancePercentage($present, $absent, $late, $excused, $courseMin = null) {
    $total = $present + $absent + $late + $excused;
    
    if ($total == 0) {
        return 0;
    }
    
    // Calculate effective attendance
    // Late counts as 50% by default
    $lateValue = $late * (LATE_COUNT_PERCENTAGE / 100);
    
    // Excused counts fully by default
    $excusedValue = EXCUSED_COUNT_FULL ? $excused : 0;
    
    $effectiveAttendance = $present + $lateValue + $excusedValue;
    
    return round(($effectiveAttendance / $total) * 100, 2);
}

/**
 * Get attendance status based on percentage
 */
function getAttendanceStatus($percentage, $minRequired = null) {
    if ($minRequired === null) {
        $minRequired = DEFAULT_MIN_ATTENDANCE;
    }
    
    if ($percentage >= $minRequired) {
        return [
            'status' => 'completed',
            'class' => 'success',
            'label' => 'Completed'
        ];
    } else {
        return [
            'status' => 'not_completed',
            'class' => 'danger',
            'label' => 'At Risk'
        ];
    }
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'suspended' => '<span class="badge bg-danger">Suspended</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'not_completed' => '<span class="badge bg-danger">At Risk</span>',
        'in_progress' => '<span class="badge bg-info">In Progress</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Get attendance status badge
 */
function getAttendanceStatusBadge($percentage, $minRequired = null) {
    $status = getAttendanceStatus($percentage, $minRequired);
    $class = $status['class'];
    $label = $status['label'];
    return "<span class='badge bg-{$class}'>{$label}</span>";
}

/**
 * Flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message and clear
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if student is logged in (verified)
 */
function isStudentLoggedIn() {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']) && 
           isset($_SESSION['student_verified']) && $_SESSION['student_verified'] === true;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, r.role_name, r.role_slug 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Check user role
 */
function hasRole($roleSlug) {
    $user = getCurrentUser();
    return $user && $user['role_slug'] === $roleSlug;
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Redirect back with message
 */
function redirectBack($message = null, $type = 'info') {
    if ($message) {
        setFlash($type, $message);
    }
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            setFlash('warning', 'Session expired. Please login again.');
            redirect('auth/login.php');
        }
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please login to access this page.');
        redirect(BASE_URL . '/auth/login.php');
    }
    checkSessionTimeout();
}

/**
 * Require specific role
 */
function requireRole($roleSlug) {
    requireLogin();
    if (!hasRole($roleSlug)) {
        setFlash('error', 'Access denied. You do not have permission to access this page.');
        redirect('../index.php');
    }
}

/**
 * Paginate results
 */
function paginate($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => ($currentPage - 1) * $perPage
    ];
}

/**
 * Generate pagination links
 */
function generatePagination($baseUrl, $currentPage, $totalPages, $params = []) {
    $html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $params['page'] = $currentPage - 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $range = 2;
    $start = max(1, $currentPage - $range);
    $end = min($totalPages, $currentPage + $range);
    
    if ($start > 1) {
        $params['page'] = 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $params['page'] = $i;
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">' . $i . '</a></li>';
        }
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $params['page'] = $totalPages;
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $params['page'] = $currentPage + 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Get student by ID
 */
function getStudentById($studentId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ? OR student_id = ?");
    $stmt->execute([$studentId, $studentId]);
    return $stmt->fetch();
}

/**
 * Get lecturer by ID
 */
function getLecturerById($lecturerId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM lecturers WHERE id = ? OR lecturer_id = ?");
    $stmt->execute([$lecturerId, $lecturerId]);
    return $stmt->fetch();
}

/**
 * Get course by ID
 */
function getCourseById($courseId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    return $stmt->fetch();
}

/**
 * Get enrolled courses for a student
 */
function getStudentCourses($studentId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*, e.enrolled_date 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.student_id = ? AND c.status = 'active' 
        ORDER BY c.course_name
    ");
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

/**
 * Get courses assigned to a lecturer
 */
function getLecturerCourses($lecturerId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*, lca.assigned_date 
        FROM lecturer_course_assignments lca 
        JOIN courses c ON lca.course_id = c.id 
        WHERE lca.lecturer_id = ? AND c.status = 'active' 
        ORDER BY c.course_name
    ");
    $stmt->execute([$lecturerId]);
    return $stmt->fetchAll();
}

/**
 * Get students enrolled in a course
 */
function getCourseStudents($courseId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.*, e.enrolled_date 
        FROM enrollments e 
        JOIN students s ON e.student_id = s.id 
        WHERE e.course_id = ? AND s.status = 'active' 
        ORDER BY s.full_name
    ");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}

/**
 * Get attendance statistics for a student in a course
 */
function getStudentCourseAttendance($studentId, $courseId) {
    $db = getDB();
    
    // Get total sessions
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM attendance_sessions 
        WHERE course_id = ? AND session_date <= CURDATE()
    ");
    $stmt->execute([$courseId]);
    $total = $stmt->fetch()['total'];
    
    if ($total == 0) {
        return [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'percentage' => 0
        ];
    }
    
    // Get attendance records
    $stmt = $db->prepare("
        SELECT ar.attendance_status, COUNT(*) as count 
        FROM attendance_records ar 
        JOIN attendance_sessions ass ON ar.session_id = ass.id 
        WHERE ar.student_id = ? AND ass.course_id = ? 
        GROUP BY ar.attendance_status
    ");
    $stmt->execute([$studentId, $courseId]);
    $records = $stmt->fetchAll();
    
    $stats = [
        'total' => $total,
        'present' => 0,
        'absent' => 0,
        'late' => 0,
        'excused' => 0,
        'percentage' => 0
    ];
    
    foreach ($records as $record) {
        $stats[$record['attendance_status']] = $record['count'];
    }
    
    $stats['percentage'] = calculateAttendancePercentage(
        $stats['present'],
        $stats['absent'],
        $stats['late'],
        $stats['excused']
    );
    
    return $stats;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = null) {
    $db = getDB();
    try {
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    } catch (PDOException $e) {
        // Keep runtime stable when activity_logs table is not imported yet.
    }
}

/**
 * Get recent activities
 */
function getRecentActivities($limit = 10) {
    $db = getDB();
    try {
        $stmt = $db->prepare("
            SELECT al.*, u.username 
            FROM activity_logs al 
            JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Keep runtime stable when activity_logs table is not imported yet
        return [];
    }
}

/**
 * Get announcements
 */
function getAnnouncements($limit = null, $activeOnly = true) {
    $db = getDB();
    
    $sql = "SELECT a.*, u.username as created_by_name 
            FROM announcements a 
            JOIN users u ON a.created_by = u.id 
            WHERE 1=1";
    
    if ($activeOnly) {
        $sql .= " AND (a.expiry_date IS NULL OR a.expiry_date >= CURDATE()) 
                  AND a.publish_date <= CURDATE()";
    }
    
    $sql .= " ORDER BY a.publish_date DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    try {
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Allow app to run even if announcements table is not yet imported
        return [];
    }
}

/**
 * Generate student ID - ATI-TAN-YYYY-XXXX format
 */
function generateStudentId($batch = null) {
    $db = getDB();
    
    $year = date('Y');
    $prefix = "ATI-TAN-" . $year . "-";
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE student_id LIKE ?");
    $stmt->execute([$prefix . '%']);
    $count = $stmt->fetchColumn() + 1;
    
    return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate lecturer ID
 */
function generateLecturerId() {
    $db = getDB();
    $prefix = "LEC-";
    $stmt = $db->query("SELECT lecturer_id FROM lecturers WHERE lecturer_id LIKE 'LEC-%'");
    $usedIds = [];

    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $lecturerId) {
        if (preg_match('/^LEC-(\d+)$/', $lecturerId, $matches)) {
            $usedIds[(int)$matches[1]] = true;
        }
    }

    $nextNumber = 1;
    while (isset($usedIds[$nextNumber])) {
        $nextNumber++;
    }

    return $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Format percentage with color
 */
function formatPercentage($percentage, $minRequired = null) {
    $color = 'secondary';
    if ($minRequired !== null) {
        if ($percentage >= $minRequired) {
            $color = 'success';
        } else {
            $color = 'danger';
        }
    } elseif ($percentage >= 75) {
        $color = 'success';
    } elseif ($percentage >= 50) {
        $color = 'warning';
    } else {
        $color = 'danger';
    }
    
    return '<span class="text-' . $color . ' fw-bold">' . number_format($percentage, 1) . '%</span>';
}

/**
 * Create progress bar
 */
function createProgressBar($percentage, $minRequired = null, $showLabel = true) {
    $color = 'bg-success';
    if ($minRequired !== null) {
        if ($percentage >= $minRequired) {
            $color = 'bg-success';
        } else {
            $color = 'bg-danger';
        }
    }
    
    $label = $showLabel ? number_format($percentage, 1) . '%' : '';
    
    return "
        <div class='progress' style='height: 20px;'>
            <div class='progress-bar {$color}' role='progressbar' 
                 style='width: {$percentage}%' 
                 aria-valuenow='{$percentage}' 
                 aria-valuemin='0' 
                 aria-valuemax='100'>
                {$label}
            </div>
        </div>
    ";
}

/**
 * Export to CSV
 */
function exportToCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // Add headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

/**
 * Format phone number
 */
function formatPhone($phone) {
    if (empty($phone)) return '-';
    return preg_replace('/(\d{3})(\d{3})(\d{4})/', '$1-$2-$3', $phone);
}

/**
 * Get attendance status counts
 */
function getAttendanceStatusCounts($sessionId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT attendance_status, COUNT(*) as count 
        FROM attendance_records 
        WHERE session_id = ? 
        GROUP BY attendance_status
    ");
    $stmt->execute([$sessionId]);
    $results = $stmt->fetchAll();
    
    $counts = [
        'present' => 0,
        'absent' => 0,
        'late' => 0,
        'excused' => 0
    ];
    
    foreach ($results as $row) {
        $counts[$row['attendance_status']] = $row['count'];
    }
    
    return $counts;
}

/**
 * Get profile image HTML for any user type
 */
function getProfileImage($imagePath, $fullName, $size = 40, $class = '') {
    $defaultSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
    
    if (!empty($imagePath) && file_exists(__DIR__ . '/../' . $imagePath)) {
        $classes = trim('rounded-circle ' . $class);
        return '<img src="' . BASE_URL . '/' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($fullName) . '" class="' . htmlspecialchars($classes) . '" style="width: ' . $size . 'px; height: ' . $size . 'px; object-fit: cover; border: 2px solid #dee2e6;">';
    }
    
    $initials = strtoupper(substr($fullName, 0, 1));
    return '<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: ' . $size . 'px; height: ' . $size . 'px; font-size: ' . max(12, $size/2.5) . 'px; font-weight: 600;">' . $initials . '</div>';
}

/**
 * Get lecturer profile image
 */
function getLecturerImage($imagePath, $fullName, $size = 40) {
    return getProfileImage($imagePath, $fullName, $size);
}

/**
 * Get student profile image
 */
function getStudentImage($imagePath, $fullName, $size = 40) {
    return getProfileImage($imagePath, $fullName, $size);
}
