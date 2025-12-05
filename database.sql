-- ============================================================================
-- SMART CLASSROOM SYSTEM - COMPLETE DATABASE SCHEMA
-- Updated: November 1, 2025
-- Version: 1.0.0 (Production Ready)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS smart_classroom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_classroom;

-- ============================================================================
-- USERS TABLE
-- Stores all system users (admin, teachers, advisors, students)
-- ============================================================================

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) DEFAULT NULL,
  role ENUM('admin', 'teacher', 'advisor', 'student') NOT NULL DEFAULT 'student',
  is_active TINYINT(1) DEFAULT 1,
  status ENUM('active', 'inactive') DEFAULT 'active',
  department VARCHAR(100) DEFAULT NULL,
  specialization VARCHAR(100) DEFAULT NULL,
  contact_number VARCHAR(20) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME NULL,
  login_attempts INT DEFAULT 0,
  locked_until DATETIME NULL,
  INDEX idx_username (username),
  INDEX idx_role (role),
  INDEX idx_status (status),
  INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- STUDENTS TABLE
-- Stores student information with QR codes and photos
-- ============================================================================

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(50) UNIQUE NOT NULL,
  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  middle_name VARCHAR(50) DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  section VARCHAR(50) NOT NULL,
  year_level INT NOT NULL,
  contact_number VARCHAR(20) DEFAULT NULL,
  parent_contact VARCHAR(20) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  photo VARCHAR(255) DEFAULT NULL,
  photo_path VARCHAR(255) DEFAULT NULL,
  qr_code VARCHAR(255) DEFAULT NULL,
  qr_code_path VARCHAR(255) DEFAULT NULL,
  user_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_student_id (student_id),
  INDEX idx_section (section),
  INDEX idx_year_level (year_level),
  INDEX idx_user_id (user_id),
  INDEX idx_section_year (section, year_level),
  INDEX idx_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBJECTS TABLE
-- Stores subject information
-- ============================================================================

CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_name VARCHAR(100) NOT NULL,
  subject_code VARCHAR(20) NOT NULL,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT DEFAULT NULL,
  classroom_id INT DEFAULT NULL,
  teacher_id INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_subject_code (subject_code),
  INDEX idx_code (code),
  INDEX idx_classroom (classroom_id),
  INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CLASSROOMS TABLE
-- Stores classroom information (for advisor system)
-- ============================================================================

CREATE TABLE IF NOT EXISTS classrooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  classroom_name VARCHAR(100) NOT NULL,
  section VARCHAR(50) NOT NULL,
  year_level INT NOT NULL,
  school_year VARCHAR(20) NOT NULL,
  advisor_id INT DEFAULT NULL,
  created_by INT NOT NULL,
  room_number VARCHAR(50) DEFAULT NULL,
  capacity INT DEFAULT 40,
  description TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (advisor_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_advisor (advisor_id),
  INDEX idx_created_by (created_by),
  INDEX idx_year_level (year_level),
  INDEX idx_section (section),
  INDEX idx_school_year (school_year),
  INDEX idx_year_section (year_level, section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CLASSROOM SUBJECTS TABLE
-- Links classrooms with subjects and teachers
-- ============================================================================

CREATE TABLE IF NOT EXISTS classroom_subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  classroom_id INT NOT NULL,
  subject_id INT NOT NULL,
  teacher_id INT DEFAULT NULL,
  capacity INT DEFAULT 40,
  school_year VARCHAR(20) NOT NULL,
  created_by INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_classroom (classroom_id),
  INDEX idx_subject (subject_id),
  INDEX idx_teacher (teacher_id),
  INDEX idx_created_by (created_by),
  INDEX idx_school_year (school_year),
  UNIQUE KEY unique_classroom_subject (classroom_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBJECT STUDENTS TABLE
-- Enrollment of students in classroom subjects
-- ============================================================================

CREATE TABLE IF NOT EXISTS subject_students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  classroom_subject_id INT NOT NULL,
  student_id VARCHAR(50) NOT NULL,
  enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (classroom_subject_id) REFERENCES classroom_subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
  INDEX idx_classroom_subject (classroom_subject_id),
  INDEX idx_student (student_id),
  UNIQUE KEY unique_enrollment (classroom_subject_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ATTENDANCE TABLE (Subject-based attendance)
-- Records attendance for specific subjects/classes
-- ============================================================================

CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(50) NOT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  time_in TIME DEFAULT NULL,
  status ENUM('Present', 'Absent', 'Late', 'Excused') DEFAULT 'Present',
  remarks TEXT DEFAULT NULL,
  recorded_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_student_id (student_id),
  INDEX idx_date (date),
  INDEX idx_status (status),
  INDEX idx_student_date (student_id, date),
  INDEX idx_date_status (date, status),
  UNIQUE KEY unique_attendance (student_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SCHOOL ATTENDANCE TABLE (Time In/Out - SMS notifications)
-- Separate from subject attendance for daily time in/out tracking
-- ============================================================================

CREATE TABLE IF NOT EXISTS school_attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(50) NOT NULL,
  date DATE NOT NULL,
  time_in TIME DEFAULT NULL,
  time_out TIME DEFAULT NULL,
  status ENUM('On Time', 'Late', 'Absent') DEFAULT 'On Time',
  remarks TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
  INDEX idx_student_id (student_id),
  INDEX idx_date (date),
  INDEX idx_status (status),
  UNIQUE KEY unique_student_date (student_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- GRADES TABLE
-- Stores student grades by subject and term
-- ============================================================================

CREATE TABLE IF NOT EXISTS grades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(50) NOT NULL,
  subject VARCHAR(100) NOT NULL,
  grade DECIMAL(5,2) NOT NULL,
  term VARCHAR(20) NOT NULL,
  school_year VARCHAR(20) NOT NULL,
  remarks TEXT DEFAULT NULL,
  teacher_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_student_id (student_id),
  INDEX idx_subject (subject),
  INDEX idx_term (term),
  INDEX idx_school_year (school_year),
  INDEX idx_teacher_id (teacher_id),
  INDEX idx_student_term (student_id, term, school_year),
  UNIQUE KEY unique_grade (student_id, subject, term, school_year),
  CHECK (grade >= 0 AND grade <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SMS LOGS TABLE
-- Tracks all SMS notifications sent to parents
-- ============================================================================

CREATE TABLE IF NOT EXISTS sms_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone_number VARCHAR(20) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending',
  response TEXT DEFAULT NULL,
  student_id VARCHAR(50) DEFAULT NULL,
  type ENUM('time_in', 'time_out', 'subject_attendance', 'general') DEFAULT 'general',
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE SET NULL,
  INDEX idx_phone_number (phone_number),
  INDEX idx_status (status),
  INDEX idx_type (type),
  INDEX idx_student_id (student_id),
  INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ACTIVITY LOGS TABLE
-- Tracks all user activities in the system
-- ============================================================================

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  table_name VARCHAR(100) DEFAULT NULL,
  record_id INT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_action (action),
  INDEX idx_table_name (table_name),
  INDEX idx_created_at (created_at),
  INDEX idx_user_date (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SYSTEM LOGS TABLE
-- Stores system errors and warnings
-- ============================================================================

CREATE TABLE IF NOT EXISTS system_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  level ENUM('INFO', 'WARNING', 'ERROR', 'CRITICAL') DEFAULT 'INFO',
  message TEXT NOT NULL,
  context TEXT DEFAULT NULL,
  file VARCHAR(255) DEFAULT NULL,
  line INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_level (level),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SECTIONS TABLE
-- Stores section information for better organization
-- ============================================================================

CREATE TABLE IF NOT EXISTS sections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section_name VARCHAR(50) UNIQUE NOT NULL,
  year_level INT NOT NULL,
  advisor_id INT DEFAULT NULL,
  school_year VARCHAR(20) NOT NULL,
  capacity INT DEFAULT 40,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (advisor_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_year_level (year_level),
  INDEX idx_advisor_id (advisor_id),
  INDEX idx_school_year (school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DEFAULT DATA
-- Insert default admin user
-- ============================================================================

-- Default Admin User (password: password123)
INSERT INTO users (username, password, name, email, role, is_active, status) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@smartclassroom.edu', 'admin', 1, 'active')
ON DUPLICATE KEY UPDATE username=username;

-- ============================================================================
-- VIEWS FOR EASIER DATA ACCESS
-- ============================================================================

-- View: Student Full Information
CREATE OR REPLACE VIEW view_students_full AS
SELECT 
  s.*,
  u.username,
  u.email AS user_email,
  u.is_active AS account_active
FROM students s
LEFT JOIN users u ON s.user_id = u.id;

-- View: Attendance Summary
CREATE OR REPLACE VIEW view_attendance_summary AS
SELECT 
  s.student_id,
  s.name AS student_name,
  s.section,
  s.year_level,
  COUNT(a.id) AS total_days,
  SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_days,
  SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) AS late_days,
  SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS absent_days,
  ROUND((SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0)) * 100, 2) AS attendance_rate
FROM students s
LEFT JOIN attendance a ON s.student_id = a.student_id
GROUP BY s.student_id, s.name, s.section, s.year_level;

-- View: Grade Summary
CREATE OR REPLACE VIEW view_grade_summary AS
SELECT 
  s.student_id,
  s.name AS student_name,
  s.section,
  s.year_level,
  g.term,
  g.school_year,
  COUNT(g.id) AS total_subjects,
  ROUND(AVG(g.grade), 2) AS average_grade,
  MIN(g.grade) AS lowest_grade,
  MAX(g.grade) AS highest_grade
FROM students s
LEFT JOIN grades g ON s.student_id = g.student_id
GROUP BY s.student_id, s.name, s.section, s.year_level, g.term, g.school_year;

-- ============================================================================
-- STORED PROCEDURES FOR COMMON OPERATIONS
-- ============================================================================

DELIMITER //

-- Procedure: Get Student Attendance Rate
CREATE PROCEDURE IF NOT EXISTS sp_get_attendance_rate(
  IN p_student_id VARCHAR(50),
  IN p_start_date DATE,
  IN p_end_date DATE
)
BEGIN
  SELECT 
    COUNT(*) AS total_days,
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_days,
    ROUND((SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) AS attendance_rate
  FROM attendance
  WHERE student_id = p_student_id
    AND date BETWEEN p_start_date AND p_end_date;
END //

-- Procedure: Get Student Average Grade
CREATE PROCEDURE IF NOT EXISTS sp_get_average_grade(
  IN p_student_id VARCHAR(50),
  IN p_term VARCHAR(20),
  IN p_school_year VARCHAR(20)
)
BEGIN
  SELECT 
    COUNT(*) AS total_subjects,
    ROUND(AVG(grade), 2) AS average_grade,
    MIN(grade) AS lowest_grade,
    MAX(grade) AS highest_grade
  FROM grades
  WHERE student_id = p_student_id
    AND term = p_term
    AND school_year = p_school_year;
END //

DELIMITER ;

-- ============================================================================
-- TRIGGERS FOR AUTOMATIC LOGGING
-- ============================================================================

DELIMITER //

-- Trigger: Log student creation
CREATE TRIGGER IF NOT EXISTS trg_student_created
AFTER INSERT ON students
FOR EACH ROW
BEGIN
  INSERT INTO system_logs (level, message, context)
  VALUES ('INFO', 'New student created', CONCAT('Student ID: ', NEW.student_id, ', Name: ', NEW.name));
END //

-- Trigger: Log grade entry
CREATE TRIGGER IF NOT EXISTS trg_grade_created
AFTER INSERT ON grades
FOR EACH ROW
BEGIN
  INSERT INTO system_logs (level, message, context)
  VALUES ('INFO', 'New grade entered', CONCAT('Student ID: ', NEW.student_id, ', Subject: ', NEW.subject, ', Grade: ', NEW.grade));
END //

DELIMITER ;

-- ============================================================================
-- DATABASE INFORMATION
-- ============================================================================

SELECT 'Smart Classroom Database Schema Created Successfully!' AS Status;
SELECT VERSION() AS 'MySQL Version';
SELECT DATABASE() AS 'Current Database';
SELECT COUNT(*) AS 'Total Tables' FROM information_schema.tables WHERE table_schema = 'smart_classroom';

-- ============================================================================
-- NOTES
-- ============================================================================
-- 1. All tables use InnoDB engine for transaction support and foreign keys
-- 2. Foreign keys ensure referential integrity across related tables
-- 3. Composite indexes optimize common query patterns
-- 4. Default admin credentials: admin / password123
-- 5. Character set: utf8mb4 (supports emojis and international characters)
-- 6. Collation: utf8mb4_unicode_ci (case-insensitive, Unicode-aware)
-- 7. Timestamps use CURRENT_TIMESTAMP for automatic tracking
-- 8. Views simplify complex queries for reporting
-- 9. Stored procedures encapsulate common business logic
-- 10. Triggers provide automatic audit logging
-- 11. CHECK constraints validate data integrity (grades 0-100)
-- 12. UNIQUE constraints prevent duplicate records
-- 13. ON DELETE CASCADE/SET NULL maintains referential integrity
-- ============================================================================
