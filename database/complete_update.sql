-- =====================================================
-- Complete Database Update Script
-- Advanced Technological Institute of Tangalle
-- =====================================================

USE atit_attendance;

-- ===================================================== 
-- 1. UPDATE USERS TABLE - Add security columns
-- =====================================================
ALTER TABLE `users` ADD COLUMN `must_change_password` TINYINT(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `failed_login_attempts` INT DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `lock_until` DATETIME;

-- =====================================================
-- 2. UPDATE STUDENTS TABLE - Add all required fields
-- =====================================================
ALTER TABLE `students` ADD COLUMN `date_of_birth` DATE;
ALTER TABLE `students` ADD COLUMN `nic` VARCHAR(20);
ALTER TABLE `students` ADD COLUMN `phone` VARCHAR(20);
ALTER TABLE `students` ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `students` ADD COLUMN `verification_field` VARCHAR(50);
ALTER TABLE `students` MODIFY `email` VARCHAR(100) NULL; -- Make email optional

-- =====================================================
-- 3. CREATE LECTURER PASSWORD RESET TABLE
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
-- 4. CREATE HOME PAGE SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default home page settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES 
('hero_image', 'https://images.unsplash.com/photo-1563206767-5b1d97299374?w=1200&h=400&fit=crop'),
('hero_title', 'Advanced Technological Institute of Tangalle'),
('hero_subtitle', 'Attendance Management System');

-- =====================================================
-- 5. UPDATE STUDENT VERIFICATION FIELDS
-- =====================================================
UPDATE students SET verification_field = '0315' WHERE student_id = 'ATIT-2024-0001';
UPDATE students SET verification_field = '0722' WHERE student_id = 'ATIT-2024-0002';
UPDATE students SET verification_field = '0110' WHERE student_id = 'ATIT-2024-0003';
UPDATE students SET verification_field = '0905' WHERE student_id = 'ATIT-2024-0004';
UPDATE students SET verification_field = '1118' WHERE student_id = 'ATIT-2024-0005';
UPDATE students SET verification_field = '0430' WHERE student_id = 'ATIT-2024-0006';
UPDATE students SET verification_field = '0812' WHERE student_id = 'ATIT-2024-0007';
UPDATE students SET verification_field = '1225' WHERE student_id = 'ATIT-2024-0008';

-- =====================================================
-- 6. UPDATE ADMIN PASSWORD (admin123)
-- =====================================================
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';

-- =====================================================
-- 7. UPDATE LECTURER PASSWORDS (lecturer123)
-- =====================================================
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE role_id = 2;

-- =====================================================
-- 8. ADD INDEXES
-- =====================================================
CREATE INDEX IF NOT EXISTS idx_student_phone ON students(phone);
CREATE INDEX IF NOT EXISTS idx_student_nic ON students(nic);
CREATE INDEX IF NOT EXISTS idx_student_verification ON students(verification_field);
CREATE INDEX IF NOT EXISTS idx_users_password ON users(password);