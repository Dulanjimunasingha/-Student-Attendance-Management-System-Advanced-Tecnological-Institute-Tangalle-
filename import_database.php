<?php
/**
 * Database Import Using MySQL Client
 * Uses the mysql.exe command line tool to import the SQL file
 */

echo "<pre>";
echo "Database Import Using MySQL Client\n";
echo "=================================\n\n";

// Configuration
$mysqlPath = 'C:/xampp/mysql/bin/mysql.exe';
$host = '127.0.0.1';
$port = 1129;
$user = 'root';
$pass = ''; // empty password
$dbName = 'atit_attendance';
$sqlFile = 'C:/xampp/htdocs/atit-attendly-main/php-site/database/attendance_system.sql';

// Check if mysql.exe exists
if (!file_exists($mysqlPath)) {
    die("Error: MySQL executable not found at {$mysqlPath}\n");
}

// Check if SQL file exists
if (!file_exists($sqlFile)) {
    die("Error: SQL file not found at {$sqlFile}\n");
}

echo "MySQL path: {$mysqlPath}\n";
echo "Host: {$host}:{$port}\n";
echo "Database: {$dbName}\n";
echo "SQL file: {$sqlFile}\n\n";

// Step 1: Drop and create database
echo "Step 1: Dropping and creating database...\n";
$sql = "DROP DATABASE IF EXISTS {$dbName}; CREATE DATABASE {$dbName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
$command = "\"{$mysqlPath}\" -h {$host} -P {$port} -u {$user} -p\"{$pass}\" -e \"{$sql}\"";
echo "Running: {$command}\n";
exec($command, $output, $returnVar);
if ($returnVar !== 0) {
    echo "Error executing SQL:\n";
    foreach ($output as $line) {
        echo "  {$line}\n";
    }
    die("Failed to drop/create database\n");
}
echo "✓ Database created successfully\n\n";

// Step 2: Import SQL file
echo "Step 2: Importing SQL file...\n";
$command = "\"{$mysqlPath}\" -h {$host} -P {$port} -u {$user} -p\"{$pass}\" {$dbName} < \"{$sqlFile}\"";
echo "Running: {$command}\n";
exec($command, $output, $returnVar);
if ($returnVar !== 0) {
    echo "Error importing SQL file:\n";
    foreach ($output as $line) {
        echo "  {$line}\n";
    }
    die("Failed to import SQL file\n");
}
echo "✓ SQL file imported successfully\n\n";

// Step 3: Verification
echo "Step 3: Verifying import...\n";
// Connect to verify
try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    echo "  - Tables created: " . count($tables) . "\n";
    
    # Check key tables
    $keyTables = ['users', 'students', 'courses', 'lecturers'];
    foreach ($keyTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->fetchColumn()) {
            $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            echo "  - {$table}: {$count} records\n";
        } else {
            echo "  - {$table}: MISSING\n";
        }
    }
    
    # Check admin user
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
    
} catch (PDOException $e) {
    echo "Error connecting to verify: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>