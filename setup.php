<?php
/**
 * Complete Setup Script
 * Run this once to set up the database and create all tables
 * 
 * Usage: http://localhost/atit-attendly-main/php-site/setup.php
 */

// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>ATIT Setup</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f6fa; }
        .card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        h1 { color: #0d6efd; }
        .success { color: #198754; }
        .error { color: #dc3545; }
        .info { color: #0dcaf0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto; }
        .btn { display: inline-block; padding: 12px 24px; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        .btn:hover { background: #0a58ca; }
    </style>
</head>
<body>
<div class='card'>
<h1>🔧 ATIT Attendance System Setup</h1>
<pre>";

// Database configuration
$host = '127.0.0.1';
$port = 3306;  // Change to 1129 if using that port
$dbname = 'atit_attendance';
$username = 'root';
$password = '';

try {
    // Step 1: Connect to MySQL
    echo "Step 1: Connecting to MySQL server...\n";
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<span class='success'>✓ Connected to MySQL</span>\n\n";
    
    // Step 2: Create database
    echo "Step 2: Creating database...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    echo "<span class='success'>✓ Database '$dbname' ready</span>\n\n";
    
    // Step 3: Create tables
    echo "Step 3: Creating tables...\n";
    
    $tables = [
        "roles" => "CREATE TABLE IF NOT EXISTS roles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            role_name VARCHAR(50) NOT NULL UNIQUE,
            role_slug VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        "users" => "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role_id INT NOT NULL,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            must_change_password BOOLEAN DEFAULT FALSE,
            temp_password VARCHAR(255),
            last_login DATETIME,
            failed_login_attempts INT DEFAULT 0,
            lock_until DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB",
        
        "students" => "CREATE TABLE IF NOT EXISTS students (
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
        ) ENGINE=InnoDB",
        
        "lecturers" => "CREATE TABLE IF NOT EXISTS lecturers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            lecturer_id VARCHAR(50) NOT NULL UNIQUE,
            full_name VARCHAR(200) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            department VARCHAR(100),
            qualification VARCHAR(100),
            profile_image VARCHAR(255),
            image_path VARCHAR(255),
            status ENUM('active', 'inactive') DEFAULT 'active',
            user_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",
        
        "courses" => "CREATE TABLE IF NOT EXISTS courses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            course_code VARCHAR(50) NOT NULL UNIQUE,
            course_name VARCHAR(200) NOT NULL,
            description TEXT,
            department VARCHAR(100),
            credits INT DEFAULT 3,
            min_attendance_percentage DECIMAL(5,2) DEFAULT 75.00,
            late_count_percentage DECIMAL(5,2) DEFAULT 50.00,
            excused_count_full BOOLEAN DEFAULT TRUE,
            start_date DATE,
            end_date DATE,
            status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        "enrollments" => "CREATE TABLE IF NOT EXISTS enrollments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            enrolled_date DATE NOT NULL,
            completion_status ENUM('not_started', 'in_progress', 'completed', 'dropped') DEFAULT 'not_started',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            UNIQUE KEY unique_enrollment (student_id, course_id)
        ) ENGINE=InnoDB",
        
        "lecturer_course_assignments" => "CREATE TABLE IF NOT EXISTS lecturer_course_assignments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            lecturer_id INT NOT NULL,
            course_id INT NOT NULL,
            assigned_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            UNIQUE KEY unique_assignment (lecturer_id, course_id)
        ) ENGINE=InnoDB",
        
        "attendance_sessions" => "CREATE TABLE IF NOT EXISTS attendance_sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            course_id INT NOT NULL,
            lecturer_id INT NOT NULL,
            session_date DATE NOT NULL,
            lesson_topic VARCHAR(255),
            notes TEXT,
            is_makeup_session BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
            UNIQUE KEY unique_session (course_id, session_date)
        ) ENGINE=InnoDB",
        
        "attendance_records" => "CREATE TABLE IF NOT EXISTS attendance_records (
            id INT PRIMARY KEY AUTO_INCREMENT,
            session_id INT NOT NULL,
            student_id INT NOT NULL,
            attendance_status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
            marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            marked_by INT,
            FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_record (session_id, student_id)
        ) ENGINE=InnoDB",
        
        "announcements" => "CREATE TABLE IF NOT EXISTS announcements (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            publish_date DATE NOT NULL,
            expiry_date DATE,
            audience ENUM('all', 'students', 'lecturers', 'admin') DEFAULT 'all',
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            is_pinned BOOLEAN DEFAULT FALSE,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        "activity_logs" => "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",
        
        "system_settings" => "CREATE TABLE IF NOT EXISTS system_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
    ];
    
    foreach ($tables as $name => $sql) {
        try {
            $pdo->exec($sql);
            echo "<span class='success'>✓ Table '$name' created</span>\n";
        } catch (PDOException $e) {
            echo "<span class='info'>ℹ Table '$name' already exists</span>\n";
        }
    }
    echo "\n";
    
    // Step 4: Insert default data
    echo "Step 4: Inserting default data...\n";
    
    // Roles
    try {
        $pdo->exec("INSERT IGNORE INTO roles (id, role_name, role_slug, description) VALUES
            (1, 'Administrator', 'admin', 'System administrator with full access'),
            (2, 'Lecturer', 'lecturer', 'Teaching staff who can mark attendance')");
        echo "<span class='success'>✓ Roles inserted</span>\n";
    } catch (PDOException $e) {
        echo "<span class='info'>ℹ Roles already exist</span>\n";
    }
    
    // Admin user (password: admin123)
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    try {
        $pdo->exec("INSERT IGNORE INTO users (id, username, email, password, role_id, status) VALUES
            (1, 'admin', 'admin@atit.edu.lk', '$adminHash', 1, 'active')");
        echo "<span class='success'>✓ Admin user created (admin / admin123)</span>\n";
    } catch (PDOException $e) {
        echo "<span class='info'>ℹ Admin user already exists</span>\n";
    }
    
    // Lecturer users
    $lecturerHash = password_hash('lecturer123', PASSWORD_DEFAULT);
    try {
        $pdo->exec("INSERT IGNORE INTO users (id, username, email, password, role_id, status) VALUES
            (2, 'dr.silva', 'dr.silva@atit.edu.lk', '$lecturerHash', 2, 'active'),
            (3, 'ms.perera', 'ms.perera@atit.edu.lk', '$lecturerHash', 2, 'active'),
            (4, 'mr.jayawardena', 'mr.jayawardena@atit.edu.lk', '$lecturerHash', 2, 'active')");
        echo "<span class='success'>✓ Lecturer users created (password: lecturer123)</span>\n";
    } catch (PDOException $e) {
        echo "<span class='info'>ℹ Lecturer users already exist</span>\n";
    }
    
    // Lecturers
    try {
        $pdo->exec("INSERT IGNORE INTO lecturers (id, lecturer_id, full_name, email, phone, department, qualification, status, user_id) VALUES
            (1, 'LEC-0001', 'Dr. Kumara Silva', 'dr.silva@atit.edu.lk', '077-123-4567', 'Computer Science', 'PhD in Computer Science', 'active', 2),
            (2, 'LEC-0002', 'Ms. Priya Perera', 'ms.perera@atit.edu.lk', '077-234-5678', 'Information Technology', 'MSc in IT', 'active', 3),
            (3, 'LEC-0003', 'Mr. Anura Jayawardena', 'mr.jayawardena@atit.edu.lk', '077-345-6789', 'Mathematics', 'MSc in Mathematics', 'active', 4)");
        echo "<span class='success'>✓ Lecturers created</span>\n";
    } catch (PDOException $e) {
        echo "<span class='info'>ℹ Lecturers already exist</span>\n";
    }
    
    // Students
    try {
        $pdo->exec("INSERT IGNORE INTO students (id, student_id, full_name, email, phone, batch, academic_year, email_verified, status, date_of_birth, nic, verification_field) VALUES
            (1, 'ATIT-2024-0001', 'Kamal Rathnayake', 'kamal.rathnayake@student.atit.edu.lk', '0711234567', '2024', 1, TRUE, 'active', '2005-03-15', '2005523V', '0315'),
            (2, 'ATIT-2024-0002', 'Nisha Fernando', 'nisha.fernando@student.atit.edu.lk', '0712345678', '2024', 1, TRUE, 'active', '2005-07-22', '2005723V', '0722'),
            (3, 'ATIT-2024-0003', 'Roshan Silva', 'roshan.silva@student.atit.edu.lk', '0713456789', '2024', 1, TRUE, 'active', '2005-01-10', '2005110V', '0110'),
            (4, 'ATIT-2024-0004', 'Dilini Perera', 'dilini.perera@student.atit.edu.lk', '0714567890', '2024', 1, TRUE, 'active', '2005-09-05', '2005905V', '0905'),
            (5, 'ATIT-2024-0005', 'Kasun Jayawardena', 'kasun.jayawardena@student.atit.edu.lk', '0715678901', '2024', 1, TRUE, 'active', '2005-11-18', '2005118V', '1118'),
            (6, 'ATIT-2024-0006', 'Sachini Dissanayake', 'sachini.dissanayake@student.atit.edu.lk', '0716789012', '2024', 1, TRUE, 'active', '2005-04-30', '2005430V', '0430'),
            (7, 'ATIT-2024-0007', 'Chamod Weerawardena', 'chamod.weerawardena@student.atit.edu.lk', '0717890123', '2024', 1, TRUE, 'active', '2005-08-12', '2005812V', '0812'),
            (8, 'ATIT-2024-0008', 'Thiwanka Rajapaksha', 'thiwanka.rajapaksha@student.atit.edu.lk', '0718901234', '2024', 1, TRUE, 'active', '2005-12-25', '2005125V', '1225')");
        echo "<span class='success'>✓ Students created (8 sample students)</span>\n";
    } catch (PDOException $e) {
        echo "<span class='info'>ℹ Students already exist</span>\n";
    }
    
    // Courses
    try {
        $pdo->exec("INSERT IGNORE INTO courses (id, course_code, course_name, description, department, credits, min_attendance_percentage, status) VALUES
            (1, 'CS101', 'Introduction to Programming', 'Basic programming concepts using Python', 'Computer Science', 3, 75.00, 'active'),
            (2, 'CS102', 'Data Structures and Algorithms', 'Fundamental data structures and algorithm design', 'Computer Science', 4, 75.00, 'active'),
            (3, 'IT101', 'Web Development', 'HTML, CSS, JavaScript and modern web frameworks', 'Information Technology', 3, 75.00, 'active'),
            (4, 'MATH101', 'Applied Mathematics', 'Calculus and linear algebra for computing', 'Mathematics', 3, 75.00, 'active'),
            (5, 'IT102', 'Database Systems', 'Relational databases and SQL', 'Information Technology', 3, 75.00, 'active')");
        echo "<span class='success'>✓ Courses created (5 sample courses)</span>\n";
    } catch (PDOException $e) {
        echo "<span class='info'>ℹ Courses already exist</span>\n";
    }
    
    // Enrollments
    try {
        $pdo->exec("INSERT IGNORE INTO enrollments (student_id, course_id, enrolled_date, completion_status) VALUES
            (1, 1, '2024-01-15', 'in_progress'), (1, 2, '2024-01-15', 'in_progress'), (1, 3, '2024-01-15', 'in_progress'), (1, 5, '2024-01-15', 'in_progress'),
            (2, 1, '2024-01-15', 'in_progress'), (2, 2, '2024-01-15', 'in_progress'), (2, 3, '2024-01-15', 'in_progress'),
            (3, 1, '2024-01-15', 'in_progress'), (3, 2, '2024-01-15', 'in_progress'), (3, 4, '2024-01-15', 'in_progress'),
            (4, 1, '2024-01-15', 'in_progress'), (4, 3, '2024-01-15', 'in_progress'), (4, 5, '2024-01-15', 'in_progress'),
            (5, 2, '2024-01-15', 'in_progress'), (5, 4, '2024-01-15', 'in_progress'), (5, 5, '2024-01-15', 'in_progress'),
            (6, 1, '2024-01-15', 'in_progress'), (6, 2, '2024-01-15', 'in_progress'), (6, 3, '2024-01-15', 'in_progress'),
            (7, 1, '2024-01-15', 'in_progress'), (7, 5, '2024-01-15', 'in_progress'),
            (8, 2, '2024-01-15', 'in_progress'), (8, 3, '2024-01-15', 'in_progress'), (8, 4, '2024-01-15', 'in_progress')");
        echo "<span class='success'>✓ Enrollments created</span>\n";
    } catch (PDOException $e) {
        echo "<span class='info'>ℹ Enrollments already exist</span>\n";
    }
    
    // Lecturer assignments
    try {
        $pdo->exec("INSERT IGNORE INTO lecturer_course_assignments (lecturer_id, course_id, assigned_date) VALUES
            (1, 1, '2024-01-10'), (1, 2, '2024-01-10'),
            (2, 3, '2024-01-10'), (2, 5, '2024-01-10'),
            (3, 4, '2024-01-10')");
        echo "<span class='success'>✓ Lecturer assignments created</span>\n";
    } catch (PDOException $e) {
        echo "<span class='info'>ℹ Lecturer assignments already exist</span>\n";
    }
    
    // Announcements
    try {
        $pdo->exec("INSERT IGNORE INTO announcements (id, title, content, publish_date, expiry_date, audience, priority, is_pinned, created_by) VALUES
            (1, 'Welcome to ATIT Attendance System', 'Dear Students and Staff, Welcome to the new Academic Year 2024! We are pleased to introduce our new Attendance Management System.', '2024-01-15', '2025-12-31', 'all', 'high', TRUE, 1),
            (2, 'Examination Schedule Notice', 'The midterm examinations will be conducted from March 15-30, 2024. Students must maintain at least 75% attendance to be eligible.', '2024-03-01', '2025-04-01', 'students', 'high', TRUE, 1),
            (3, 'Staff Meeting Announcement', 'There will be a staff meeting on March 20, 2024 at 2:00 PM in the main conference hall.', '2024-03-15', '2025-03-25', 'lecturers', 'medium', FALSE, 1)");
        echo "<span class='success'>✓ Announcements created</span>\n";
    } catch (PDOException $e) {
        echo "<span class='info'>ℹ Announcements already exist</span>\n";
    }
    
    // System settings
    try {
        $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
            ('hero_image', 'assets/images/campus.jpg'),
            ('hero_title', 'Advanced Technological Institute of Tangalle'),
            ('hero_subtitle', 'Attendance Management System')");
        echo "<span class='success'>✓ System settings created</span>\n";
    } catch (PDOException $e) {
        echo "<span class='info'>ℹ System settings already exist</span>\n";
    }
    
    echo "\n";
    echo "<span class='success'>🎉 Setup complete!</span>\n\n";
    
    // Verification
    echo "Verification:\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "  - Tables: " . count($tables) . "\n";
    
    foreach (['users', 'students', 'lecturers', 'courses'] as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "  - $t: $count records\n";
    }
    
    echo "\n";
    echo "<span class='success'>Login Credentials:</span>\n";
    echo "  Admin:    admin / admin123\n";
    echo "  Lecturer: dr.silva / lecturer123\n";
    echo "  Student:  ATIT-2024-0001 / 0315\n";
    
    echo "\n<a href='index.php' class='btn'>Go to Homepage</a>";
    echo " <a href='auth/login.php' class='btn'>Go to Login</a>";
    
} catch (PDOException $e) {
    echo "<span class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure MySQL/XAMPP is running\n";
    echo "2. Check database credentials in this file\n";
    echo "3. If using a different port, change \$port variable\n";
}

echo "</pre></div></body></html>";
?>