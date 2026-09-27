<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/database/rebuild_tables.sql');
    
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Split statements by semicolon
    $statements = [];
    $current = '';
    foreach (explode("\n", $sql) as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) continue;
        $current .= $line . "\n";
        if (substr(rtrim($line), -1) === ';') {
            $statements[] = trim($current);
            $current = '';
        }
    }
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }
    
    echo "Executing " . count($statements) . " statements...\n\n";
    
    $count = 0;
    $errors = 0;
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try {
            $db->exec($stmt);
            $count++;
        } catch (PDOException $e) {
            $errors++;
            $msg = $e->getMessage();
            if (stripos($msg, 'already exists') === false && 
                stripos($msg, 'duplicate') === false &&
                stripos($msg, 'warning') === false) {
                echo "ERROR: {$msg}\n";
                echo "  SQL: " . substr(str_replace("\n", ' ', $stmt), 0, 100) . "...\n";
            }
        }
    }
    
    echo "\n-----------------------------\n";
    echo "Executed: {$count}\n";
    echo "Ignored (duplicates): {$errors}\n\n";
    
    // Verify
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database (" . count($tables) . "): " . implode(', ', $tables) . "\n";
    
    if (in_array('students', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM students");
        $row = $stmt->fetch();
        echo "Students: " . $row['cnt'] . "\n";
    }
    
    if (in_array('lecturers', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM lecturers");
        $row = $stmt->fetch();
        echo "Lecturers: " . $row['cnt'] . "\n";
    }
    
    if (in_array('courses', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM courses");
        $row = $stmt->fetch();
        echo "Courses: " . $row['cnt'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Fatal: " . $e->getMessage() . "\n";
}