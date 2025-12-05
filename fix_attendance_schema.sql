-- ============================================================================
-- FIX: School Attendance Table Schema Issue
-- Problem: Foreign key mismatch between student_id types
-- Solution: Change school_attendance.student_id to INT to match students.id
-- ============================================================================

USE smart_classroom;

-- Drop the existing foreign key constraint
ALTER TABLE school_attendance 
DROP FOREIGN KEY IF EXISTS school_attendance_ibfk_1;

-- Drop the unique constraint
ALTER TABLE school_attendance 
DROP INDEX IF EXISTS unique_student_date;

-- Change student_id column from VARCHAR to INT
ALTER TABLE school_attendance 
MODIFY COLUMN student_id INT NOT NULL;

-- Re-add the foreign key constraint (now referencing students.id)
ALTER TABLE school_attendance 
ADD CONSTRAINT fk_school_attendance_student 
FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE;

-- Re-add the unique constraint
ALTER TABLE school_attendance 
ADD UNIQUE KEY unique_student_date (student_id, date);

-- Verify the change
DESCRIBE school_attendance;

SELECT 'School attendance table fixed successfully!' AS Status;
