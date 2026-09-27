<?php
/**
 * Authentication Helper - Fixed Version
 * Advanced Technological Institute of Tangalle - Attendance Management System
 * 
 * SECURITY FEATURES:
 * - Admin/Lecturer: Email/Username + Password (bcrypt hashed)
 * - Student: Student ID + DOB/NIC last 4/Phone last 4 verification
 * - NO OTP - removed
 * - NO email login for students
 * - PDO prepared statements
 * - Session-based auth with role-based access
 * - Rate limiting for student verification
 */

require_once __DIR__ . '/functions.php';

/**
 * Ensure students table exists (self-healing for fresh setups)
 */
function ensureStudentsTable(PDO $db) {
    try {
        // Check if students table exists
        $stmt = $db->prepare("SHOW TABLES LIKE 'students'");
        $stmt->execute();
        $tableExists = $stmt->fetch();
        
        if (!$tableExists) {
            // Create students table
            $db->exec("CREATE TABLE IF NOT EXISTS students (
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
            ) ENGINE=InnoDB");
            
            // Create indexes
            $db->exec("CREATE INDEX idx_students_student_id ON students(student_id)");
            $db->exec("CREATE INDEX idx_students_email ON students(email)");
            $db->exec("CREATE INDEX idx_students_status ON students(status)");
            
            // Insert sample students for testing
            $db->exec("INSERT INTO students (student_id, full_name, email, phone, batch, academic_year, email_verified, status, verification_field) VALUES
                ('ATIT-2024-0001', 'Kamal Rathnayake', 'kamal.rathnayake@student.atit.edu.lk', '0711234567', '2024', 1, TRUE, 'active', '0315'),
                ('ATIT-2024-0002', 'Nisha Fernando', 'nisha.fernando@student.atit.edu.lk', '0712345678', '2024', 1, TRUE, 'active', '1225'),
                ('ATIT-2024-0003', 'Roshan Silva', 'roshan.silva@student.atit.edu.lk', '0713456789', '2024', 1, TRUE, 'active', '0891')");
            
            return true;
        }
        return true;
    } catch (Exception $e) {
        error_log("Failed to create students table: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if students table exists and is accessible
 */
function checkStudentsTableExists(PDO $db) {
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE 'students'");
        $stmt->execute();
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Ensure essential authentication tables exist (self-healing for fresh XAMPP setups)
 */
function ensureAuthSchema(PDO $db) {
    try {
        // Ensure database exists (for fresh setups)
        $db->exec("CREATE DATABASE IF NOT EXISTS atit_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->exec("USE atit_attendance");
        
        $db->exec("CREATE TABLE IF NOT EXISTS roles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            role_name VARCHAR(50) NOT NULL UNIQUE,
            role_slug VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS users (
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
        ) ENGINE=InnoDB");

        $stmt = $db->prepare("INSERT IGNORE INTO roles (role_name, role_slug, description) VALUES
            ('Administrator', 'admin', 'System administrator with full access'),
            ('Lecturer', 'lecturer', 'Teaching staff who can mark attendance')");
        $stmt->execute();

        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@atit.edu.lk'");
        $stmt->execute();
        if ((int)$stmt->fetchColumn() === 0) {
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role_id, status)
                VALUES ('admin', 'admin@atit.edu.lk', ?, (SELECT id FROM roles WHERE role_slug = 'admin' LIMIT 1), 'active')");
            $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
        }

        // Create additional tables that may be missing
        // Courses table
        $db->exec("CREATE TABLE IF NOT EXISTS courses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            course_code VARCHAR(50) NOT NULL UNIQUE,
            course_name VARCHAR(200) NOT NULL,
            description TEXT,
            department VARCHAR(100),
            credits INT DEFAULT 3,
            min_attendance_percentage DECIMAL(5,2) DEFAULT 75.00,
            status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        // Attendance sessions table
        $db->exec("CREATE TABLE IF NOT EXISTS attendance_sessions (
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
            FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_session (course_id, session_date)
        ) ENGINE=InnoDB");

        // Attendance records table
        $db->exec("CREATE TABLE IF NOT EXISTS attendance_records (
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
        ) ENGINE=InnoDB");

        // Enrollments table
        $db->exec("CREATE TABLE IF NOT EXISTS enrollments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            enrolled_date DATE NOT NULL,
            completion_status ENUM('not_started', 'in_progress', 'completed', 'dropped') DEFAULT 'not_started',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            UNIQUE KEY unique_enrollment (student_id, course_id)
        ) ENGINE=InnoDB");

        // Activity logs table
        $db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB");
        
        // Lecturers table
        $db->exec("CREATE TABLE IF NOT EXISTS lecturers (
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
        ) ENGINE=InnoDB");
        
        // Announcements table
        $db->exec("CREATE TABLE IF NOT EXISTS announcements (
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
        ) ENGINE=InnoDB");

        // Lecturer course assignments table
        $db->exec("CREATE TABLE IF NOT EXISTS lecturer_course_assignments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            lecturer_id INT NOT NULL,
            course_id INT NOT NULL,
            assigned_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            UNIQUE KEY unique_assignment (lecturer_id, course_id)
        ) ENGINE=InnoDB");
        
        // Student OTP verifications table
        $db->exec("CREATE TABLE IF NOT EXISTS student_otp_verifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            email VARCHAR(100) NOT NULL,
            otp_code VARCHAR(10) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            expires_at DATETIME NOT NULL,
            is_used BOOLEAN DEFAULT FALSE,
            attempts INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        // Try to import full schema from SQL file (best approach)
        try {
            $sqlFile = __DIR__ . '/../database/attendance_system.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                // Split on semicolons and execute each statement
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $statement) {
                    if (!empty($statement) && stripos($statement, 'DROP DATABASE') === false) {
                        try {
                            $db->exec($statement);
                        } catch (Exception $e) {
                            // Ignore errors for individual statements (table might already exist)
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Continue even if SQL import fails
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Login admin or lecturer with email/username and password
 */
function loginUser($username, $password, $role = null) {
    $db = getDB();
    ensureAuthSchema($db);
    
    $sql = "SELECT u.*, r.role_name, r.role_slug 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE (u.username = ? OR u.email = ?)";
    
    $params = [$username, $username];
    if ($role) {
        $sql .= " AND r.role_slug = ?";
        $params[] = $role;
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database schema is not ready. Please import the SQL file before logging in.'
        ];
    }
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid username or email'];
    }
    
    // Check if account is locked
    $lockUntil = $user['lock_until'] ?? null;
    if ($lockUntil && strtotime($lockUntil) > time()) {
        return ['success' => false, 'message' => 'Account is temporarily locked'];
    }
    
    // Verify password
    $passwordValid = false;
    $hashedPassword = $user['password'];
    
    // Check if it's a valid bcrypt hash (starts with $2y$ or $2a$)
    if (preg_match('/^\$2[ayb]\$\d{2}\$/', $hashedPassword)) {
        $passwordValid = password_verify($password, $hashedPassword);
    } 
    // Legacy: plain text password check (for migration)
    elseif ($hashedPassword === $password) {
        $passwordValid = true;
        // Upgrade to bcrypt
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $user['id']]);
    }
    
    if (!$passwordValid) {
        $failedAttempts = ($user['failed_login_attempts'] ?? 0) + 1;
        
        if ($failedAttempts >= 5) {
            $lockUntil = date('Y-m-d H:i:s', time() + 900);
            try {
                $stmt = $db->prepare("UPDATE users SET failed_login_attempts = ?, lock_until = ? WHERE id = ?");
                $stmt->execute([$failedAttempts, $lockUntil, $user['id']]);
            } catch (Exception $e) {}
            return ['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.'];
        }
        
        try {
            $stmt = $db->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?");
            $stmt->execute([$failedAttempts, $user['id']]);
        } catch (Exception $e) {}
        
        return ['success' => false, 'message' => 'Invalid password'];
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account is not active'];
    }
    
    // Reset failed attempts
    try {
        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, lock_until = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
    } catch (Exception $e) {}
    
    // Check if password change is required
    $mustChangePassword = $user['must_change_password'] ?? false;
    
    createUserSession($user);
    
    // Return info about required password change
    return ['success' => true, 'message' => 'Login successful', 'user' => $user, 'must_change_password' => $mustChangePassword];
}

/**
 * Find student by Student ID only (no email)
 */
function findStudentById($studentId) {
    $db = getDB();
    
    // Check if students table exists
    if (!checkStudentsTableExists($db)) {
        // Try to create it
        if (!ensureStudentsTable($db)) {
            return [
                'success' => false,
                'message' => 'Students table not found. Please contact administrator or import database schema.'
            ];
        }
    }
    
    $stmt = $db->prepare("
        SELECT id, student_id, full_name, email, phone, verification_field, status, image_path 
        FROM students WHERE student_id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        return ['success' => false, 'message' => 'Student ID not found'];
    }
    
    if ($student['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account is not active'];
    }
    
    // Store in session for verification step
    $_SESSION['student_verify_id'] = $student['id'];
    $_SESSION['student_verify_name'] = $student['full_name'];
    $_SESSION['student_verify_field'] = $student['verification_field'] ?? '';
    $_SESSION['student_verify_phone'] = $student['phone'] ?? '';
    $_SESSION['student_verification_step'] = 1;
    $_SESSION['student_verify_attempts'] = 0;
    
    return ['success' => true, 'message' => 'Student found', 'student' => $student];
}

/**
 * Verify student directly with Student ID + verification field (single step)
 */
function verifyStudentDirect($studentId, $verificationValue) {
    $db = getDB();
    
    // Check if students table exists
    if (!checkStudentsTableExists($db)) {
        // Try to create it
        if (!ensureStudentsTable($db)) {
            return [
                'success' => false,
                'message' => 'Students table not found. Please contact administrator or import database schema.'
            ];
        }
    }
    
    // Check rate limiting
    $attempts = $_SESSION['student_verify_attempts'] ?? 0;
    if ($attempts >= 5) {
        return ['success' => false, 'message' => 'Too many failed attempts. Please try again later.'];
    }
    
    // Find student by ID
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $_SESSION['student_verify_attempts'] = $attempts + 1;
        return ['success' => false, 'message' => 'Student ID not found. Please check your student ID.'];
    }
    
    if ($student['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account is not active. Please contact the administrator.'];
    }
    
    // Clean input - only keep digits
    $input = preg_replace('/[^0-9]/i', '', $verificationValue);
    $isValid = false;
    
    // Check verification_field (stored last 4 of DOB/NIC/Phone)
    if (!empty($student['verification_field'])) {
        $storedClean = preg_replace('/[^0-9]/i', '', $student['verification_field']);
        if ($storedClean === $input) {
            $isValid = true;
        }
    }
    
    // Check phone last 4 digits
    if (!$isValid && !empty($student['phone']) && strlen($student['phone']) >= 4) {
        $phoneLast4 = substr(preg_replace('/[^0-9]/i', '', $student['phone']), -4);
        if ($phoneLast4 === $input) {
            $isValid = true;
        }
    }
    
    // Check NIC last 4
    if (!$isValid && !empty($student['nic']) && strlen($student['nic']) >= 4) {
        $nicLast4 = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $student['nic']), -4));
        $inputUpper = strtoupper($input);
        if ($nicLast4 === $inputUpper) {
            $isValid = true;
        }
    }
    
    // Check DOB in format DDMM (last 4 of YYMMDD)
    if (!$isValid && !empty($student['date_of_birth'])) {
        $dobClean = preg_replace('/[^0-9]/i', '', $student['date_of_birth']);
        $dobLast4 = substr($dobClean, -4);
        if ($dobLast4 === $input) {
            $isValid = true;
        }
    }
    
    if (!$isValid) {
        $_SESSION['student_verify_attempts'] = $attempts + 1;
        logActivity(null, 'Student Verify Failed', "Invalid verification for student ID: $studentId");
        $attemptsLeft = 5 - ($attempts + 1);
        return ['success' => false, 'message' => "Invalid verification details. $attemptsLeft attempts remaining."];
    }
    
    // Success - create session
    createStudentSession($student);
    
    // Clear verification data
    unset($_SESSION['student_verify_id'], $_SESSION['student_verify_name'], 
           $_SESSION['student_verify_field'], $_SESSION['student_verify_phone'],
           $_SESSION['student_verification_step'], $_SESSION['student_verify_attempts']);
    
    logActivity(null, 'Student Login', "Student logged in: {$student['student_id']}");
    
    return ['success' => true, 'message' => 'Login successful', 'student' => $student];
}

/**
 * Verify student with second field (DOB/NIC/Phone last 4)
 */
function verifyStudent($verificationValue) {
    $studentId = $_SESSION['student_verify_id'] ?? null;
    $storedField = $_SESSION['student_verify_field'] ?? '';
    $storedPhone = $_SESSION['student_verify_phone'] ?? '';
    
    if (!$studentId) {
        return ['success' => false, 'message' => 'Session expired. Please start over.'];
    }
    
    // Check rate limiting
    $attempts = $_SESSION['student_verify_attempts'] ?? 0;
    if ($attempts >= 5) {
        return ['success' => false, 'message' => 'Too many failed attempts. Please try again later.'];
    }
    
    // Get full student data
    $db = getDB();
    
    // Check if students table exists
    if (!checkStudentsTableExists($db)) {
        if (!ensureStudentsTable($db)) {
            return [
                'success' => false,
                'message' => 'Students table not found. Please contact administrator or import database schema.'
            ];
        }
    }
    
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        return ['success' => false, 'message' => 'Student record not found'];
    }
    
    // Clean input - remove any non-alphanumeric characters
    $input = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $verificationValue));
    $isValid = false;
    
    // Check verification_field (stored last 4 of DOB/NIC/Phone)
    if (!empty($storedField)) {
        $storedClean = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $storedField));
        if ($storedClean === $input) {
            $isValid = true;
        }
    }
    
    // Check phone last 4 digits
    if (!$isValid && !empty($storedPhone) && strlen($storedPhone) >= 4) {
        $phoneLast4 = substr(preg_replace('/[^0-9]/i', '', $storedPhone), -4);
        if ($phoneLast4 === $verificationValue) {
            $isValid = true;
        }
    }
    
    // Check NIC last 4
    if (!$isValid && !empty($student['nic']) && strlen($student['nic']) >= 4) {
        $nicLast4 = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $student['nic']), -4));
        if ($nicLast4 === $input) {
            $isValid = true;
        }
    }
    
    // Check DOB in format DDMM (last 4 of YYMMDD)
    if (!$isValid && !empty($student['date_of_birth'])) {
        $dobClean = preg_replace('/[^0-9]/i', '', $student['date_of_birth']);
        $dobLast4 = substr($dobClean, -4);
        if ($dobLast4 === $input) {
            $isValid = true;
        }
    }
    
    if (!$isValid) {
        $_SESSION['student_verify_attempts'] = $attempts + 1;
        logActivity(null, 'Student Verify Failed', "Invalid verification for student ID: $studentId");
        $attemptsLeft = 5 - ($attempts + 1);
        return ['success' => false, 'message' => "Invalid verification code. $attemptsLeft attempts remaining."];
    }
    
    // Success - create session
    createStudentSession($student);
    
    // Clear verification data
    unset($_SESSION['student_verify_id'], $_SESSION['student_verify_name'], 
           $_SESSION['student_verify_field'], $_SESSION['student_verify_phone'],
           $_SESSION['student_verification_step'], $_SESSION['student_verify_attempts']);
    
    logActivity(null, 'Student Login', "Student logged in: {$student['student_id']}");
    
    return ['success' => true, 'message' => 'Login successful', 'student' => $student];
}

/**
 * Create student session
 */
function createStudentSession($student) {
    session_regenerate_id(true);
    
    $_SESSION['student_id'] = $student['id'];
    $_SESSION['student_number'] = $student['student_id'];
    $_SESSION['student_name'] = $student['full_name'];
    $_SESSION['student_email'] = $student['email'] ?? '';
    $_SESSION['student_image'] = $student['image_path'] ?? '';
    $_SESSION['user_type'] = 'student';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['student_verified'] = true;
}

/**
 * Create admin/lecturer session
 */
function createUserSession($user) {
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['role_slug'] = $user['role_slug'];
    $_SESSION['user_type'] = $user['role_slug'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    logActivity($user['id'], 'Login', 'User logged in successfully');
}

/**
 * Validate session
 */
function validateSession() {
    if (!isLoggedIn() && !isStudentLoggedIn()) return false;
    
    if (isset($_SESSION['last_activity'])) {
        $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 1800;
        if (time() - $_SESSION['last_activity'] > $timeout) return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Logout
 */
function logoutUser() {
    $userId = $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? null;
    $userType = $_SESSION['user_type'] ?? 'unknown';
    
    if ($userType !== 'student') {
        logActivity($userId, 'Logout', 'User logged out');
    } else {
        logActivity(null, 'Student Logout', "Student {$_SESSION['student_number']} logged out");
    }
    
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    
    session_destroy();
}

/**
 * Get current user (admin/lecturer)
 */
function getCurrentUserAuth() {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, r.role_name, r.role_slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Get current student
 */
function getCurrentStudent() {
    if (!isStudentLoggedIn()) return null;
    
    $db = getDB();
    
    // Check if students table exists
    if (!checkStudentsTableExists($db)) {
        // Try to create it
        ensureStudentsTable($db);
    }
    
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    return $stmt->fetch();
}

/**
 * Require student login
 */
function requireStudentLogin() {
    if (!isStudentLoggedIn()) {
        setFlash('warning', 'Please verify your identity to access this page.');
        redirect(BASE_URL . '/auth/login.php');
    }
    validateSession();
}

/**
 * Admin: Create lecturer account
 */
function createLecturerAccount($fullName, $email, $phone, $department, $qualification, $tempPassword = null) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    $username = strtolower(explode('@', $email)[0]) . rand(100, 999);
    $password = $tempPassword ?: 'Lecturer@' . date('Y');
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role_id, status, must_change_password) VALUES (?, ?, ?, (SELECT id FROM roles WHERE role_slug = 'lecturer'), 'active', TRUE)");
        $stmt->execute([$username, $email, $hashedPassword]);
        $userId = $db->lastInsertId();
        
        $lecturerId = 'LEC-' . str_pad($db->query("SELECT COUNT(*) + 1 FROM lecturers")->fetchColumn(), 4, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("INSERT INTO lecturers (lecturer_id, full_name, email, phone, department, qualification, status, user_id) VALUES (?, ?, ?, ?, ?, ?, 'active', ?)");
        $stmt->execute([$lecturerId, $fullName, $email, $phone, $department, $qualification, $userId]);
        
        logActivity($_SESSION['user_id'], 'Lecturer Created', "Created lecturer: $email");
        
        return ['success' => true, 'message' => 'Lecturer account created successfully', 'temp_password' => $password, 'lecturer_id' => $lecturerId];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to create lecturer account'];
    }
}

/**
 * Admin: Reset lecturer password
 */
function resetLecturerPassword($userId, $newPassword = null) {
    $db = getDB();
    
    if ($newPassword === null) {
        $newPassword = bin2hex(random_bytes(5));
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ?, must_change_password = TRUE, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    logActivity($_SESSION['user_id'], 'Password Reset', "Reset password for user ID: $userId");
    
    return ['success' => true, 'message' => 'Password reset successfully', 'new_password' => $newPassword];
}

/**
 * Admin: Force password change
 */
function forcePasswordChange($userId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET must_change_password = TRUE WHERE id = ?");
    $stmt->execute([$userId]);
    
    return ['success' => true, 'message' => 'User will be required to change password on next login'];
}

/**
 * Admin: Update account status
 */
function updateAccountStatus($userId, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $userId]);
    
    logActivity($_SESSION['user_id'], 'Account Status', "Changed status to $status for user ID: $userId");
    
    return ['success' => true, 'message' => "Account $status successfully"];
}

/**
 * Lecturer: Change own password
 */
function changeOwnPassword($userId, $currentPassword, $newPassword) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $valid = false;
    if (substr($user['password'], 0, 4) === '$2y$') {
        $valid = password_verify($currentPassword, $user['password']);
    } else if ($user['password'] === $currentPassword) {
        $valid = true;
    }
    
    if (!$valid) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ?, must_change_password = FALSE, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    logActivity($userId, 'Password Changed', 'User changed their own password');
    
    return ['success' => true, 'message' => 'Password changed successfully'];
}
