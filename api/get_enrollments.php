<?php
/**
 * Get Student Enrollments API
 */ 

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$studentId = sanitize($_GET['student_id'] ?? '');

if (empty($studentId)) {
    echo json_encode([]);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
$stmt->execute([$studentId]);
$enrollments = $stmt->fetchAll();

echo json_encode(array_column($enrollments, 'course_id'));
