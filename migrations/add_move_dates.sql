-- =============================================================================
-- Migration: Add move_in_date and move_out_date to households table
-- Run this SQL if you have an existing Beverly database and want to add 
-- the new move in/out date functionality.
-- 
-- Features:
-- - Date of move-in: Records when a household member moved in
-- - Date of move-out: Records when they moved out (NULL if "Up to present")
-- - "Up to present" option: When selected, move_out_date is set to NULL,
--   indicating the resident is still living at the property
-- =============================================================================

ALTER TABLE households ADD COLUMN move_in_date DATE DEFAULT NULL AFTER num_pets;
ALTER TABLE households ADD COLUMN move_out_date DATE DEFAULT NULL AFTER move_in_date;

-- Verify the columns were added
SHOW COLUMNS FROM households LIKE 'move_%';
