-- ============================================
-- Rollback Script for LOS v3.0 → v2.0
-- ============================================
-- Description: Rollback all migrations to revert to v2.0
-- Author: Claude AI
-- Date: 2025-10-30
--
-- ⚠️ WARNING: This will DELETE all v3.0 features and data!
-- - All disbursements will be lost
-- - All facilities will be lost
-- - All approval conditions will be lost
-- - All escalations will be lost
-- - All new permissions will be lost
--
-- IMPORTANT:
-- 1. BACKUP your database before running this script!
-- 2. This is a DESTRUCTIVE operation
-- 3. Only run if you need to completely rollback to v2.0
--
-- Usage:
--   mysql -u username -p database_name < rollback_all_migrations.sql
-- ============================================

USE vnbc_los;

SET FOREIGN_KEY_CHECKS=0;
START TRANSACTION;

SELECT '==================================================' AS '';
SELECT 'WARNING: Rolling back to v2.0...' AS '';
SELECT 'This will DELETE all v3.0 data!' AS '';
SELECT '==================================================' AS '';

-- ============================================
-- STEP 1: Drop new tables (reverse order of creation)
-- ============================================

SELECT 'Dropping new tables...' AS '';

DROP TABLE IF EXISTS `user_branch_access`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `document_history`;
DROP TABLE IF EXISTS `disbursement_history`;
DROP TABLE IF EXISTS `disbursement_conditions`;
DROP TABLE IF EXISTS `escalations`;
DROP TABLE IF EXISTS `workflow_steps`;
DROP TABLE IF EXISTS `approval_conditions`;
DROP TABLE IF EXISTS `disbursements`;
DROP TABLE IF EXISTS `facilities`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `schema_migrations`;

-- ============================================
-- STEP 2: Revert changes to existing tables
-- ============================================

SELECT 'Reverting credit_applications table...' AS '';

-- Drop foreign keys first
ALTER TABLE `credit_applications` DROP FOREIGN KEY IF EXISTS `fk_app_current_step`;

-- Drop indexes
DROP INDEX IF EXISTS `idx_effective_date` ON `credit_applications`;
DROP INDEX IF EXISTS `idx_legal_completed` ON `credit_applications`;
DROP INDEX IF EXISTS `idx_sla_status` ON `credit_applications`;
DROP INDEX IF EXISTS `idx_current_step` ON `credit_applications`;
DROP INDEX IF EXISTS `idx_approved_date` ON `credit_applications`;

-- Drop columns
ALTER TABLE `credit_applications`
DROP COLUMN IF EXISTS `effective_date`,
DROP COLUMN IF EXISTS `legal_completed`,
DROP COLUMN IF EXISTS `legal_completed_date`,
DROP COLUMN IF EXISTS `legal_notes`,
DROP COLUMN IF EXISTS `sla_due_date`,
DROP COLUMN IF EXISTS `sla_status`,
DROP COLUMN IF EXISTS `workflow_type`,
DROP COLUMN IF EXISTS `current_step_id`,
DROP COLUMN IF EXISTS `previous_stage`,
DROP COLUMN IF EXISTS `submitted_date`,
DROP COLUMN IF EXISTS `approved_date`,
DROP COLUMN IF EXISTS `rejected_date`,
DROP COLUMN IF EXISTS `rejection_reason`;

-- ============================================

SELECT 'Reverting application_collaterals table...' AS '';

-- Drop foreign keys
ALTER TABLE `application_collaterals` DROP FOREIGN KEY IF EXISTS `fk_collateral_warehouse_by`;
ALTER TABLE `application_collaterals` DROP FOREIGN KEY IF EXISTS `fk_collateral_activated_by`;

-- Drop unique key
ALTER TABLE `application_collaterals` DROP KEY IF EXISTS `unique_collateral_code`;

-- Drop indexes
DROP INDEX IF EXISTS `idx_warehouse_status` ON `application_collaterals`;
DROP INDEX IF EXISTS `idx_facility_activated` ON `application_collaterals`;
DROP INDEX IF EXISTS `idx_legal_status` ON `application_collaterals`;
DROP INDEX IF EXISTS `idx_warehouse_date` ON `application_collaterals`;
DROP INDEX IF EXISTS `idx_collateral_code` ON `application_collaterals`;

-- Drop columns
ALTER TABLE `application_collaterals`
DROP COLUMN IF EXISTS `collateral_code`,
DROP COLUMN IF EXISTS `warehouse_status`,
DROP COLUMN IF EXISTS `warehouse_location`,
DROP COLUMN IF EXISTS `warehouse_date`,
DROP COLUMN IF EXISTS `warehouse_received_by_id`,
DROP COLUMN IF EXISTS `release_date`,
DROP COLUMN IF EXISTS `release_to`,
DROP COLUMN IF EXISTS `facility_activated`,
DROP COLUMN IF EXISTS `activation_date`,
DROP COLUMN IF EXISTS `activated_by_id`,
DROP COLUMN IF EXISTS `legal_status`,
DROP COLUMN IF EXISTS `registration_number`,
DROP COLUMN IF EXISTS `registration_date`,
DROP COLUMN IF EXISTS `registration_authority`,
DROP COLUMN IF EXISTS `valuation_date`,
DROP COLUMN IF EXISTS `valuation_organization`,
DROP COLUMN IF EXISTS `valuation_report_number`,
DROP COLUMN IF EXISTS `valuation_notes`,
DROP COLUMN IF EXISTS `insurance_required`,
DROP COLUMN IF EXISTS `insurance_policy_number`,
DROP COLUMN IF EXISTS `insurance_company`,
DROP COLUMN IF EXISTS `insurance_expiry_date`,
DROP COLUMN IF EXISTS `notes`;

-- ============================================

SELECT 'Reverting application_documents table...' AS '';

-- Drop foreign keys
ALTER TABLE `application_documents` DROP FOREIGN KEY IF EXISTS `fk_doc_verified_by`;
ALTER TABLE `application_documents` DROP FOREIGN KEY IF EXISTS `fk_doc_previous_version`;

-- Drop unique key
ALTER TABLE `application_documents` DROP KEY IF EXISTS `unique_document_code`;

-- Drop indexes
DROP INDEX IF EXISTS `idx_document_code` ON `application_documents`;
DROP INDEX IF EXISTS `idx_document_category` ON `application_documents`;
DROP INDEX IF EXISTS `idx_document_type` ON `application_documents`;
DROP INDEX IF EXISTS `idx_qr_token` ON `application_documents`;
DROP INDEX IF EXISTS `idx_file_hash` ON `application_documents`;
DROP INDEX IF EXISTS `idx_status` ON `application_documents`;
DROP INDEX IF EXISTS `idx_expiry_date` ON `application_documents`;
DROP INDEX IF EXISTS `idx_latest_version` ON `application_documents`;

-- Drop columns
ALTER TABLE `application_documents`
DROP COLUMN IF EXISTS `document_code`,
DROP COLUMN IF EXISTS `document_category`,
DROP COLUMN IF EXISTS `document_type`,
DROP COLUMN IF EXISTS `qr_token`,
DROP COLUMN IF EXISTS `qr_data`,
DROP COLUMN IF EXISTS `auto_classified`,
DROP COLUMN IF EXISTS `classification_confidence`,
DROP COLUMN IF EXISTS `file_size_bytes`,
DROP COLUMN IF EXISTS `mime_type`,
DROP COLUMN IF EXISTS `file_hash`,
DROP COLUMN IF EXISTS `version`,
DROP COLUMN IF EXISTS `is_latest_version`,
DROP COLUMN IF EXISTS `previous_version_id`,
DROP COLUMN IF EXISTS `status`,
DROP COLUMN IF EXISTS `verified_by_id`,
DROP COLUMN IF EXISTS `verified_date`,
DROP COLUMN IF EXISTS `verification_notes`,
DROP COLUMN IF EXISTS `has_expiry`,
DROP COLUMN IF EXISTS `expiry_date`,
DROP COLUMN IF EXISTS `expiry_alert_sent`,
DROP COLUMN IF EXISTS `description`,
DROP COLUMN IF EXISTS `tags`;

-- ============================================

SET FOREIGN_KEY_CHECKS=1;
COMMIT;

-- ============================================
-- Rollback Summary
-- ============================================
SELECT '==================================================' AS '';
SELECT 'Rollback completed!' AS '';
SELECT '==================================================' AS '';
SELECT '' AS '';
SELECT 'Summary:' AS '';
SELECT '- New tables dropped: 13' AS '';
SELECT '- Existing tables reverted: 3' AS '';
SELECT '- Database is now at v2.0 state' AS '';
SELECT '' AS '';
SELECT 'Next steps:' AS '';
SELECT '1. Verify v2.0 application still works' AS '';
SELECT '2. Check data integrity' AS '';
SELECT '3. Consider restoring from backup if needed' AS '';
SELECT '==================================================' AS '';
