-- Database Optimization Queries
-- Run these to improve query performance

-- Add composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_student_attendance ON attendance(student_id, date);
CREATE INDEX IF NOT EXISTS idx_classroom_subjects_full ON classroom_subjects(classroom_id, subject_id, teacher_id);
CREATE INDEX IF NOT EXISTS idx_grades_student_term ON grades(student_id, term, school_year);
CREATE INDEX IF NOT EXISTS idx_users_role_active ON users(role, is_active);
CREATE INDEX IF NOT EXISTS idx_students_section_year ON students(section, year_level);
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_date ON activity_logs(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_sms_logs_status_date ON sms_logs(status, sent_at);

-- Optimize existing tables
ANALYZE TABLE users, students, classrooms, subjects, grades, attendance;

-- Show index usage
SHOW INDEX FROM users;
SHOW INDEX FROM students;
SHOW INDEX FROM attendance;
SHOW INDEX FROM grades;
SHOW INDEX FROM classroom_subjects;
