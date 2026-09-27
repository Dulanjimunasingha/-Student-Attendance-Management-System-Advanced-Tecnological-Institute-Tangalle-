-- =====================================================
-- ATIT Attendance Management System
-- SQL Schema Fix for Students Table
-- =====================================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS atit_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atit_attendance;

-- =====================================================
-- STUDENTS TABLE (If not already exists)
-- =====================================================

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

-- Indexes
CREATE INDEX idx_students_student_id ON students(student_id);
CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_students_status ON students(status);

-- =====================================================
-- SAMPLE STUDENTS (For testing login)
-- =====================================================

INSERT INTO students (student_id, full_name, email, phone, batch, academic_year, email_verified, status, verification_field) VALUES
('ATIT-2024-0001', 'Kamal Rathnayake', 'kamal.rathnayake@student.atit.edu.lk', '0711234567', '2024', 1, TRUE, 'active', '0315'),
('ATIT-2024-0002', 'Nisha Fernando', 'nisha.fernando@student.atit.edu.lk', '0712345678', '2024', 1, TRUE, 'active', '1225'),
('ATIT-2024-0003', 'Roshan Silva', 'roshan.silva@student.atit.edu.lk', '0713456789', '2024', 1, TRUE, 'active', '0891'),
('ATIT-2024-0004', 'Dilini Perera', 'dilini.perera@student.atit.edu.lk', '0714567890', '2024', 1, TRUE, 'active', '4567'),
('ATIT-2024-0005', 'Kasun Jayawardena', 'kasun.jayawardena@student.atit.edu.lk', '0715678901', '2024', 1, TRUE, 'active', '8901');

-- =====================================================
-- NOTE:
-- Verification field contains last 4 digits of:
-- - Date of Birth (DDMM format)
-- - NIC number
-- - Phone number
--
-- For testing, use:
-- Student ID: ATIT-2024-0001
-- Verification: 0315 (DOB last 4 digits)
-- =====================================================