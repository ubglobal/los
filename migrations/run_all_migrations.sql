-- ============================================
-- Master Migration Script for LOS v3.0
-- ============================================
-- Description: Run all migrations to upgrade from v2.0 to v3.0
-- Author: Claude AI
-- Date: 2025-10-30
--
-- IMPORTANT:
-- 1. Backup your database before running this script!
-- 2. Run this on a TEST environment first
-- 3. Review all migrations before executing
-- 4. Estimated time: 2-5 minutes depending on data size
--
-- Usage:
--   mysql -u username -p database_name < run_all_migrations.sql
-- ============================================

USE vnbc_los;

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- ============================================
-- STEP 1: Create new tables
-- ============================================
SELECT 'Running migration 001: Create facilities table...' AS '';
SOURCE 001_create_facilities.sql;

SELECT 'Running migration 002: Create disbursements table...' AS '';
SOURCE 002_create_disbursements.sql;

SELECT 'Running migration 003: Create disbursement_conditions table...' AS '';
SOURCE 003_create_disbursement_conditions.sql;

SELECT 'Running migration 004: Create approval_conditions table...' AS '';
SOURCE 004_create_approval_conditions.sql;

SELECT 'Running migration 005: Create escalations table...' AS '';
SOURCE 005_create_escalations.sql;

SELECT 'Running migration 006: Create workflow_steps table...' AS '';
SOURCE 006_create_workflow_steps.sql;

SELECT 'Running migration 007: Create disbursement_history table...' AS '';
SOURCE 007_create_disbursement_history.sql;

SELECT 'Running migration 008: Create document_history table...' AS '';
SOURCE 008_create_document_history.sql;

SELECT 'Running migration 009: Create roles and permissions tables...' AS '';
SOURCE 009_create_roles_permissions.sql;

SELECT 'Running migration 013: Create login_attempts table...' AS '';
SOURCE 013_create_login_attempts.sql;

-- ============================================
-- STEP 2: Alter existing tables
-- ============================================
SELECT 'Running migration 010: Alter credit_applications table...' AS '';
SOURCE 010_alter_credit_applications.sql;

SELECT 'Running migration 011: Alter application_collaterals table...' AS '';
SOURCE 011_alter_application_collaterals.sql;

SELECT 'Running migration 012: Alter application_documents table...' AS '';
SOURCE 012_alter_application_documents.sql;

-- ============================================
-- STEP 3: Post-migration setup
-- ============================================
SELECT 'Post-migration setup...' AS '';

-- Create migration tracking table
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `version` VARCHAR(50) NOT NULL COMMENT 'Migration version (e.g., 3.0.0)',
    `migration_name` VARCHAR(255) NOT NULL,
    `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `execution_time_ms` INT(11) DEFAULT NULL,
    `status` ENUM('success', 'failed', 'rollback') NOT NULL DEFAULT 'success',
    PRIMARY KEY (`id`),
    KEY `idx_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Schema migration tracking';

-- Record all migrations
INSERT INTO `schema_migrations` (`version`, `migration_name`, `status`) VALUES
('3.0.0', '001_create_facilities', 'success'),
('3.0.0', '002_create_disbursements', 'success'),
('3.0.0', '003_create_disbursement_conditions', 'success'),
('3.0.0', '004_create_approval_conditions', 'success'),
('3.0.0', '005_create_escalations', 'success'),
('3.0.0', '006_create_workflow_steps', 'success'),
('3.0.0', '007_create_disbursement_history', 'success'),
('3.0.0', '008_create_document_history', 'success'),
('3.0.0', '009_create_roles_permissions', 'success'),
('3.0.0', '010_alter_credit_applications', 'success'),
('3.0.0', '011_alter_application_collaterals', 'success'),
('3.0.0', '012_alter_application_documents', 'success'),
('3.0.0', '013_create_login_attempts', 'success');

SET FOREIGN_KEY_CHECKS=1;
COMMIT;

-- ============================================
-- Migration Summary
-- ============================================
SELECT '==================================================' AS '';
SELECT 'Migration completed successfully!' AS '';
SELECT '==================================================' AS '';
SELECT '' AS '';
SELECT 'Summary:' AS '';
SELECT '- New tables created: 13' AS '';
SELECT '- Existing tables altered: 3' AS '';
SELECT '- Total migrations: 13' AS '';
SELECT '' AS '';
SELECT 'Next steps:' AS '';
SELECT '1. Verify data integrity' AS '';
SELECT '2. Test application functionality' AS '';
SELECT '3. Update application code to use new features' AS '';
SELECT '4. Run: SELECT * FROM schema_migrations;' AS '';
SELECT '==================================================' AS '';
