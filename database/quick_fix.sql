-- Quick fix - Add only the essential columns to students table
ALTER TABLE students ADD COLUMN date_of_birth DATE;
ALTER TABLE students ADD COLUMN verification_field VARCHAR(50); 