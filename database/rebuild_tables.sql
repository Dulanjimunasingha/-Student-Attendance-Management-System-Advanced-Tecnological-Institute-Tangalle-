-- Fix corrupted InnoDB tablespace issues
-- Drop all tables explicitly then recreate

USE atit_attendance;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in order (respecting foreign keys)
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS attendance_sessions;
DROP TABLE IF EXISTS lecturer_course_assignments;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS student_otp_verifications;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS lectures; -- Note: might be misspelled
DROP TABLE IF EXISTS lecturers;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- Now create tables fresh (these are from attendance_system.sql)
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
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

CREATE TABLE students (
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

CREATE TABLE student_otp_verifications (
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

CREATE TABLE lecturers (
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

CREATE TABLE courses (
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

CREATE TABLE enrollments (
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

CREATE TABLE lecturer_course_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lecturer_id INT NOT NULL,
    course_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (lecturer_id, course_id)
) ENGINE=InnoDB;

CREATE TABLE attendance_sessions (
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

CREATE TABLE attendance_records (
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

CREATE TABLE announcements (
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

CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Indexes
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

-- Default Data: Roles
INSERT INTO roles (role_name, role_slug, description) VALUES
('Administrator', 'admin', 'System administrator with full access'),
('Lecturer', 'lecturer', 'Teaching staff who can mark attendance');

-- Default Data: Admin User
INSERT INTO users (username, email, password, role_id, status) VALUES
('admin', 'admin@atit.edu.lk', '$2y$10$DY9NaOWuW8DYvAkAVPrpC.Bjb.NAAY/rfQXRVu6Zcq4DKEFnLAxCi', 1, 'active');

-- Sample Data: Lecturers
INSERT INTO users (username, email, password, role_id, status) VALUES
('dr.silva', 'dr.silva@atit.edu.lk', '$2y$10$eF5Q7k9mNpQ8r4tU6vW0xA2bL5cD8eF3gH4iJ5kL6mN7oP8qR9sT0u', 2, 'active'),
('ms.perera', 'ms.perera@atit.edu.lk', '$2y$10$eF5Q7k9mNpQ8r4tU6vW0xA2bL5cD8eF3gH4iJ5kL6mN7oP8qR9sT0u', 2, 'active'),
('mr.jayawardena', 'mr.jayawardena@atit.edu.lk', '$2y$10$eF5Q7k9mNpQ8r4tU6vW0xA2bL5cD8eF3gH4iJ5kL6mN7oP8qR9sT0u', 2, 'active');

INSERT INTO lecturers (lecturer_id, full_name, email, phone, department, qualification, status, user_id) VALUES
('LEC-0001', 'Dr. Kumara Silva', 'dr.silva@atit.edu.lk', '077-123-4567', 'Computer Science', 'PhD in Computer Science', 'active', 2),
('LEC-0002', 'Ms. Priya Perera', 'ms.perera@atit.edu.lk', '077-234-5678', 'Information Technology', 'MSc in IT', 'active', 3),
('LEC-0003', 'Mr. Anura Jayawardena', 'mr.jayawardena@atit.edu.lk', '077-345-6789', 'Mathematics', 'MSc in Mathematics', 'active', 4);

-- Sample Data: Students
INSERT INTO students (student_id, full_name, email, phone, batch, academic_year, email_verified, status) VALUES
('ATIT-2024-0001', 'Kamal Rathnayake', 'kamal.rathnayake@student.atit.edu.lk', '071-123-4567', '2024', 1, TRUE, 'active'),
('ATIT-2024-0002', 'Nisha Fernando', 'nisha.fernando@student.atit.edu.lk', '071-234-5678', '2024', 1, TRUE, 'active'),
('ATIT-2024-0003', 'Roshan Silva', 'roshan.silva@student.atit.edu.lk', '071-345-6789', '2024', 1, TRUE, 'active'),
('ATIT-2024-0004', 'Dilini Perera', 'dilini.perera@student.atit.edu.lk', '071-456-7890', '2024', 1, TRUE, 'active'),
('ATIT-2024-0005', 'Kasun Jayawardena', 'kasun.jayawardena@student.atit.edu.lk', '071-567-8901', '2024', 1, TRUE, 'active'),
('ATIT-2024-0006', 'Sachini Dissanayake', 'sachini.dissanayake@student.atit.edu.lk', '071-678-9012', '2024', 1, TRUE, 'active'),
('ATIT-2024-0007', 'Chamod Weerawardena', 'chamod.weerawardena@student.atit.edu.lk', '071-789-0123', '2024', 1, TRUE, 'active'),
('ATIT-2024-0008', 'Thiwanka Rajapaksha', 'thiwanka.rajapaksha@student.atit.edu.lk', '071-890-1234', '2024', 1, TRUE, 'active');

-- Sample Data: Courses
INSERT INTO courses (course_code, course_name, description, department, credits, min_attendance_percentage, status) VALUES
('CS101', 'Introduction to Programming', 'Basic programming concepts using Python', 'Computer Science', 3, 75.00, 'active'),
('CS102', 'Data Structures and Algorithms', 'Fundamental data structures and algorithm design', 'Computer Science', 4, 75.00, 'active'),
('IT101', 'Web Development', 'HTML, CSS, JavaScript and modern web frameworks', 'Information Technology', 3, 75.00, 'active'),
('MATH101', 'Applied Mathematics', 'Calculus and linear algebra for computing', 'Mathematics', 3, 75.00, 'active'),
('IT102', 'Database Systems', 'Relational databases and SQL', 'Information Technology', 3, 75.00, 'active');

-- Sample Data: Enrollments
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

-- Sample Data: Lecturer Course Assignments
INSERT INTO lecturer_course_assignments (lecturer_id, course_id, assigned_date) VALUES
(1, 1, '2024-01-10'),
(1, 2, '2024-01-10'),
(2, 3, '2024-01-10'),
(2, 5, '2024-01-10'),
(3, 4, '2024-01-10');

-- Sample Data: Attendance Sessions
INSERT INTO attendance_sessions (course_id, lecturer_id, session_date, lesson_topic, notes) VALUES
-- CS101 Sessions
(1, 1, '2024-01-22', 'Introduction to Python', 'First Python session'),
(1, 1, '2024-01-23', 'Variables and Data Types', ''),
(1, 1, '2024-01-24', 'Control Flow - If Statements', ''),
-- CS102 Sessions
(2, 1, '2024-01-22', 'Arrays and Linked Lists', ''),
(2, 1, '2024-01-23', 'Stacks and Queues', ''),
-- IT101 Sessions
(3, 2, '2024-01-22', 'HTML Basics', ''),
(3, 2, '2024-01-23', 'CSS Fundamentals', ''),
-- MATH101 Sessions
(4, 3, '2024-01-22', 'Limits and Continuity', ''),
(4, 3, '2024-01-23', 'Derivatives - Rules', ''),
-- IT102 Sessions
(5, 2, '2024-01-22', 'Database Concepts', ''),
(5, 2, '2024-01-23', 'ER Modeling', '');

-- Sample Data: Announcements
INSERT INTO announcements (title, content, publish_date, expiry_date, audience, priority, is_pinned, created_by) VALUES
('Welcome to ATIT Attendance System', 'Dear Students and Staff, Welcome to the new Academic Year 2024!', '2024-01-15', '2024-12-31', 'all', 'high', TRUE, 1);

-- Sample Data: Activity Logs
INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES
(1, 'System Setup', 'Database tables recreated', '127.0.0.1', NOW());
