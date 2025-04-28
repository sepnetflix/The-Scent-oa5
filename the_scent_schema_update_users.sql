-- SQL Script to Update the 'users' table for The Scent E-commerce Platform
-- Adds columns required by the updated User model and related controllers.

-- Select the target database (replace 'the_scent' if your database name is different)
USE `the_scent`;

-- Start transaction for safety (optional, depends on environment)
-- START TRANSACTION;

-- Add 'status' column
ALTER TABLE `users`
ADD COLUMN `status` ENUM('active', 'inactive', 'locked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active'
    COMMENT 'User account status (active, inactive, locked)'
    AFTER `role`;

-- Add 'newsletter_subscribed' column
ALTER TABLE `users`
ADD COLUMN `newsletter_subscribed` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Flag indicating newsletter subscription (0=No, 1=Yes)'
    AFTER `status`;

-- Add 'reset_token' column for password reset
ALTER TABLE `users`
ADD COLUMN `reset_token` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL
    COMMENT 'Secure token for password reset requests'
    AFTER `newsletter_subscribed`;

-- Add 'reset_token_expires_at' column
ALTER TABLE `users`
ADD COLUMN `reset_token_expires_at` DATETIME NULL DEFAULT NULL
    COMMENT 'Expiry timestamp for the password reset token'
    AFTER `reset_token`;

-- Add 'updated_at' column for tracking modifications
ALTER TABLE `users`
ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    COMMENT 'Timestamp of the last record update'
    AFTER `created_at`;

-- Add basic address fields directly to the users table
-- Note: For future scalability, consider a separate 'user_addresses' table.
ALTER TABLE `users`
ADD COLUMN `address_line1` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL
    COMMENT 'Primary address line'
    AFTER `reset_token_expires_at`;

ALTER TABLE `users`
ADD COLUMN `address_line2` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL
    COMMENT 'Secondary address line (optional)'
    AFTER `address_line1`;

ALTER TABLE `users`
ADD COLUMN `city` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL
    COMMENT 'City name'
    AFTER `address_line2`;

ALTER TABLE `users`
ADD COLUMN `state` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL
    COMMENT 'State / Province / Region'
    AFTER `city`;

ALTER TABLE `users`
ADD COLUMN `postal_code` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL
    COMMENT 'Postal or ZIP code'
    AFTER `state`;

ALTER TABLE `users`
ADD COLUMN `country` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL
    COMMENT 'Country name or code'
    AFTER `postal_code`;

-- Add an index to the 'reset_token' column for faster lookups
ALTER TABLE `users`
ADD INDEX `idx_reset_token` (`reset_token`);

-- Add an index to the 'status' column (optional but potentially useful)
ALTER TABLE `users`
ADD INDEX `idx_status` (`status`);

-- Commit the changes (if using transactions)
-- COMMIT;

-- Display confirmation message
SELECT 'SUCCESS: The users table has been updated with the required columns.' AS `Operation Status`;

