<?php
echo "Checking database status...\n\n";

$host = '127.0.0.1';
$port = 1129;
$db   = 'atit_attendance';
$user = 'root';
$pass = '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Connected successfully\n\n";
    
    // Check database
    echo "Checking database: $db\n";
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $result = $stmt->fetch();
    echo "Current database: " . $result['db'] . "\n\n";
    
    // Check if students table exists
    echo "Checking if students table exists...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'students'");
    if ($stmt->fetchColumn()) {
        echo "✓ Students table EXISTS\n\n";
        
        // Get table info
        echo "Table information:\n";
        $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'students'");
        $tableInfo = $stmt->fetch();
        print_r($tableInfo);
        echo "\n";
        
        // Count records
        $count = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
        echo "Record count: $count\n\n";
        
    } else {
        echo "✗ Students table does NOT exist\n\n";
        
        // Show all tables
        echo "All tables in database:\n";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll();
        if (empty($tables)) {
            echo "(No tables found)\n";
        } else {
            foreach ($tables as $table) {
                echo "- " . $table[0] . "\n";
            }
        }
        echo "\n";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
?>