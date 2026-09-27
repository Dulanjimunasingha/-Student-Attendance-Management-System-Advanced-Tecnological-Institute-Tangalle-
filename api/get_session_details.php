<?php
/**
 * Get Session Details API
 */ 

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$sessionId = sanitize($_GET['session_id'] ?? '');

if (empty($sessionId)) {
    echo json_encode(['error' => 'Session ID required']);
    exit;
}

$db = getDB();

// Get session details
$stmt = $db->prepare("
    SELECT ass.*, c.course_code, c.course_name
    FROM attendance_sessions ass
    JOIN courses c ON ass.course_id = c.id
    WHERE ass.id = ?
");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session) {
    echo json_encode(['error' => 'Session not found']);
    exit;
}

// Get students with attendance
$stmt = $db->prepare("
    SELECT s.student_id, s.full_name, ar.attendance_status
    FROM attendance_records ar
    JOIN students s ON ar.student_id = s.id
    WHERE ar.session_id = ?
    ORDER BY s.full_name
");
$stmt->execute([$sessionId]);
$students = $stmt->fetchAll();

echo json_encode([
    'id' => $session['id'],
    'course_code' => $session['course_code'],
    'course_name' => $session['course_name'],
    'session_date' => $session['session_date'],
    'lesson_topic' => $session['lesson_topic'],
    'notes' => $session['notes'],
    'students' => $students
]);
