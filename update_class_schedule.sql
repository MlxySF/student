-- Add schedule columns to classes table
ALTER TABLE `classes` 
ADD COLUMN `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NULL AFTER `description`,
ADD COLUMN `start_time` TIME NULL AFTER `day_of_week`,
ADD COLUMN `end_time` TIME NULL AFTER `start_time`;

-- Update existing classes with default schedule (optional - you can skip this if you want to set schedules manually)
-- UPDATE `classes` SET `day_of_week` = 'Sunday', `start_time` = '10:00:00', `end_time` = '12:00:00' WHERE `class_code` = 'WSA-101';

-- Example: Set schedule for specific classes
-- UPDATE `classes` SET `day_of_week` = 'Monday', `start_time` = '18:00:00', `end_time` = '20:00:00' WHERE `class_code` = 'WSA-201';
-- UPDATE `classes` SET `day_of_week` = 'Saturday', `start_time` = '14:00:00', `end_time` = '16:00:00' WHERE `class_code` = 'WSA-301';