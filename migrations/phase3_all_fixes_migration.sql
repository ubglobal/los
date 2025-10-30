-- ============================================================================
-- PHASE 3 ALL FIXES - DATABASE MIGRATION SCRIPT
-- ============================================================================
-- Date: 2025-10-30
-- Purpose: Apply all bug fixes from Phase 3.1 to 3.7 to existing databases
-- Note: This is for EXISTING installations only. NEW installations should use database.sql
-- ============================================================================

-- Start transaction
START TRANSACTION;

-- ============================================================================
-- PHASE 3.6 FIXES (User Management)
-- ============================================================================

-- BUG-022: Add email column to users table (if not exists)
SET @exists := (SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'users'
                AND COLUMN_NAME = 'email');

SET @sqlstmt := IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN email varchar(100) NOT NULL AFTER username, ADD UNIQUE KEY email (email)',
    'SELECT "Column email already exists" AS message');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PHASE 3.7 FIXES (Workflow Engine & Exception Handling)
-- ============================================================================

-- BUG-025: Add allowed_actions column to workflow_steps table (if not exists)
SET @exists := (SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'workflow_steps'
                AND COLUMN_NAME = 'allowed_actions');

SET @sqlstmt := IF(@exists = 0,
    'ALTER TABLE workflow_steps ADD COLUMN allowed_actions text DEFAULT NULL COMMENT ''JSON array of allowed actions: ["Save","Next","Approve","Reject","Return"]'' AFTER next_step_on_reject',
    'SELECT "Column allowed_actions already exists" AS message');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- BUG-026: Add current_step_id column to credit_applications table (if not exists)
SET @exists := (SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'credit_applications'
                AND COLUMN_NAME = 'current_step_id');

SET @sqlstmt := IF(@exists = 0,
    'ALTER TABLE credit_applications ADD COLUMN current_step_id int(11) DEFAULT NULL COMMENT ''FIX BUG-026: Current workflow step'' AFTER stage',
    'SELECT "Column current_step_id already exists" AS message');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- BUG-026: Add previous_stage column to credit_applications table (if not exists)
SET @exists := (SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'credit_applications'
                AND COLUMN_NAME = 'previous_stage');

SET @sqlstmt := IF(@exists = 0,
    'ALTER TABLE credit_applications ADD COLUMN previous_stage varchar(100) DEFAULT NULL COMMENT ''FIX BUG-026: Previous workflow stage for tracking'' AFTER current_step_id',
    'SELECT "Column previous_stage already exists" AS message');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- BUG-026: Add index on current_step_id (if not exists)
SET @exists := (SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'credit_applications'
                AND INDEX_NAME = 'idx_current_step');

SET @sqlstmt := IF(@exists = 0,
    'ALTER TABLE credit_applications ADD KEY idx_current_step (current_step_id)',
    'SELECT "Index idx_current_step already exists" AS message');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- BUG-026: Add foreign key for current_step_id (if not exists)
SET @exists := (SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'credit_applications'
                AND CONSTRAINT_NAME = 'fk_application_current_step');

SET @sqlstmt := IF(@exists = 0,
    'ALTER TABLE credit_applications ADD CONSTRAINT fk_application_current_step FOREIGN KEY (current_step_id) REFERENCES workflow_steps (id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "Foreign key fk_application_current_step already exists" AS message');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Verify all columns exist
SELECT
    'users' AS table_name,
    'email' AS column_name,
    IF(COUNT(*) > 0, '✅ EXISTS', '❌ MISSING') AS status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'email'

UNION ALL

SELECT
    'workflow_steps' AS table_name,
    'allowed_actions' AS column_name,
    IF(COUNT(*) > 0, '✅ EXISTS', '❌ MISSING') AS status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'workflow_steps'
  AND COLUMN_NAME = 'allowed_actions'

UNION ALL

SELECT
    'credit_applications' AS table_name,
    'current_step_id' AS column_name,
    IF(COUNT(*) > 0, '✅ EXISTS', '❌ MISSING') AS status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'credit_applications'
  AND COLUMN_NAME = 'current_step_id'

UNION ALL

SELECT
    'credit_applications' AS table_name,
    'previous_stage' AS column_name,
    IF(COUNT(*) > 0, '✅ EXISTS', '❌ MISSING') AS status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'credit_applications'
  AND COLUMN_NAME = 'previous_stage';

-- Commit transaction
COMMIT;

-- ============================================================================
-- MIGRATION COMPLETE
-- ============================================================================
SELECT '✅ Phase 3 migration completed successfully!' AS result;
