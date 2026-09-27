-- =====================================================
-- Advanced Technological Institute of Tangalle
-- Attendance Management System Database
-- =====================================================

-- Drop database if exists (clean slate)
DROP DATABASE IF EXISTS atit_attendance;

-- Create database
CREATE DATABASE atit_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atit_attendance;

-- =====================================================
-- TABLES
-- =====================================================

-- Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Users Table (for Admin and Lecturer accounts)
CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB;

-- Students Table (No user account - accessed via OTP)
CREATE TABLE IF NOT EXISTS students (
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
) ENGINE=InnoDB;

-- Student OTP Verification Table
CREATE TABLE IF NOT EXISTS student_otp_verifications (
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
) ENGINE=InnoDB;

-- Lecturers Table (Linked to users for password auth)
CREATE TABLE IF NOT EXISTS lecturers (
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
) ENGINE=InnoDB;

-- Courses Table
CREATE TABLE IF NOT EXISTS courses (
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
) ENGINE=InnoDB;

-- Enrollments Table
CREATE TABLE IF NOT EXISTS enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_date DATE NOT NULL,
    completion_status ENUM('not_started', 'in_progress', 'completed', 'dropped') DEFAULT 'not_started',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
) ENGINE=InnoDB;

-- Lecturer Course Assignments
CREATE TABLE IF NOT EXISTS lecturer_course_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lecturer_id INT NOT NULL,
    course_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (lecturer_id, course_id)
) ENGINE=InnoDB;

-- Attendance Sessions Table
CREATE TABLE IF NOT EXISTS attendance_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    session_date DATE NOT NULL,
    lesson_topic VARCHAR(255),
    notes TEXT,
    `is_makeup_session` BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session (course_id, session_date)
) ENGINE=InnoDB;

-- Attendance Records Table
CREATE TABLE IF NOT EXISTS attendance_records (
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
) ENGINE=InnoDB;

-- Announcements Table
CREATE TABLE IF NOT EXISTS announcements (
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
) ENGINE=InnoDB;

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- INDEXES
-- =====================================================

CREATE INDEX idx_students_student_id ON students(student_id);
CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_student_otp_student ON student_otp_verifications(student_id);
CREATE INDEX idx_student_otp_code ON student_otp_verifications(otp_code);
CREATE INDEX idx_student_otp_expires ON student_otp_verifications(expires_at);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_lecturers_lecturer_id ON lecturers(lecturer_id);
CREATE INDEX idx_lecturers_status ON lecturers(status);
CREATE INDEX idx_courses_status ON courses(status);
CREATE INDEX idx_enrollments_student ON enrollments(student_id);
CREATE INDEX idx_enrollments_course ON enrollments(course_id);
CREATE INDEX idx_lecturer_assignments_lecturer ON lecturer_course_assignments(lecturer_id);
CREATE INDEX idx_lecturer_assignments_course ON lecturer_course_assignments(course_id);
CREATE INDEX idx_attendance_sessions_course ON attendance_sessions(course_id);
CREATE INDEX idx_attendance_sessions_lecturer ON attendance_sessions(lecturer_id);
CREATE INDEX idx_attendance_sessions_date ON attendance_sessions(session_date);
CREATE INDEX idx_attendance_records_session ON attendance_records(session_id);
CREATE INDEX idx_attendance_records_student ON attendance_records(student_id);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_created ON activity_logs(created_at);
CREATE INDEX idx_announcements_publish ON announcements(publish_date);
CREATE INDEX idx_announcements_expiry ON announcements(expiry_date);

-- =====================================================
-- DEFAULT DATA: ROLES
-- =====================================================

INSERT INTO roles (role_name, role_slug, description) VALUES
('Administrator', 'admin', 'System administrator with full access'),
('Lecturer', 'lecturer', 'Teaching staff who can mark attendance');

-- =====================================================
-- DEFAULT DATA: ADMIN USER
-- =====================================================

-- Default admin: admin@atit.edu.lk / admin123
INSERT INTO users (username, email, password, role_id, status) VALUES
('admin', 'admin@atit.edu.lk', '$2y$10$DY9NaOWuW8DYvAkAVPrpC.Bjb.NAAY/rfQXRVu6Zcq4DKEFnLAxCi', 1, 'active');

-- =====================================================
-- SAMPLE DATA: LECTURERS (with user accounts)
-- =====================================================

-- Insert lecturer users (password: lecturer123)
INSERT INTO users (username, email, password, role_id, status) VALUES
('dr.silva', 'dr.silva@atit.edu.lk', '$2y$10$eF5Q7k9mNpQ8r4tU6vW0xA2bL5cD8eF3gH4iJ5kL6mN7oP8qR9sT0u', 2, 'active'),
('ms.perera', 'ms.perera@atit.edu.lk', '$2y$10$eF5Q7k9mNpQ8r4tU6vW0xA2bL5cD8eF3gH4iJ5kL6mN7oP8qR9sT0u', 2, 'active'),
('mr.jayawardena', 'mr.jayawardena@atit.edu.lk', '$2y$10$eF5Q7k9mNpQ8r4tU6vW0xA2bL5cD8eF3gH4iJ5kL6mN7oP8qR9sT0u', 2, 'active');

-- Insert lecturer profiles
INSERT INTO lecturers (lecturer_id, full_name, email, phone, department, qualification, status, user_id) VALUES
('LEC-0001', 'Dr. Kumara Silva', 'dr.silva@atit.edu.lk', '077-123-4567', 'Computer Science', 'PhD in Computer Science', 'active', 2),
('LEC-0002', 'Ms. Priya Perera', 'ms.perera@atit.edu.lk', '077-234-5678', 'Information Technology', 'MSc in IT', 'active', 3),
('LEC-0003', 'Mr. Anura Jayawardena', 'mr.jayawardena@atit.edu.lk', '077-345-6789', 'Mathematics', 'MSc in Mathematics', 'active', 4);

-- =====================================================
-- SAMPLE DATA: STUDENTS (No user accounts - OTP access only)
-- =====================================================

INSERT INTO students (student_id, full_name, email, phone, batch, academic_year, email_verified, status) VALUES
('ATIT-2024-0001', 'Kamal Rathnayake', 'kamal.rathnayake@student.atit.edu.lk', '071-123-4567', '2024', 1, TRUE, 'active'),
('ATIT-2024-0002', 'Nisha Fernando', 'nisha.fernando@student.atit.edu.lk', '071-234-5678', '2024', 1, TRUE, 'active'),
('ATIT-2024-0003', 'Roshan Silva', 'roshan.silva@student.atit.edu.lk', '071-345-6789', '2024', 1, TRUE, 'active'),
('ATIT-2024-0004', 'Dilini Perera', 'dilini.perera@student.atit.edu.lk', '071-456-7890', '2024', 1, TRUE, 'active'),
('ATIT-2024-0005', 'Kasun Jayawardena', 'kasun.jayawardena@student.atit.edu.lk', '071-567-8901', '2024', 1, TRUE, 'active'),
('ATIT-2024-0006', 'Sachini Dissanayake', 'sachini.dissanayake@student.atit.edu.lk', '071-678-9012', '2024', 1, TRUE, 'active'),
('ATIT-2024-0007', 'Chamod Weerawardena', 'chamod.weerawardena@student.atit.edu.lk', '071-789-0123', '2024', 1, TRUE, 'active'),
('ATIT-2024-0008', 'Thiwanka Rajapaksha', 'thiwanka.rajapaksha@student.atit.edu.lk', '071-890-1234', '2024', 1, TRUE, 'active');

-- =====================================================
-- SAMPLE DATA: COURSES
-- =====================================================

INSERT INTO courses (course_code, course_name, description, department, credits, min_attendance_percentage, status) VALUES
('CS101', 'Introduction to Programming', 'Basic programming concepts using Python', 'Computer Science', 3, 75.00, 'active'),
('CS102', 'Data Structures and Algorithms', 'Fundamental data structures and algorithm design', 'Computer Science', 4, 75.00, 'active'),
('IT101', 'Web Development', 'HTML, CSS, JavaScript and modern web frameworks', 'Information Technology', 3, 75.00, 'active'),
('MATH101', 'Applied Mathematics', 'Calculus and linear algebra for computing', 'Mathematics', 3, 75.00, 'active'),
('IT102', 'Database Systems', 'Relational databases and SQL', 'Information Technology', 3, 75.00, 'active');

-- =====================================================
-- SAMPLE DATA: ENROLLMENTS
-- =====================================================

INSERT INTO enrollments (student_id, course_id, enrolled_date, completion_status) VALUES
(1, 1, '2024-01-15', 'in_progress'),
(1, 2, '2024-01-15', 'in_progress'),
(1, 3, '2024-01-15', 'in_progress'),
(1, 5, '2024-01-15', 'in_progress'),
(2, 1, '2024-01-15', 'in_progress'),
(2, 2, '2024-01-15', 'in_progress'),
(2, 3, '2024-01-15', 'in_progress'),
(3, 1, '2024-01-15', 'in_progress'),
(3, 2, '2024-01-15', 'in_progress'),
(3, 4, '2024-01-15', 'in_progress'),
(4, 1, '2024-01-15', 'in_progress'),
(4, 3, '2024-01-15', 'in_progress'),
(4, 5, '2024-01-15', 'in_progress'),
(5, 2, '2024-01-15', 'in_progress'),
(5, 4, '2024-01-15', 'in_progress'),
(5, 5, '2024-01-15', 'in_progress'),
(6, 1, '2024-01-15', 'in_progress'),
(6, 2, '2024-01-15', 'in_progress'),
(6, 3, '2024-01-15', 'in_progress'),
(7, 1, '2024-01-15', 'in_progress'),
(7, 5, '2024-01-15', 'in_progress'),
(8, 2, '2024-01-15', 'in_progress'),
(8, 3, '2024-01-15', 'in_progress'),
(8, 4, '2024-01-15', 'in_progress');

-- =====================================================
-- SAMPLE DATA: LECTURER COURSE ASSIGNMENTS
-- =====================================================

INSERT INTO lecturer_course_assignments (lecturer_id, course_id, assigned_date) VALUES
(1, 1, '2024-01-10'),
(1, 2, '2024-01-10'),
(2, 3, '2024-01-10'),
(2, 5, '2024-01-10'),
(3, 4, '2024-01-10');

-- =====================================================
-- SAMPLE DATA: ATTENDANCE SESSIONS
-- =====================================================

-- CS101 Sessions
INSERT INTO attendance_sessions (course_id, lecturer_id, session_date, lesson_topic, notes) VALUES
(1, 1, '2024-01-22', 'Introduction to Python', 'First Python session'),
(1, 1, '2024-01-23', 'Variables and Data Types', ''),
(1, 1, '2024-01-24', 'Control Flow - If Statements', ''),
(1, 1, '2024-01-25', 'Loops - While and For', ''),
(1, 1, '2024-01-29', 'Functions - Part 1', ''),
(1, 1, '2024-01-30', 'Functions - Part 2', ''),
(1, 1, '2024-01-31', 'Lists and Tuples', ''),
(1, 1, '2024-02-01', 'Dictionaries', ''),
(1, 1, '2024-02-05', 'File Handling', ''),
(1, 1, '2024-02-06', 'Exception Handling', ''),

-- CS102 Sessions
(2, 1, '2024-01-22', 'Arrays and Linked Lists', ''),
(2, 1, '2024-01-23', 'Stacks and Queues', ''),
(2, 1, '2024-01-24', 'Trees - Binary Trees', ''),
(2, 1, '2024-01-25', 'Tree Traversals', ''),
(2, 1, '2024-01-29', 'Binary Search Trees', ''),
(2, 1, '2024-01-30', 'Heaps and Priority Queues', ''),
(2, 1, '2024-01-31', 'Graphs - Introduction', ''),
(2, 1, '2024-02-01', 'BFS and DFS', ''),
(2, 1, '2024-02-05', 'Sorting Algorithms', ''),
(2, 1, '2024-02-06', 'Search Algorithms', ''),

-- IT101 Sessions
(3, 2, '2024-01-22', 'HTML Basics', ''),
(3, 2, '2024-01-23', 'CSS Fundamentals', ''),
(3, 2, '2024-01-24', 'CSS Flexbox', ''),
(3, 2, '2024-01-25', 'JavaScript - Variables', ''),
(3, 2, '2024-01-29', 'JavaScript - Functions', ''),
(3, 2, '2024-01-30', 'DOM Manipulation', ''),
(3, 2, '2024-01-31', 'Events and Listeners', ''),
(3, 2, '2024-02-01', 'Form Validation', ''),
(3, 2, '2024-02-05', 'AJAX Basics', ''),
(3, 2, '2024-02-06', 'JSON Handling', ''),

-- MATH101 Sessions
(4, 3, '2024-01-22', 'Limits and Continuity', ''),
(4, 3, '2024-01-23', 'Derivatives - Rules', ''),
(4, 3, '2024-01-24', 'Chain Rule', ''),
(4, 3, '2024-01-25', 'Integration - Basics', ''),
(4, 3, '2024-01-29', 'Integration Techniques', ''),
(4, 3, '2024-01-30', 'Matrices - Operations', ''),
(4, 3, '2024-01-31', 'Determinants', ''),
(4, 3, '2024-02-01', 'Eigenvalues', ''),
(4, 3, '2024-02-05', 'Vectors', ''),
(4, 3, '2024-02-06', 'Vector Spaces', ''),

-- IT102 Sessions
(5, 2, '2024-01-22', 'Database Concepts', ''),
(5, 2, '2024-01-23', 'ER Modeling', ''),
(5, 2, '2024-01-24', 'SQL - SELECT', ''),
(5, 2, '2024-01-25', 'SQL - WHERE Clause', ''),
(5, 2, '2024-01-29', 'SQL - JOINs', ''),
(5, 2, '2024-01-30', 'SQL - Aggregations', ''),
(5, 2, '2024-01-31', 'Subqueries', ''),
(5, 2, '2024-02-01', 'Database Design', ''),
(5, 2, '2024-02-05', 'Normalization', ''),
(5, 2, '2024-02-06', 'Transactions', '');

-- =====================================================
-- SAMPLE DATA: ATTENDANCE RECORDS
-- =====================================================

-- Course 1 (CS101) attendance records
INSERT INTO attendance_records (session_id, student_id, attendance_status, marked_by) VALUES
(1, 1, 'present', 2), (2, 1, 'present', 2), (3, 1, 'present', 2), (4, 1, 'present', 2),
(5, 1, 'present', 2), (6, 1, 'present', 2), (7, 1, 'late', 2), (8, 1, 'present', 2),
(9, 1, 'present', 2), (10, 1, 'present', 2),
(1, 2, 'present', 2), (2, 2, 'present', 2), (3, 2, 'present', 2), (4, 2, 'absent', 2),
(5, 2, 'present', 2), (6, 2, 'present', 2), (7, 2, 'present', 2), (8, 2, 'late', 2),
(9, 2, 'present', 2), (10, 2, 'present', 2),
(1, 3, 'present', 2), (2, 3, 'present', 2), (3, 3, 'present', 2), (4, 3, 'present', 2),
(5, 3, 'present', 2), (6, 3, 'present', 2), (7, 3, 'present', 2), (8, 3, 'present', 2),
(9, 3, 'present', 2), (10, 3, 'present', 2),
(1, 4, 'present', 2), (2, 4, 'present', 2), (3, 4, 'present', 2), (4, 4, 'present', 2),
(5, 4, 'absent', 2), (6, 4, 'present', 2), (7, 4, 'present', 2), (8, 4, 'present', 2),
(9, 4, 'late', 2), (10, 4, 'present', 2),
(1, 6, 'present', 2), (2, 6, 'present', 2), (3, 6, 'present', 2), (4, 6, 'present', 2),
(5, 6, 'present', 2), (6, 6, 'present', 2), (7, 6, 'present', 2), (8, 6, 'present', 2),
(9, 6, 'present', 2), (10, 6, 'present', 2),
(1, 7, 'present', 2), (2, 7, 'present', 2), (3, 7, 'absent', 2), (4, 7, 'present', 2),
(5, 7, 'present', 2), (6, 7, 'late', 2), (7, 7, 'present', 2), (8, 7, 'present', 2),
(9, 7, 'present', 2), (10, 7, 'present', 2);

-- Course 2 (CS102) attendance records
INSERT INTO attendance_records (session_id, student_id, attendance_status, marked_by) VALUES
(11, 1, 'present', 2), (12, 1, 'present', 2), (13, 1, 'present', 2), (14, 1, 'present', 2),
(15, 1, 'present', 2), (16, 1, 'present', 2), (17, 1, 'present', 2), (18, 1, 'late', 2),
(19, 1, 'present', 2), (20, 1, 'present', 2),
(11, 2, 'present', 2), (12, 2, 'present', 2), (13, 2, 'present', 2), (14, 2, 'present', 2),
(15, 2, 'present', 2), (16, 2, 'present', 2), (17, 2, 'present', 2), (18, 2, 'present', 2),
(19, 2, 'present', 2), (20, 2, 'present', 2),
(11, 3, 'present', 2), (12, 3, 'present', 2), (13, 3, 'present', 2), (14, 3, 'present', 2),
(15, 3, 'present', 2), (16, 3, 'present', 2), (17, 3, 'present', 2), (18, 3, 'present', 2),
(19, 3, 'present', 2), (20, 3, 'present', 2),
(11, 5, 'present', 2), (12, 5, 'present', 2), (13, 5, 'present', 2), (14, 5, 'absent', 2),
(15, 5, 'present', 2), (16, 5, 'present', 2), (17, 5, 'present', 2), (18, 5, 'present', 2),
(19, 5, 'present', 2), (20, 5, 'present', 2),
(11, 8, 'present', 2), (12, 8, 'present', 2), (13, 8, 'present', 2), (14, 8, 'present', 2),
(15, 8, 'present', 2), (16, 8, 'present', 2), (17, 8, 'present', 2), (18, 8, 'late', 2),
(19, 8, 'present', 2), (20, 8, 'present', 2);

-- Course 3 (IT101) attendance records
INSERT INTO attendance_records (session_id, student_id, attendance_status, marked_by) VALUES
(21, 1, 'present', 3), (22, 1, 'present', 3), (23, 1, 'present', 3), (24, 1, 'present', 3),
(25, 1, 'present', 3), (26, 1, 'present', 3), (27, 1, 'present', 3), (28, 1, 'present', 3),
(29, 1, 'present', 3), (30, 1, 'present', 3),
(21, 2, 'present', 3), (22, 2, 'present', 3), (23, 2, 'present', 3), (24, 2, 'present', 3),
(25, 2, 'absent', 3), (26, 2, 'present', 3), (27, 2, 'present', 3), (28, 2, 'present', 3),
(29, 2, 'present', 3), (30, 2, 'present', 3),
(21, 4, 'present', 3), (22, 4, 'present', 3), (23, 4, 'present', 3), (24, 4, 'present', 3),
(25, 4, 'present', 3), (26, 4, 'present', 3), (27, 4, 'present', 3), (28, 4, 'present', 3),
(29, 4, 'present', 3), (30, 4, 'present', 3),
(21, 6, 'present', 3), (22, 6, 'present', 3), (23, 6, 'present', 3), (24, 6, 'present', 3),
(25, 6, 'present', 3), (26, 6, 'present', 3), (27, 6, 'present', 3), (28, 6, 'present', 3),
(29, 6, 'present', 3), (30, 6, 'present', 3),
(21, 8, 'present', 3), (22, 8, 'present', 3), (23, 8, 'present', 3), (24, 8, 'present', 3),
(25, 8, 'present', 3), (26, 8, 'present', 3), (27, 8, 'present', 3), (28, 8, 'present', 3),
(29, 8, 'present', 3), (30, 8, 'present', 3);

-- Course 4 (MATH101) attendance records
INSERT INTO attendance_records (session_id, student_id, attendance_status, marked_by) VALUES
(31, 3, 'present', 4), (32, 3, 'present', 4), (33, 3, 'present', 4), (34, 3, 'present', 4),
(35, 3, 'present', 4), (36, 3, 'present', 4), (37, 3, 'present', 4), (38, 3, 'present', 4),
(39, 3, 'present', 4), (40, 3, 'present', 4),
(31, 5, 'present', 4), (32, 5, 'present', 4), (33, 5, 'present', 4), (34, 5, 'present', 4),
(35, 5, 'present', 4), (36, 5, 'present', 4), (37, 5, 'present', 4), (38, 5, 'present', 4),
(39, 5, 'present', 4), (40, 5, 'present', 4),
(31, 8, 'present', 4), (32, 8, 'present', 4), (33, 8, 'present', 4), (34, 8, 'absent', 4),
(35, 8, 'present', 4), (36, 8, 'present', 4), (37, 8, 'present', 4), (38, 8, 'present', 4),
(39, 8, 'present', 4), (40, 8, 'present', 4);

-- Course 5 (IT102) attendance records
INSERT INTO attendance_records (session_id, student_id, attendance_status, marked_by) VALUES
(41, 1, 'present', 3), (42, 1, 'present', 3), (43, 1, 'present', 3), (44, 1, 'present', 3),
(45, 1, 'present', 3), (46, 1, 'present', 3), (47, 1, 'present', 3), (48, 1, 'present', 3),
(49, 1, 'present', 3), (50, 1, 'present', 3),
(41, 4, 'present', 3), (42, 4, 'present', 3), (43, 4, 'absent', 3), (44, 4, 'present', 3),
(45, 4, 'present', 3), (46, 4, 'late', 3), (47, 4, 'present', 3), (48, 4, 'present', 3),
(49, 4, 'present', 3), (50, 4, 'present', 3),
(41, 5, 'present', 3), (42, 5, 'present', 3), (43, 5, 'present', 3), (44, 5, 'present', 3),
(45, 5, 'present', 3), (46, 5, 'present', 3), (47, 5, 'present', 3), (48, 5, 'present', 3),
(49, 5, 'present', 3), (50, 5, 'present', 3),
(41, 7, 'present', 3), (42, 7, 'present', 3), (43, 7, 'present', 3), (44, 7, 'present', 3),
(45, 7, 'present', 3), (46, 7, 'present', 3), (47, 7, 'present', 3), (48, 7, 'present', 3),
(49, 7, 'present', 3), (50, 7, 'present', 3);

-- =====================================================
-- SAMPLE DATA: ANNOUNCEMENTS
-- =====================================================

INSERT INTO announcements (title, content, publish_date, expiry_date, audience, priority, is_pinned, created_by) VALUES
('Welcome to ATIT Attendance System', 'Dear Students and Staff, Welcome to the new Academic Year 2024! We are pleased to introduce our new Attendance Management System. Please ensure you log in regularly to check your attendance records.', '2024-01-15', '2024-12-31', 'all', 'high', TRUE, 1),
('Examination Schedule Notice', 'The midterm examinations will be conducted from March 15-30, 2024. Students must maintain at least 75% attendance to be eligible for examinations. Please check your attendance regularly.', '2024-03-01', '2024-04-01', 'students', 'high', TRUE, 1),
('New Course Registration', 'Course registration for the second semester will begin on April 1, 2024. Students are advised to meet their academic advisors before the registration deadline.', '2024-03-10', '2024-05-01', 'students', 'medium', FALSE, 1),
('Staff Meeting Announcement', 'There will be a staff meeting on March 20, 2024 at 2:00 PM in the main conference hall. All lecturers are requested to attend.', '2024-03-15', '2024-03-25', 'lecturers', 'medium', FALSE, 1),
('System Maintenance', 'The attendance system will undergo scheduled maintenance on March 25, 2024 from 10:00 PM to 12:00 AM. Please plan your attendance marking accordingly.', '2024-03-20', '2024-03-26', 'all', 'low', FALSE, 1);

-- =====================================================
-- SAMPLE DATA: ACTIVITY LOGS
-- =====================================================

INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES
(1, 'System Setup', 'Initial database setup completed', '127.0.0.1', '2024-01-10 09:00:00'),
(1, 'User Created', 'Admin user created', '127.0.0.1', '2024-01-10 09:05:00'),
(2, 'Login', 'Lecturer logged in', '192.168.1.100', '2024-01-22 08:00:00'),
(2, 'Attendance Marked', 'Marked attendance for CS101', '192.168.1.100', '2024-01-22 08:15:00'),
(3, 'Login', 'Lecturer logged in', '192.168.1.101', '2024-01-23 08:30:00');

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================

-- Database setup complete
