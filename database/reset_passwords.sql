-- Update admin password to admin123
UPDATE users SET password = '$2y$10$uebd4RrU3IHrvih9VR1WCuQ..IxZy6u1l3I.8.YqYwd5Gab5QvMJ6' WHERE username = 'admin';

-- Update lecturer passwords to lecturer123
UPDATE users SET password = '$2y$10$uebd4RrU3IHrvih9VR1WCuQ..IxZy6u1l3I.8.YqYwd5Gab5QvMJ6' WHERE role_id = 2; 