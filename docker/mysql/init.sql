-- MySQL initialization script
-- This runs only on first container startup

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS accounting_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant privileges
GRANT ALL PRIVILEGES ON accounting_system.* TO 'accounting_user'@'%';
FLUSH PRIVILEGES;

-- Switch to accounting database
USE accounting_system;
