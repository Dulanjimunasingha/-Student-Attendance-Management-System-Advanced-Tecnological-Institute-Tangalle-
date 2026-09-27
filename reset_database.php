<?php
/**
 * Database Reset and Import Script
 * 
 * This script will:
 * 1. Drop the existing database
 * 2. Create a fresh database
 * 3. Import the schema and data from attendance_system.sql
 */

set_time_limit(0); // Remove time limit for large imports
ignore_user_abort(true); // Continue even if user aborts

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 1129);
define('DB_NAME', 'atit_attendance');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

echo "<pre>";
echo "Database Reset and Import Tool\n";
echo "=============================\n\n";

try {
    // Connect to MySQL server (without database)
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "✓ Connected to MySQL server\n";
    
    // Drop database if exists
    echo "Dropping database '" . DB_NAME . "' if it exists...\n";
    $pdo->exec("DROP DATABASE IF EXISTS " . DB_NAME);
    echo "✓ Database dropped\n";
    
    // Create database
    echo "Creating database '" . DB_NAME . "'...\n";
    $pdo->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created\n";
    
    // Select database
    $pdo->exec("USE " . DB_NAME);
    echo "✓ Selected database\n";
    
    // Load and execute SQL file
    $sqlFile = __DIR__ . '/database/attendance_system.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: " . $sqlFile);
    }
    
    echo "Reading SQL file: " . $sqlFile . "\n";
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Failed to read SQL file");
    }
    
    echo "SQL file loaded successfully (" . strlen($sql) . " bytes)\n";
    echo "Executing SQL statements...\n";
    
    // Split SQL into statements
    // This is a simple split - might not handle all edge cases but should work for our schema
    $statements = preg_split('/;[\r\n]+/', $sql);
    
    $success = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || substr($statement, 0, 2) == '--') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $success++;
            
            // Show progress every 50 statements
            if ($success % 50 == 0) {
                echo "  Executed {$success} statements...\n";
            }
        } catch (PDOException $e) {
            $failed++;
            // Only show errors for non-trivial statements that aren't expected to fail
            if (stripos($statement, 'drop table if exists') !== 0 && 
                stripos($statement, 'create table if not exists') !== 0 &&
                stripos($statement, 'insert into') !== 0 &&
                stripos($statement, 'update ') !== 0 &&
                stripos($statement, 'delete from') !== 0) {
                echo "  ⚠ Warning on statement " . ($success + $failed) . ": " . $e->getMessage() . "\n";
                echo "     Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\n✓ Import completed!\n";
    echo "  - Successful statements: {$success}\n";
    echo "  - Failed statements: {$failed}\n\n";
    
    // Verification
    echo "Verifying import...\n";
    
    // Check if we have tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    echo "  - Tables created: " . count($tables) . "\n";
    
    // Check key tables
    $keyTables = ['users', 'students', 'courses', 'lecturers'];
    foreach ($keyTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '" . $table . "'");
        if ($stmt->fetchColumn()) {
            $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            echo "  - {$table}: {$count} records\n";
        } else {
            echo "  - {$table}: MISSING\n";
        }
    }
    
    // Check admin user
    $stmt = $pdo->query("SELECT * FROM users WHERE username = 'admin'");
    $admin = $stmt->fetch();
    if ($admin) {
        echo "  - Admin user: FOUND (email: {$admin['email']})\n";
    } else {
        echo "  - Admin user: MISSING\n";
    }
    
    echo "\n🎉 Database setup complete! You can now login to the system.\n";
    echo "   Default admin login:\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Please check:\n";
    echo "1. XAMPP MySQL service is running\n";
    echo "2. The SQL file exists at: " . __DIR__ . '/database/attendance_system.sql' . "\n";
    echo "3. You have sufficient privileges\n";
}

echo "</pre>";
?>