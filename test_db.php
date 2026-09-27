<?php
header('Content-Type: text/plain');

// Database credentials from config
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 1129);
define('DB_NAME', 'atit_attendance');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    // First connect without database to handle fresh setups
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $tempConnection = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "Connected to MySQL server successfully\n";
    
    // Check if database exists
    $dbs = $tempConnection->query("SHOW DATABASES LIKE '" . DB_NAME . "'")->fetchColumn();
    if ($dbs) {
        echo "Database '" . DB_NAME . "' exists\n";
        
        // Connect to the database
        $tempConnection->exec("USE " . DB_NAME);
        
        // Check if students table exists
        $tables = $tempConnection->query("SHOW TABLES LIKE 'students'")->fetchColumn();
        if ($tables) {
            echo "Table 'students' exists\n";
            
            // Count records
            $count = $tempConnection->query("SELECT COUNT(*) FROM students")->fetchColumn();
            echo "Students table has " . $count . " records\n";
        } else {
            echo "Table 'students' does NOT exist\n";
            
            // List all tables
            echo "Available tables:\n";
            $result = $tempConnection->query("SHOW TABLES");
            while ($row = $result->fetch()) {
                echo "  - " . $row[0] . "\n";
            }
        }
    } else {
        echo "Database '" . DB_NAME . "' does NOT exist\n";
        
        // List all databases
        echo "Available databases:\n";
        $result = $tempConnection->query("SHOW DATABASES");
        while ($row = $result->fetch()) {
            echo "  - " . $row[0] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>