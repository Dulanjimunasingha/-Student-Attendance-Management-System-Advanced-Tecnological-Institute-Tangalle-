<?php
/**
 * Get Lecturer Courses API
 */ 

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$lecturerId = sanitize($_GET['lecturer_id'] ?? '');

if (empty($lecturerId)) {
    echo json_encode([]);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT course_id FROM lecturer_course_assignments WHERE lecturer_id = ?");
$stmt->execute([$lecturerId]);
$assignments = $stmt->fetchAll();

echo json_encode(array_column($assignments, 'course_id'));
