-- ATIT Attendance System - Complete Fix Script
-- Run this in phpMyAdmin SQL tab

USE atit_attendance;
 
-- =====================================================
-- FIX 1: Add verification_field column to students
-- =====================================================
ALTER TABLE students ADD COLUMN verification_field VARCHAR(50);
ALTER TABLE students ADD COLUMN date_of_birth DATE;
ALTER TABLE students ADD COLUMN nic VARCHAR(20);
ALTER TABLE students ADD COLUMN address TEXT;

-- =====================================================
-- FIX 2: Fix admin and lecturer passwords
-- =====================================================
-- Admin: admin@atit.edu.lk / admin123
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';

-- Lecturers: use admin123 as default (hash is same as above)
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE role_id = 2;

-- =====================================================
-- FIX 3: Update sample student verification data
-- =====================================================
UPDATE students SET verification_field = '0315', date_of_birth = '2005-03-15', nic = '2005523V', phone = '0711234567' WHERE student_id = 'ATIT-2024-0001';
UPDATE students SET verification_field = '0722', date_of_birth = '2005-07-22', nic = '2005723V', phone = '0712345678' WHERE student_id = 'ATIT-2024-0002';
UPDATE students SET verification_field = '0110', date_of_birth = '2005-01-10', nic = '2005110V', phone = '0713456789' WHERE student_id = 'ATIT-2024-0003';
UPDATE students SET verification_field = '0905', date_of_birth = '2005-09-05', nic = '2005905V', phone = '0714567890' WHERE student_id = 'ATIT-2024-0004';
UPDATE students SET verification_field = '1118', date_of_birth = '2005-11-18', nic = '2005118V', phone = '0715678901' WHERE student_id = 'ATIT-2024-0005';
UPDATE students SET verification_field = '0430', date_of_birth = '2005-04-30', nic = '2005430V', phone = '0716789012' WHERE student_id = 'ATIT-2024-0006';
UPDATE students SET verification_field = '0812', date_of_birth = '2005-08-12', nic = '2005812V', phone = '0717890123' WHERE student_id = 'ATIT-2024-0007';
UPDATE students SET verification_field = '1225', date_of_birth = '2005-12-25', nic = '2005125V', phone = '0718901234' WHERE student_id = 'ATIT-2024-0008';

-- =====================================================
-- Verify fixes
-- =====================================================
SELECT 'Users after password fix:' as info;
SELECT id, username, email, LEFT(password, 30) as pwd_prefix, role_id, status FROM users;

SELECT 'Students after verification fix:' as info;
SELECT id, student_id, full_name, verification_field, date_of_birth, phone FROM students LIMIT 10;
