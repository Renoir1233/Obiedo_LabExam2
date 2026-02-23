-- ============================================
-- DATABASE MIGRATION SCRIPT
-- Safely updates existing database to new schema
-- ============================================
-- This script checks if columns/tables exist before creating them
-- Run this if you have existing data you want to preserve
-- ============================================

USE infosec_lab;

-- ============================================
-- 1. UPDATE USERS TABLE
-- ============================================

-- Add email column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'email';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN email VARCHAR(100) NOT NULL UNIQUE AFTER password',
    'SELECT "email column already exists" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add role column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'role';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN role ENUM("admin", "user") NOT NULL DEFAULT "user" AFTER email',
    'SELECT "role column already exists" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modify password column to VARCHAR(255) if it's too short
SET @col_type = '';
SELECT DATA_TYPE INTO @col_type 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'password';

SET @col_length = 0;
SELECT CHARACTER_MAXIMUM_LENGTH INTO @col_length 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'password';

SET @sql = IF(@col_length < 255, 
    'ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL',
    'SELECT "password column already correct length" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add created_at column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'created_at';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT "created_at column already exists" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add updated_at column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'updated_at';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "updated_at column already exists" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update admin password to hashed version if it's still plaintext
UPDATE users 
SET password = '$2y$10$Ohpxkwg1Rx2eoR7oMqHus.q/ppCAxsOyvO5TLgfdGEkSD2pHRK.5.',
    email = 'admin@example.com',
    role = 'admin'
WHERE username = 'admin' AND password = 'admin123';

-- ============================================
-- 2. CREATE COURSES TABLE (if not exists)
-- ============================================
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `course_code` VARCHAR(50) NOT NULL UNIQUE,
    `course_description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample courses if table is empty
INSERT IGNORE INTO `courses` (`course_code`, `course_description`) VALUES
('BSIT', 'Bachelor of Science in Information Technology'),
('BSCS', 'Bachelor of Science in Computer Science'),
('BSIS', 'Bachelor of Science in Information Systems'),
('BSCE', 'Bachelor of Science in Computer Engineering'),
('BSEE', 'Bachelor of Science in Electrical Engineering');

-- ============================================
-- 3. UPDATE STUDENTS TABLE
-- ============================================

-- Add course_id column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'students' 
AND COLUMN_NAME = 'course_id';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE students ADD COLUMN course_id INT(11) DEFAULT NULL AFTER email',
    'SELECT "course_id column already exists" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add created_by column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'students' 
AND COLUMN_NAME = 'created_by';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE students ADD COLUMN created_by INT(11) DEFAULT NULL AFTER course_id',
    'SELECT "created_by column already exists" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add created_at column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'students' 
AND COLUMN_NAME = 'created_at';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE students ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT "created_at column already exists" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add updated_at column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'students' 
AND COLUMN_NAME = 'updated_at';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE students ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "updated_at column already exists" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Migrate existing course data to courses table and update students with course_id
-- This is a safe migration that handles existing data

-- First, check if old course columns exist
SET @course_col_exists = 0;
SELECT COUNT(*) INTO @course_col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'students' 
AND COLUMN_NAME = 'course';

-- If old course column exists, migrate data
SET @migration_needed = @course_col_exists;

-- Only proceed with migration if needed
-- Insert unique courses from students table into courses table
INSERT IGNORE INTO courses (course_code, course_description)
SELECT DISTINCT course, course_description 
FROM students 
WHERE course IS NOT NULL AND course != ''
AND @migration_needed = 1;

-- Update students with course_id based on course_code match
UPDATE students s
INNER JOIN courses c ON s.course = c.course_code
SET s.course_id = c.id
WHERE s.course IS NOT NULL AND s.course_id IS NULL
AND @migration_needed = 1;

-- ============================================
-- 4. ADD FOREIGN KEYS (if not exists)
-- ============================================

-- Add foreign key for course_id if it doesn't exist
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists 
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'students' 
AND CONSTRAINT_NAME = 'students_ibfk_1';

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE students ADD CONSTRAINT students_ibfk_1 FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL',
    'SELECT "FK course_id already exists" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for created_by if it doesn't exist
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists 
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'infosec_lab' 
AND TABLE_NAME = 'students' 
AND CONSTRAINT_NAME = 'students_ibfk_2';

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE students ADD CONSTRAINT students_ibfk_2 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "FK created_by already exists" AS msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 5. CREATE LOGIN_ATTEMPTS TABLE (if not exists)
-- ============================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_username` (`username`),
    INDEX `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- MIGRATION COMPLETE
-- ============================================
SELECT 'Database migration completed successfully!' AS Status;

-- Show updated table structures
SHOW TABLES;
