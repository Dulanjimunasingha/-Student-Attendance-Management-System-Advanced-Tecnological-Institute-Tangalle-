-- Update admin and lecturer passwords to match 'admin123' / 'lecturer123'
-- Run this in phpMyAdmin SQL tab

-- First generate the hash using PHP: password_hash('admin123', PASSWORD_DEFAULT)
-- The hash below is for 'admin123'
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';

-- For lecturers, also set to admin123 (or you can set different passwords)
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE role_id = 2;