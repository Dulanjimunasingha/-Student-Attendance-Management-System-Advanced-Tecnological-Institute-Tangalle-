-- Fix authentication issues - passwords and student verification
USE atit_attendance;

-- =====================================================
-- FIX 1: Update admin password with correct bcrypt hash
-- Password: admin123
-- =====================================================
UPDATE users SET password = '$2y$10$AYcxCCi.Z4DL6mzO0VEOSeVt6lrcGsSpWdH3qprwW7ERqd6fJptyu' 
WHERE email = 'admin@atit.edu.lk';

-- ===================================================== 
-- FIX 2: Update lecturer passwords with correct bcrypt hash
-- Password: lecturer123
-- =====================================================
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'dr.silva@atit.edu.lk';

UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'ms.perera@atit.edu.lk';

UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'mr.jayawardena@atit.edu.lk';

-- =====================================================
-- FIX 3: Add verification fields to students table
-- =====================================================
ALTER TABLE students ADD COLUMN IF NOT EXISTS date_of_birth DATE;
ALTER TABLE students ADD COLUMN IF NOT EXISTS nic VARCHAR(20);
ALTER TABLE students ADD COLUMN IF NOT EXISTS verification_field VARCHAR(50);

-- =====================================================
-- FIX 4: Update student verification data
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
-- VERIFY: Check users table
-- =====================================================
SELECT id, username, email, LEFT(password, 30) as password_prefix, role_id, status FROM users;

-- =====================================================
-- VERIFY: Check students table
-- =====================================================
SELECT id, student_id, full_name, verification_field, date_of_birth, nic, phone FROM students;
