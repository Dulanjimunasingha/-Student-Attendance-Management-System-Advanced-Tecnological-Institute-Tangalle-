-- Run these SQL commands in phpMyAdmin to update the database

-- Add missing columns to users table
ALTER TABLE `users` ADD COLUMN `must_change_password` TINYINT(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `temp_password` VARCHAR(255);
ALTER TABLE `users` ADD COLUMN `failed_login_attempts` INT DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `lock_until` DATETIME;

-- Create student_otp_verifications table
CREATE TABLE IF NOT EXISTS `student_otp_verifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `otp_code` VARCHAR(10) NOT NULL, 
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `expires_at` DATETIME NOT NULL,
    `is_used` TINYINT(1) DEFAULT 0,
    `attempts` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
);

-- Add indexes
CREATE INDEX idx_student_otp_student ON student_otp_verifications(student_id);
CREATE INDEX idx_student_otp_code ON student_otp_verifications(otp_code);
CREATE INDEX idx_student_otp_expires ON student_otp_verifications(expires_at);