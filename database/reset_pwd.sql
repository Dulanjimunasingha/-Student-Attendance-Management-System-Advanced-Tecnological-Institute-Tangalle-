-- Reset Admin Password
USE atit_attendance;

UPDATE users SET password = '$2y$10$dR.JjIwkhXyecRDry8mxFeY9/EdlDRDsnzTL87Yle.ZwQCq4TEm.O' WHERE username = 'admin';

-- Also update lecturer passwords
UPDATE users SET password = '$2y$10$dR.JjIwkhXyecRDry8mxFeY9/EdlDRDsnzTL87Yle.ZwQCq4TEm.O' WHERE role_id = 2;

SELECT username, email, LEFT(password, 30) as pwd_prefix FROM users; 