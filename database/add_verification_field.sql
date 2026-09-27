-- Add missing columns to students table
USE atit_attendance;

ALTER TABLE students ADD COLUMN verification_field VARCHAR(50);
ALTER TABLE students ADD COLUMN date_of_birth DATE; 
ALTER TABLE students ADD COLUMN nic VARCHAR(20);
ALTER TABLE students ADD COLUMN phone VARCHAR(20);
ALTER TABLE students ADD COLUMN image_path VARCHAR(255);

-- Update existing students with verification field (last 4 of phone or default)
UPDATE students SET verification_field = '0315' WHERE student_id = 'ATIT-2024-0001';
UPDATE students SET verification_field = '0722' WHERE student_id = 'ATIT-2024-0002';

-- Show updated table structure
SHOW COLUMNS FROM students;