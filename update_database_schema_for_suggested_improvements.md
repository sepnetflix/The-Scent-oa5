```sql
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

```

**Explanation and How to Use:**

1.  **Backup:** **ALWAYS backup your database** before running any schema modification script.
2.  **Database Name:** The script includes `USE the_scent;`. Ensure this matches your actual database name.
3.  **Run the Script:** Execute this SQL script against your MySQL database using a tool like MySQL Workbench, phpMyAdmin, or the command-line client:
    ```bash
    mysql -u your_mysql_user -p your_database_name < the_scent_schema_update.sql
    ```
    (Replace `your_mysql_user` and `your_database_name` accordingly).
4.  **New Columns:** It adds the columns identified as missing for the enhanced product detail view (`short_description`, `benefits`, `ingredients`, etc.) to the `products` table.
    *   `JSON` type is used for `benefits` and `gallery_images`. If your MySQL version doesn't support JSON well or you prefer plain text, you can change the type to `TEXT` and handle JSON encoding/decoding entirely in your PHP code. Comments are included in the script for this.
    *   Columns are added as `NULL` initially to prevent errors if you already have data in the `products` table. You will need to populate these fields for existing products afterward.
    *   `backorder_allowed` is added with a `DEFAULT 0` (false).
5.  **Redundant Column Removal:** It removes the `stock` column, as `stock_quantity` appears to be the intended and used column based on the reviewed code.
6.  **Optional SKU Constraint:** A commented-out line shows how to add a `UNIQUE` constraint to the `sku` column if needed. Uncomment it if SKUs must be unique.
7.  **Application Code:** After running this script, you will need to:
    *   Update the `Product` model (`models/Product.php`) and any queries in `ProductController` to select and potentially handle (e.g., `json_decode`) these new fields.
    *   Ensure the enhanced `views/product_detail.php` template correctly references these new field names (e.g., `$product['benefits']`, `$product['ingredients']`).
    *   Remove any remaining references to the old `stock` column in your PHP code.
8.  **Data Population:** Update your existing product records to populate the newly added columns (`short_description`, `benefits`, `ingredients`, `sku`, etc.) with appropriate data.

https://drive.google.com/file/d/10gCnL8NJp79PUjHWxDtMcW4-Nj5In661/view?usp=sharing, https://drive.google.com/file/d/126KjzuTW6OQd1YXyc5oKi7XUArOEP96m/view?usp=sharing, https://drive.google.com/file/d/1BM2Pr-Q-dRs2lQtzFYIABmcqcFVllSsN/view?usp=sharing, https://drive.google.com/file/d/1Bp0-5HMlGKICNb4U_YbJ_mFD35T2YfOf/view?usp=sharing, https://drive.google.com/file/d/1FXsDOP7FCoP1cUYxDI4hEC4AXRGjQwAC/view?usp=sharing, https://drive.google.com/file/d/1GDqixZr8XpKYZgWn7p7_BUJVTGbp7c8p/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221Tsva1prccYU-Un90emc34sB2sHhMLXja%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1XyxEK8Yb9GQZ0Ahk1P_Wf-FkfF965Omj/view?usp=sharing, https://drive.google.com/file/d/1bDNZgMUeBQNrCoO8Sr-w5Z0N0dCFDJjU/view?usp=sharing, https://drive.google.com/file/d/1eUiM9-m0SALwdiqcRWmeYkDz-17JUIoj/view?usp=sharing, https://drive.google.com/file/d/1tcI9kfjgyvoAe8xjYs0xfOxpCYYFYp0H/view?usp=sharing
