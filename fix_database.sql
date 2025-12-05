-- Fix Database Issues
-- Run this file to fix missing columns and table issues
-- Execute in phpMyAdmin or MySQL command line

USE smart_classroom;

-- Fix 1: Add missing columns to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS specialization VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS contact_number VARCHAR(20) DEFAULT NULL;

-- Fix 2: Add photo column to students table if it doesn't exist
ALTER TABLE students ADD COLUMN IF NOT EXISTS photo VARCHAR(255) DEFAULT NULL AFTER contact_number;

-- Fix 3: Ensure students table has all required columns
ALTER TABLE students 
  MODIFY COLUMN student_id VARCHAR(50) UNIQUE NOT NULL,
  MODIFY COLUMN name VARCHAR(100) NOT NULL,
  MODIFY COLUMN section VARCHAR(50),
  MODIFY COLUMN year_level VARCHAR(20),
  MODIFY COLUMN contact_number VARCHAR(20),
  MODIFY COLUMN photo VARCHAR(255) DEFAULT NULL,
  MODIFY COLUMN qr_code VARCHAR(255);

-- Fix 4: Create or fix classrooms table
CREATE TABLE IF NOT EXISTS classrooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  classroom_name VARCHAR(100) NOT NULL,
  year_level VARCHAR(20) NOT NULL,
  section VARCHAR(50) NOT NULL,
  advisor_id INT DEFAULT NULL,
  room_number VARCHAR(50) DEFAULT NULL,
  capacity INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_advisor (advisor_id),
  INDEX idx_year_section (year_level, section)
);

-- Add missing columns to classrooms if table already exists
ALTER TABLE classrooms ADD COLUMN IF NOT EXISTS classroom_name VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE classrooms ADD COLUMN IF NOT EXISTS year_level VARCHAR(20) NOT NULL DEFAULT '';
ALTER TABLE classrooms ADD COLUMN IF NOT EXISTS section VARCHAR(50) NOT NULL DEFAULT '';
ALTER TABLE classrooms ADD COLUMN IF NOT EXISTS advisor_id INT DEFAULT NULL;
ALTER TABLE classrooms ADD COLUMN IF NOT EXISTS room_number VARCHAR(50) DEFAULT NULL;
ALTER TABLE classrooms ADD COLUMN IF NOT EXISTS capacity INT DEFAULT NULL;

-- Fix 5: Create or fix subjects table
CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  classroom_id INT DEFAULT NULL,
  teacher_id INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_classroom (classroom_id),
  INDEX idx_teacher (teacher_id),
  INDEX idx_code (code)
);

-- Add missing columns to subjects if table already exists
ALTER TABLE subjects ADD COLUMN IF NOT EXISTS code VARCHAR(20) NOT NULL DEFAULT '';
ALTER TABLE subjects ADD COLUMN IF NOT EXISTS name VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE subjects ADD COLUMN IF NOT EXISTS classroom_id INT DEFAULT NULL;
ALTER TABLE subjects ADD COLUMN IF NOT EXISTS teacher_id INT DEFAULT NULL;

-- Fix 6: Add indexes for performance
ALTER TABLE school_attendance ADD INDEX IF NOT EXISTS idx_student_date (student_id, date);
ALTER TABLE attendance ADD INDEX IF NOT EXISTS idx_student_date (student_id, date);
ALTER TABLE grades ADD INDEX IF NOT EXISTS idx_student_subject (student_id, subject);

-- Verify tables
SELECT 'Users table structure:' as '';
DESCRIBE users;

SELECT 'Students table structure:' as '';
DESCRIBE students;

SELECT 'Classrooms table structure:' as '';
DESCRIBE classrooms;

SELECT 'Subjects table structure:' as '';
DESCRIBE subjects;

SELECT 'Database fixed successfully!' as 'Status';
