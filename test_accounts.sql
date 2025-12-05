-- ========================================
-- TEST ACCOUNTS FOR ALL ROLES
-- ========================================
-- All accounts use password: password123
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

USE smart_classroom;

-- ========================================
-- ADMIN ACCOUNT (Already exists in schema.sql)
-- ========================================
-- Username: admin
-- Password: password123
-- Role: admin

-- ========================================
-- ADVISOR ACCOUNTS
-- ========================================
INSERT INTO users (username, name, email, password, role, status) VALUES
('advisor1', 'John Advisor', 'advisor1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'advisor', 'active'),
('advisor2', 'Mary Advisor', 'advisor2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'advisor', 'active');

-- ========================================
-- TEACHER ACCOUNTS
-- ========================================
INSERT INTO users (username, name, email, password, role, status) VALUES
('teacher1', 'Jane Teacher', 'teacher1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active'),
('teacher2', 'Bob Teacher', 'teacher2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active'),
('teacher3', 'Alice Teacher', 'teacher3@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active');

-- ========================================
-- STUDENT ACCOUNTS
-- ========================================
INSERT INTO users (username, name, email, password, role, status) VALUES
('student1', 'Carlos Student', 'student1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
('student2', 'Maria Student', 'student2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
('student3', 'Pedro Student', 'student3@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
('student4', 'Ana Student', 'student4@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
('student5', 'Jose Student', 'student5@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active');

-- ========================================
-- CLASSROOMS
-- ========================================
INSERT INTO classrooms (name, advisor_id) VALUES
('Grade 10-A', (SELECT id FROM users WHERE username = 'advisor1')),
('Grade 10-B', (SELECT id FROM users WHERE username = 'advisor2'));

-- ========================================
-- SUBJECTS
-- ========================================
INSERT INTO subjects (name, classroom_id, teacher_id) VALUES
('Mathematics', 1, (SELECT id FROM users WHERE username = 'teacher1')),
('English', 1, (SELECT id FROM users WHERE username = 'teacher2')),
('Science', 1, (SELECT id FROM users WHERE username = 'teacher3')),
('Mathematics', 2, (SELECT id FROM users WHERE username = 'teacher1')),
('English', 2, (SELECT id FROM users WHERE username = 'teacher2'));

-- ========================================
-- STUDENT RECORDS
-- ========================================
INSERT INTO students (user_id, classroom_id) VALUES
((SELECT id FROM users WHERE username = 'student1'), 1),
((SELECT id FROM users WHERE username = 'student2'), 1),
((SELECT id FROM users WHERE username = 'student3'), 1),
((SELECT id FROM users WHERE username = 'student4'), 2),
((SELECT id FROM users WHERE username = 'student5'), 2);

-- ========================================
-- SAMPLE ATTENDANCE DATA
-- ========================================
INSERT INTO attendance (student_id, date, time_in, status) VALUES
(1, CURDATE(), '07:45:00', 'present'),
(2, CURDATE(), '07:50:00', 'present'),
(3, CURDATE(), '08:15:00', 'late'),
(4, CURDATE(), '07:55:00', 'present'),
(5, CURDATE(), '07:40:00', 'present'),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '07:48:00', 'present'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '07:52:00', 'present'),
(3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:20:00', 'late'),
(4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '07:50:00', 'present'),
(5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '07:47:00', 'present');

-- ========================================
-- SAMPLE GRADES DATA
-- ========================================
INSERT INTO grades (student_id, subject_id, quarter, grade, remarks) VALUES
(1, 1, 'Q1', 88.5, 'Good performance'),
(1, 2, 'Q1', 90.0, 'Excellent'),
(1, 3, 'Q1', 85.5, 'Satisfactory'),
(2, 1, 'Q1', 92.0, 'Outstanding'),
(2, 2, 'Q1', 89.5, 'Very good'),
(2, 3, 'Q1', 91.0, 'Excellent'),
(3, 1, 'Q1', 78.0, 'Needs improvement'),
(3, 2, 'Q1', 82.5, 'Satisfactory'),
(3, 3, 'Q1', 80.0, 'Fair'),
(4, 4, 'Q1', 95.0, 'Outstanding'),
(4, 5, 'Q1', 93.5, 'Excellent'),
(5, 4, 'Q1', 87.0, 'Good'),
(5, 5, 'Q1', 88.5, 'Good performance');

-- ========================================
-- ACCOUNT SUMMARY
-- ========================================
-- Admin:    admin / password123
-- Advisors: advisor1, advisor2 / password123
-- Teachers: teacher1, teacher2, teacher3 / password123
-- Students: student1-5 / password123
