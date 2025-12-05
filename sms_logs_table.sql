-- ============================================================================
-- SMS LOGS TABLE
-- Stores all SMS notifications sent to parents
-- ============================================================================

CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    response TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add parent_contact column to students table if it doesn't exist
ALTER TABLE students 
ADD COLUMN IF NOT EXISTS parent_contact VARCHAR(20) DEFAULT NULL AFTER contact_number;

-- Update existing parent_contact from parent_phone if exists
UPDATE students 
SET parent_contact = parent_phone 
WHERE parent_contact IS NULL AND parent_phone IS NOT NULL;
