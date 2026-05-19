-- Add position field to users table
-- This allows storing the user's position/title for signatories

ALTER TABLE users ADD COLUMN position VARCHAR(150) DEFAULT NULL COMMENT 'User position/title for signatories';

UPDATE users SET position = 'Administrative Aide III' WHERE role = 'Admin';
UPDATE users SET position = 'System Administrator' WHERE role = 'Super Admin';
UPDATE users SET position = 'IT Officer' WHERE role = 'IT';

SELECT 'User position field added successfully' AS status;
