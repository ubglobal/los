-- =====================================================
-- FIX BUG-006: Application Code Generation
-- Purpose: Add sequence table for unique code generation
-- =====================================================

USE los_db;

-- Create sequence table for application codes
CREATE TABLE IF NOT EXISTS application_code_sequence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sequence generator for unique application codes';

-- Optional: Add unique constraint to hstd_code in credit_applications
-- (This ensures database-level uniqueness)
ALTER TABLE credit_applications
ADD UNIQUE KEY uk_hstd_code (hstd_code);

-- =====================================================
-- VERIFICATION
-- =====================================================
SELECT 'Application code sequence table created successfully!' as Status;

-- Show table structure
DESCRIBE application_code_sequence;
