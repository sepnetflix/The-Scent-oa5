-- Create database if not exists
CREATE DATABASE IF NOT EXISTS the_scent;

-- Create user if not exists
CREATE USER IF NOT EXISTS 'scent_user'@'localhost' IDENTIFIED BY 'scent_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON the_scent.* TO 'scent_user'@'localhost';
FLUSH PRIVILEGES;

-- Switch to the database
USE the_scent;