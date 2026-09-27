-- =====================================================
-- Advanced Technological Institute of Tangalle
-- Attendance Management System Database Update
-- =====================================================

USE atit_attendance; 

-- =====================================================
-- UPDATE USERS TABLE
-- =====================================================

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `failed_login_attempts` INT DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `lock_until` DATETIME;

-- =====================================================
-- UPDATE STUDENTS TABLE - Add verification fields
-- =====================================================

ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `date_of_birth` DATE;
ALTER TABLE `students` ADD COLUMN `nic` VARCHAR(20);
ALTER TABLE `students` ADD COLUMN `phone` VARCHAR(20);
ALTER TABLE `students` ADD COLUMN `verification_field` VARCHAR(50); -- For storing DOB/NIC/Phone hash

-- =====================================================
-- CREATE STUDENT VERIFICATION LOG TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `student_verification_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT,
    `email` VARCHAR(100),
    `verification_type` VARCHAR(20) NOT NULL,
    `ip_address` VARCHAR(45),
    `success` TINYINT(1) DEFAULT 0,
    `attempts` INT DEFAULT 0,
    `locked_until` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE SET NULL
);

-- =====================================================
-- CREATE PASSWORD RESET REQUESTS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `password_reset_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(100) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- =====================================================
-- UPDATE SAMPLE STUDENT DATA WITH VERIFICATION FIELDS
-- =====================================================

UPDATE students SET date_of_birth = '2005-03-15', nic = '2005523V', phone = '0711234567', verification_field = '0315' WHERE student_id = 'ATIT-2024-0001';
UPDATE students SET date_of_birth = '2005-07-22', nic = '2005723V', phone = '0712345678', verification_field = '0722' WHERE student_id = 'ATIT-2024-0002';
UPDATE students SET date_of_birth = '2005-01-10', nic = '2005110V', phone = '0713456789', verification_field = '0110' WHERE student_id = 'ATIT-2024-0003';
UPDATE students SET date_of_birth = '2005-09-05', nic = '2005905V', phone = '0714567890', verification_field = '0905' WHERE student_id = 'ATIT-2024-0004';
UPDATE students SET date_of_birth = '2005-11-18', nic = '2005118V', phone = '0715678901', verification_field = '1118' WHERE student_id = 'ATIT-2024-0005';
UPDATE students SET date_of_birth = '2005-04-30', nic = '2005430V', phone = '0716789012', verification_field = '0430' WHERE student_id = 'ATIT-2024-0006';
UPDATE students SET date_of_birth = '2005-08-12', nic = '2005812V', phone = '0717890123', verification_field = '0812' WHERE student_id = 'ATIT-2024-0007';
UPDATE students SET date_of_birth = '2005-12-25', nic = '2005125V', phone = '0718901234', verification_field = '1225' WHERE student_id = 'ATIT-2024-0008';

-- =====================================================
-- UPDATE ADMIN PASSWORD (admin123)
-- =====================================================

UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';

-- =====================================================
-- UPDATE LECTURER PASSWORDS (lecturer123)
-- =====================================================

UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE role_id = 2;

-- =====================================================
-- ADD INDEXES
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_student_verification ON student_verification_logs(student_id);
CREATE INDEX IF NOT EXISTS idx_student_phone ON students(phone);
CREATE INDEX IF NOT EXISTS idx_student_nic ON students(nic);
CREATE INDEX IF NOT EXISTS idx_student_dob ON students(date_of_birth);