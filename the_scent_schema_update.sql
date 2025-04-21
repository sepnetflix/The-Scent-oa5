-- SQL Schema Update Script for "The Scent" Project
-- Filename: the_scent_schema_update.sql
-- Version: 1.0
-- Date: 2024-05-16
-- Description: This script applies recommended schema changes to the 'the_scent' database
--              based on code review and analysis (Ref: technical_design_specification-v2.md).
--              It adds missing columns to the 'products' table required for the enhanced
--              product detail view and removes a redundant column.

-- Make sure to backup your database before applying this script!

USE `the_scent`;

-- -----------------------------------------------------
-- Step 1: Add Missing Columns to `products` Table
-- -----------------------------------------------------
-- These columns are needed for the enhanced product detail page layout and functionality.
-- Adding them as NULL initially to avoid issues with existing rows.
-- Set appropriate defaults or update existing rows as needed afterwards.

ALTER TABLE `products`
  ADD COLUMN `short_description` TEXT COLLATE utf8mb4_unicode_ci NULL COMMENT 'Brief description for listings/previews' AFTER `description`,
  ADD COLUMN `benefits` JSON NULL COMMENT 'Product benefits, stored as JSON array of strings' AFTER `price`, -- Consider TEXT if JSON type is not supported/preferred
  ADD COLUMN `ingredients` TEXT COLLATE utf8mb4_unicode_ci NULL COMMENT 'List of key ingredients' AFTER `benefits`,
  ADD COLUMN `usage_instructions` TEXT COLLATE utf8mb4_unicode_ci NULL COMMENT 'How to use the product' AFTER `ingredients`,
  ADD COLUMN `gallery_images` JSON NULL COMMENT 'JSON array of additional image paths' AFTER `image`, -- Consider TEXT if JSON type is not supported/preferred
  ADD COLUMN `size` VARCHAR(50) COLLATE utf8mb4_unicode_ci NULL COMMENT 'e.g., 10ml, 100g' AFTER `stock_quantity`,
  ADD COLUMN `scent_profile` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL COMMENT 'Simple text description of scent (Alternative: Use JOIN with product_attributes table)' AFTER `size`,
  ADD COLUMN `origin` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL COMMENT 'Country or region of origin' AFTER `scent_profile`,
  ADD COLUMN `sku` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL COMMENT 'Stock Keeping Unit' AFTER `origin`,
  ADD COLUMN `backorder_allowed` TINYINT(1) DEFAULT 0 NULL COMMENT '0 = No, 1 = Yes. Allow purchase when stock_quantity <= 0' AFTER `reorder_point`;

-- Optional: Add a unique constraint to SKU if it should be unique across all products
-- ALTER TABLE `products` ADD CONSTRAINT `sku_unique` UNIQUE (`sku`);

-- -----------------------------------------------------
-- Step 2: Remove Redundant `stock` Column from `products` Table
-- -----------------------------------------------------
-- The table has both `stock` and `stock_quantity`. Based on code review,
-- `stock_quantity` is the actively used column. Removing the old `stock` column.

ALTER TABLE `products`
  DROP COLUMN `stock`;

-- -----------------------------------------------------
-- End of Schema Updates
-- -----------------------------------------------------

-- Add a comment indicating completion
-- You might need to update your application code (Models, Controllers)
-- to utilize these new columns and remove references to the dropped 'stock' column.
-- Remember to populate the new columns with data for existing products.

SELECT 'Schema update script completed successfully.' AS `Status`;
