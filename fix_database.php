<?php
/**
 * Database Diagnostic and Fix Script
 * 
 * This script will:
 * 1. Check if the database exists
 * 2. Check if tables exist
 * 3. If missing, import the SQL file
 */

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 1129);
define('DB_NAME', 'atit_attendance');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

echo "<pre>";
echo "Database Diagnostic Tool\n";
echo "=====================\n\n";

// Try to connect
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "✓ Connected to MySQL server\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($stmt->fetchColumn()) {
        echo "✓ Database '" . DB_NAME . "' exists\n";
        
        // Select the database
        $pdo->exec("USE " . DB_NAME);
        
        // Check for students table
        $stmt = $pdo->query("SHOW TABLES LIKE 'students'");
        if ($stmt->fetchColumn()) {
            echo "✓ Table 'students' exists\n";
            
            // Get count
            $count = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            echo "✓ Students table has {$count} records\n\n";
            
            echo "Database is ready! You can now login.\n";
        } else {
            echo "✗ Table 'students' does NOT exist\n\n";
            
            // Show all tables
            echo "Available tables:\n";
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll();
            if (empty($tables)) {
                echo "  (No tables found)\n\n";
            } else {
                foreach ($tables as $table) {
                    echo "  - " . $table[0] . "\n";
                }
                echo "\n";
            }
            
            // Offer to fix
            echo "The database exists but tables are missing.\n";
            echo "Would you like to import the schema now? (y/n): ";
            $handle = fopen ("php://stdin","r");
            $line = fgets($handle);
            if(trim($line) == 'y') {
                importDatabase($pdo);
            }
        }
    } else {
        echo "✗ Database '" . DB_NAME . "' does NOT exist\n\n";
        
        // Show all databases
        echo "Available databases:\n";
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll();
        foreach ($databases as $db) {
            echo "  - " . $db[0] . "\n";
        }
        echo "\n";
        
        // Offer to fix
        echo "Database is missing. Would you like to create it and import the schema? (y/n): ";
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        if(trim($line) == 'y') {
            // Create database
            $pdo->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✓ Created database '" . DB_NAME . "'\n";
            importDatabase($pdo);
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n\n";
    
    // Show error details
    echo "Error details:\n";
    echo "- Host: " . DB_HOST . "\n";
    echo "- Port: " . DB_PORT . "\n";
    echo "- Username: " . DB_USER . "\n";
    echo "- Password: " . (empty($DB_PASS) ? '(empty)' : 'set') . "\n";
    echo "- Database: " . DB_NAME . "\n\n";
    
    echo "Please check your XAMPP MySQL service is running on port " . DB_PORT . "\n";
    echo "You can start it from the XAMPP Control Panel.\n";
}

/**
 * Import the database schema from SQL file
 */
function importDatabase($pdo) {
    global $DB_NAME;
    
    echo "\nImporting database schema...\n";
    
    // Select database
    $pdo->exec("USE " . $DB_NAME);
    
    // SQL file path
    $sqlFile = __DIR__ . '/database/attendance_system.sql';
    
    if (!file_exists($sqlFile)) {
        echo "✗ SQL file not found: " . $sqlFile . "\n";
        return;
    }
    
    // Read SQL file
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon followed by newline or end of file
    $statements = preg_split('/;\s*[\r\n]+/', $sql);
    
    $success = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            // Skip comments
            if (strpos($statement, '--') === 0) continue;
            
            $pdo->exec($statement);
            $success++;
        } catch (PDOException $e) {
            // Some statements might fail (like CREATE IF EXISTS), that's ok
            $failed++;
            // Only show errors for non-trivial statements
            if (stripos($statement, 'insert') !== 0 && stripos($statement, 'update') !== 0) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
                echo "  Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "✓ Import completed: {$success} statements executed, {$failed} failed\n";
    
    // Verify
    echo "\nVerifying import...\n";
    $pdo->exec("USE " . $DB_NAME);
    $stmt = $pdo->query("SHOW TABLES LIKE 'students'");
    if ($stmt->fetchColumn()) {
        echo "✓ Students table now exists\n";
        $count = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
        echo "✓ Students table has {$count} records\n\n";
        echo "Database is ready! You can now login.\n";
    } else {
        echo "✗ Import may have failed - students table still missing\n";
    }
}

echo "</pre>";
?>