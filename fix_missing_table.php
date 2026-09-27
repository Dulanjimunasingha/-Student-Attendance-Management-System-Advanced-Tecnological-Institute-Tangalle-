<?php
/**
 * Fix for missing table issue
 * When SHOW TABLES shows a table but DESCRIBE fails with "doesn't exist in engine"
 * This usually means the table definition exists but the actual table data is missing/corrupted
 */

echo "Fixing missing table issue...\n\n";

$host = '127.0.0.1';
$port = 1129;
$db   = 'atit_attendance';
$user = 'root';
$pass = '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Connected to database '$db'\n\n";
    
    // List tables that might be problematic
    echo "Checking for problematic tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    $problematic = [];
    foreach ($tables as $table) {
        $tableName = $table[0];
        try {
            // Try to describe the table
            $pdo->query("DESCRIBE `$tableName`")->fetchAll();
            // If we get here, table is OK
        } catch (PDOException $e) {
            if ($e->getCode() == '42S02' && strpos($e->getMessage(), "doesn't exist in engine") !== false) {
                $problematic[] = $tableName;
                echo "✗ Problematic table found: $tableName\n";
            }
        }
    }
    
    if (empty($problematic)) {
        echo "✓ No problematic tables found\n";
    } else {
        echo "\nFound " . count($problematic) . " problematic table(s):\n";
        foreach ($problematic as $table) {
            echo "  - $table\n";
        }
        
        // Ask for confirmation
        echo "\nDo you want to drop and recreate these tables? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim(strtolower($line)) == 'y') {
            // Drop and recreate each problematic table
            foreach ($problematic as $table) {
                echo "\nProcessing table: $table\n";
                try {
                    // Drop the table
                    $pdo->exec("DROP TABLE IF EXISTS `$table`");
                    echo "  → Dropped table\n";
                    
                    // We'll need to recreate from SQL file - let's handle students specially for now
                    if ($table == 'students') {
                        echo "  → Recreating students table...\n";
                        $createSql = "CREATE TABLE IF NOT EXISTS students (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            student_id VARCHAR(50) NOT NULL UNIQUE,
                            full_name VARCHAR(200) NOT NULL,
                            email VARCHAR(100) NOT NULL DEFAULT '',
                            phone VARCHAR(20),
                            address TEXT,
                            batch VARCHAR(20),
                            academic_year INT,
                            profile_image VARCHAR(255),
                            image_path VARCHAR(255),
                            date_of_birth DATE,
                            nic VARCHAR(20),
                            verification_field VARCHAR(50),
                            email_verified BOOLEAN DEFAULT FALSE,
                            status ENUM('active', 'inactive', 'graduated', 'suspended') DEFAULT 'active',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB";
                        
                        $pdo->exec($createSql);
                        echo "  → Created students table\n";
                    }
                    
                } catch (PDOException $e) {
                    echo "  ✗ Error: " . $e->getMessage() . "\n";
                }
            }
            
            echo "\n✓ Table fix attempt completed\n";
            echo "Now you need to import the full schema to restore all tables and data\n";
            echo "Run: php fix_database.php\n";
        } else {
            echo "Operation cancelled\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>