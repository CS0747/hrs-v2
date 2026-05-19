-- Remove duplicate schedules
-- Keep only the most recent entry for each employee + date combination

-- Create temporary table with IDs to keep (most recent for each employee+date)
CREATE TEMPORARY TABLE schedules_to_keep AS
SELECT MAX(id) as id
FROM schedules
GROUP BY employee_no, schedule_date;

-- Delete all schedules except the ones we want to keep
DELETE FROM schedules
WHERE id NOT IN (SELECT id FROM schedules_to_keep);

-- Drop temporary table
DROP TEMPORARY TABLE schedules_to_keep;

-- Add unique constraint to prevent future duplicates
ALTER TABLE schedules
ADD UNIQUE KEY unique_employee_date (employee_no, schedule_date);
