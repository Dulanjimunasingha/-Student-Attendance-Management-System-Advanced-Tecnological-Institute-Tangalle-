-- Quick fix - Add verification_field column and update sample data
ALTER TABLE students ADD COLUMN verification_field VARCHAR(50);

-- Update sample students with verification codes (last 4 digits of DOB as YYMMDD format - using last 4)
UPDATE students SET verification_field = '0315' WHERE student_id = 'ATIT-2024-0001';
UPDATE students SET verification_field = '0722' WHERE student_id = 'ATIT-2024-0002';
UPDATE students SET verification_field = '0110' WHERE student_id = 'ATIT-2024-0003';
UPDATE students SET verification_field = '0905' WHERE student_id = 'ATIT-2024-0004';
UPDATE students SET verification_field = '1118' WHERE student_id = 'ATIT-2024-0005';
UPDATE students SET verification_field = '0430' WHERE student_id = 'ATIT-2024-0006';
UPDATE students SET verification_field = '0812' WHERE student_id = 'ATIT-2024-0007';
UPDATE students SET verification_field = '1225' WHERE student_id = 'ATIT-2024-0008'; 