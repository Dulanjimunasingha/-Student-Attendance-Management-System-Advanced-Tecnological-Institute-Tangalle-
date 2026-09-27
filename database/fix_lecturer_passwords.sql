-- Fix lecturer passwords to use 'lecturer123'
USE atit_attendance;

-- Update all lecturer accounts with correct bcrypt hash
UPDATE users SET password = '$2y$10$cTRSX1T60UFptWUrIld2kud2jbd8hcHuvnz/a8.sdQMyLSSX49ZaO' WHERE email = 'dr.silva@atit.edu.lk';
UPDATE users SET password = '$2y$10$cTRSX1T60UFptWUrIld2kud2jbd8hcHuvnz/a8.sdQMyLSSX49ZaO' WHERE email = 'ms.perera@atit.edu.lk';
UPDATE users SET password = '$2y$10$cTRSX1T60UFptWUrIld2kud2jbd8hcHuvnz/a8.sdQMyLSSX49ZaO' WHERE email = 'mr.jayawardena@atit.edu.lk';
UPDATE users SET password = '$2y$10$cTRSX1T60UFptWUrIld2kud2jbd8hcHuvnz/a8.sdQMyLSSX49ZaO' WHERE email = 'malidudeepsiri@gmail.com';

-- Verify
SELECT email, LEFT(password, 30) as pwd_prefix FROM users WHERE role_id = 2; 