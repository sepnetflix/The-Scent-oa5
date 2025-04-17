-- modify_product_image_paths.sql
-- Update product image paths to use the convention: public/images/products/product_ID.jpg

UPDATE products SET image = CONCAT('/images/products/', id, '.jpg');
