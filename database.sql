-- ============================================================================
-- LOS v3.0 - Complete Database Schema for Fresh Installation
-- ============================================================================
-- Description: Complete database structure for Loan Origination System v3.0
-- Author: Claude AI
-- Date: 2025-10-30
-- Version: 3.0.0
-- Compatible: MySQL 5.7+ / MariaDB 10.2+
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. CORE TABLES (From v2.0)
-- ============================================================================

-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('Admin','CVQHKH','CVTĐ','CPD','GDK','Kiểm soát','Thủ quỹ') NOT NULL,
  `branch` varchar(100) DEFAULT 'Hội sở',
  `approval_limit` decimal(15,2) DEFAULT NULL COMMENT 'Hạn mức phê duyệt (VND) - Dành cho CPD/GDK',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_branch` (`branch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: customers
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) NOT NULL,
  `customer_type` enum('CÁ NHÂN','DOANH NGHIỆP') NOT NULL DEFAULT 'CÁ NHÂN',
  `full_name` varchar(100) NOT NULL,
  `id_number` varchar(20) DEFAULT NULL COMMENT 'CCCD/CMND for individuals',
  `dob` date DEFAULT NULL COMMENT 'Date of birth for individuals',
  `company_tax_code` varchar(50) DEFAULT NULL COMMENT 'Tax code for companies',
  `company_representative` varchar(100) DEFAULT NULL COMMENT 'Representative for companies',
  `address` text DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `branch` varchar(100) DEFAULT 'Hội sở',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  KEY `idx_id_number` (`id_number`),
  KEY `idx_company_tax_code` (`company_tax_code`),
  KEY `idx_branch` (`branch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: products
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_type` enum('Vốn lưu động ngắn hạn','Đầu tư dài hạn','Tài trợ thương mại','Thấu chi') NOT NULL,
  `max_amount` decimal(20,2) DEFAULT NULL,
  `max_term_months` int(11) DEFAULT NULL,
  `interest_rate_min` decimal(5,2) DEFAULT NULL,
  `interest_rate_max` decimal(5,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: collateral_types
DROP TABLE IF EXISTS `collateral_types`;
CREATE TABLE `collateral_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: document_definitions
DROP TABLE IF EXISTS `document_definitions`;
CREATE TABLE `document_definitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_name` varchar(100) NOT NULL,
  `doc_type` enum('Identity','Financial','Legal','Collateral','Business','Other') NOT NULL DEFAULT 'Other',
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: credit_applications
DROP TABLE IF EXISTS `credit_applications`;
CREATE TABLE `credit_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hstd_code` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `amount` decimal(20,2) NOT NULL,
  `term_months` int(11) NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('Bản nháp','Đang xử lý','Đã phê duyệt','Từ chối','Yêu cầu bổ sung','Đã hủy') NOT NULL DEFAULT 'Bản nháp',
  `stage` varchar(100) DEFAULT 'Khởi tạo',
  `created_by_id` int(11) NOT NULL,
  `assigned_to_id` int(11) DEFAULT NULL,
  `reviewed_by_id` int(11) DEFAULT NULL,
  `approved_by_id` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sla_target_date` datetime DEFAULT NULL,
  `sla_status` enum('On Track','Warning','Overdue') DEFAULT 'On Track',
  `legal_completed` tinyint(1) NOT NULL DEFAULT 0,
  `legal_completed_date` date DEFAULT NULL,
  `legal_completed_by_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hstd_code` (`hstd_code`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_status` (`status`),
  KEY `idx_stage` (`stage`),
  KEY `idx_assigned_to` (`assigned_to_id`),
  KEY `idx_created_by` (`created_by_id`),
  CONSTRAINT `fk_application_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_application_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_application_created_by` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_application_assigned_to` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: application_collaterals
DROP TABLE IF EXISTS `application_collaterals`;
CREATE TABLE `application_collaterals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `collateral_type_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `estimated_value` decimal(20,2) DEFAULT NULL,
  `appraised_value` decimal(20,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `warehouse_in` tinyint(1) NOT NULL DEFAULT 0,
  `warehouse_in_date` date DEFAULT NULL,
  `warehouse_in_by_id` int(11) DEFAULT NULL,
  `activated` tinyint(1) NOT NULL DEFAULT 0,
  `activated_date` date DEFAULT NULL,
  `activated_by_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_application` (`application_id`),
  KEY `idx_collateral_type` (`collateral_type_id`),
  CONSTRAINT `fk_appcol_application` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_appcol_collateral_type` FOREIGN KEY (`collateral_type_id`) REFERENCES `collateral_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: application_documents
DROP TABLE IF EXISTS `application_documents`;
CREATE TABLE `application_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `document_definition_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by_id` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `version` int(11) NOT NULL DEFAULT 1,
  `is_latest` tinyint(1) NOT NULL DEFAULT 1,
  `qr_code` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_application` (`application_id`),
  KEY `idx_document_definition` (`document_definition_id`),
  KEY `idx_uploaded_by` (`uploaded_by_id`),
  CONSTRAINT `fk_appdoc_application` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_appdoc_document_definition` FOREIGN KEY (`document_definition_id`) REFERENCES `document_definitions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_appdoc_uploaded_by` FOREIGN KEY (`uploaded_by_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. v3.0 NEW TABLES - Business Logic
-- ============================================================================

-- Table: facilities (Hạn mức tín dụng)
DROP TABLE IF EXISTS `facilities`;
CREATE TABLE `facilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `facility_code` varchar(50) NOT NULL,
  `facility_type` varchar(100) NOT NULL,
  `product_id` int(11) NOT NULL,
  `amount` decimal(20,2) NOT NULL,
  `disbursed_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `available_amount` decimal(20,2) GENERATED ALWAYS AS (`amount` - `disbursed_amount`) STORED,
  `currency` varchar(3) NOT NULL DEFAULT 'VND',
  `status` enum('Pending','Active','Inactive','Closed','Expired') NOT NULL DEFAULT 'Pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `collateral_required` tinyint(1) NOT NULL DEFAULT 0,
  `collateral_activated` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_id` int(11) NOT NULL,
  `approved_by_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_facility_code` (`facility_code`),
  KEY `idx_application` (`application_id`),
  KEY `idx_status` (`status`),
  KEY `idx_facility_type` (`facility_type`),
  KEY `idx_facility_dates` (`start_date`,`end_date`),
  CONSTRAINT `fk_facility_application` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_facility_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_facility_created_by` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_facility_approved_by` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: disbursements (Giải ngân)
DROP TABLE IF EXISTS `disbursements`;
CREATE TABLE `disbursements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disbursement_code` varchar(50) NOT NULL,
  `application_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `disbursement_type` enum('Lần đầu','Rút vốn','Giải ngân theo tiến độ') NOT NULL DEFAULT 'Lần đầu',
  `amount` decimal(20,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'VND',
  `purpose` text NOT NULL,
  `beneficiary_type` enum('Chính chủ','Bên thứ 3') NOT NULL DEFAULT 'Chính chủ',
  `beneficiary_name` varchar(255) NOT NULL,
  `beneficiary_account` varchar(50) DEFAULT NULL,
  `beneficiary_bank` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Awaiting Conditions Check','Awaiting Approval','Approved','Executed','Rejected','Cancelled') NOT NULL DEFAULT 'Draft',
  `stage` varchar(100) NOT NULL DEFAULT 'Khởi tạo',
  `assigned_to_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) NOT NULL,
  `checked_by_id` int(11) DEFAULT NULL,
  `approved_by_id` int(11) DEFAULT NULL,
  `executed_by_id` int(11) DEFAULT NULL,
  `requested_date` date NOT NULL,
  `approved_date` date DEFAULT NULL,
  `disbursed_date` date DEFAULT NULL,
  `expected_disbursement_date` date DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `sla_due_date` datetime DEFAULT NULL,
  `sla_status` enum('On Track','Warning','Overdue') DEFAULT 'On Track',
  `notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_disbursement_code` (`disbursement_code`),
  KEY `idx_application` (`application_id`),
  KEY `idx_facility` (`facility_id`),
  KEY `idx_status` (`status`),
  KEY `idx_stage` (`stage`),
  CONSTRAINT `fk_disb_application` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_disb_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_disb_created_by` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: disbursement_conditions
DROP TABLE IF EXISTS `disbursement_conditions`;
CREATE TABLE `disbursement_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disbursement_id` int(11) NOT NULL,
  `condition_text` text NOT NULL,
  `condition_type` enum('Pre-disbursement','Post-disbursement','Legal','Financial','Other') NOT NULL DEFAULT 'Pre-disbursement',
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `is_met` tinyint(1) NOT NULL DEFAULT 0,
  `met_date` date DEFAULT NULL,
  `met_by_id` int(11) DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_disbursement` (`disbursement_id`),
  KEY `idx_is_met` (`is_met`),
  CONSTRAINT `fk_disbcond_disbursement` FOREIGN KEY (`disbursement_id`) REFERENCES `disbursements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_disbcond_met_by` FOREIGN KEY (`met_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: approval_conditions
DROP TABLE IF EXISTS `approval_conditions`;
CREATE TABLE `approval_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `condition_code` varchar(50) DEFAULT NULL,
  `condition_text` text NOT NULL,
  `condition_category` enum('Credit Rating','Income','Collateral','Legal','Policy','Other') NOT NULL DEFAULT 'Other',
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `allow_exception` tinyint(1) NOT NULL DEFAULT 0,
  `is_exception_requested` tinyint(1) NOT NULL DEFAULT 0,
  `exception_reason` text DEFAULT NULL,
  `exception_requested_by_id` int(11) DEFAULT NULL,
  `exception_requested_date` date DEFAULT NULL,
  `exception_approved` tinyint(1) NOT NULL DEFAULT 0,
  `exception_approved_by_id` int(11) DEFAULT NULL,
  `exception_approved_date` date DEFAULT NULL,
  `exception_rejection_reason` text DEFAULT NULL,
  `is_met` tinyint(1) NOT NULL DEFAULT 0,
  `met_date` date DEFAULT NULL,
  `met_by_id` int(11) DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_application` (`application_id`),
  KEY `idx_is_met` (`is_met`),
  CONSTRAINT `fk_appr_cond_application` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: escalations
DROP TABLE IF EXISTS `escalations`;
CREATE TABLE `escalations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) DEFAULT NULL,
  `disbursement_id` int(11) DEFAULT NULL,
  `escalation_type` enum('Credit','Disbursement') NOT NULL,
  `reason` text NOT NULL,
  `supporting_documents` text DEFAULT NULL,
  `urgency_level` enum('Normal','High','Critical') NOT NULL DEFAULT 'Normal',
  `escalated_by_id` int(11) NOT NULL,
  `escalated_to_id` int(11) NOT NULL,
  `original_rejector_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Under Review','Resolved - Approved','Resolved - Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `resolution` text DEFAULT NULL,
  `resolved_by_id` int(11) DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `escalated_date` datetime NOT NULL DEFAULT current_timestamp(),
  `expected_response_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_application` (`application_id`),
  KEY `idx_disbursement` (`disbursement_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_esc_application` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_esc_disbursement` FOREIGN KEY (`disbursement_id`) REFERENCES `disbursements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_esc_escalated_by` FOREIGN KEY (`escalated_by_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_esc_escalated_to` FOREIGN KEY (`escalated_to_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: workflow_steps
DROP TABLE IF EXISTS `workflow_steps`;
CREATE TABLE `workflow_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workflow_type` enum('Credit','Disbursement') NOT NULL,
  `step_code` varchar(50) NOT NULL,
  `step_name` varchar(100) NOT NULL,
  `step_order` int(11) NOT NULL,
  `assigned_role` varchar(50) DEFAULT NULL,
  `next_step_on_approve` varchar(50) DEFAULT NULL,
  `next_step_on_reject` varchar(50) DEFAULT NULL,
  `sla_hours` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_workflow_step` (`workflow_type`,`step_code`),
  KEY `idx_workflow_type` (`workflow_type`),
  KEY `idx_step_order` (`step_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: disbursement_history
DROP TABLE IF EXISTS `disbursement_history`;
CREATE TABLE `disbursement_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disbursement_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_status` varchar(100) DEFAULT NULL,
  `new_status` varchar(100) DEFAULT NULL,
  `performed_by_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_disbursement` (`disbursement_id`),
  KEY `idx_performed_by` (`performed_by_id`),
  CONSTRAINT `fk_disbhist_disbursement` FOREIGN KEY (`disbursement_id`) REFERENCES `disbursements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_disbhist_performed_by` FOREIGN KEY (`performed_by_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: document_history
DROP TABLE IF EXISTS `document_history`;
CREATE TABLE `document_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `action` enum('Upload','Replace','Delete','Update Metadata') NOT NULL,
  `old_file_path` varchar(500) DEFAULT NULL,
  `new_file_path` varchar(500) DEFAULT NULL,
  `old_version` int(11) DEFAULT NULL,
  `new_version` int(11) DEFAULT NULL,
  `performed_by_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_document` (`document_id`),
  KEY `idx_performed_by` (`performed_by_id`),
  CONSTRAINT `fk_dochist_document` FOREIGN KEY (`document_id`) REFERENCES `application_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dochist_performed_by` FOREIGN KEY (`performed_by_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: roles_permissions
DROP TABLE IF EXISTS `roles_permissions`;
CREATE TABLE `roles_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_description` text DEFAULT NULL,
  `is_granted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_name`,`permission_name`),
  KEY `idx_role` (`role_name`),
  KEY `idx_permission` (`permission_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: user_branch_access
DROP TABLE IF EXISTS `user_branch_access`;
CREATE TABLE `user_branch_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `branch` varchar(100) NOT NULL,
  `can_access_customers` tinyint(1) NOT NULL DEFAULT 0,
  `can_access_collaterals` tinyint(1) NOT NULL DEFAULT 0,
  `can_access_facilities` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_branch` (`user_id`,`branch`),
  KEY `idx_user` (`user_id`),
  KEY `idx_branch` (`branch`),
  CONSTRAINT `fk_user_branch_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: login_attempts (Rate limiting)
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_attempt_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. INITIAL DATA - Minimal Sample Data for Testing
-- ============================================================================

-- Insert sample products
INSERT INTO `products` (`product_code`, `product_name`, `product_type`, `max_amount`, `max_term_months`, `interest_rate_min`, `interest_rate_max`, `is_active`) VALUES
('VLD-001', 'Vốn lưu động ngắn hạn', 'Vốn lưu động ngắn hạn', 5000000000.00, 12, 7.00, 10.00, 1),
('DDH-001', 'Đầu tư dài hạn', 'Đầu tư dài hạn', 10000000000.00, 60, 8.00, 11.00, 1),
('TTTM-001', 'Tài trợ thương mại', 'Tài trợ thương mại', 3000000000.00, 6, 6.50, 9.50, 1);

-- Insert sample collateral types
INSERT INTO `collateral_types` (`type_name`, `description`, `is_active`) VALUES
('Bất động sản', 'Đất, nhà, căn hộ', 1),
('Ô tô', 'Xe ô tô các loại', 1),
('Máy móc thiết bị', 'Máy móc, thiết bị sản xuất', 1),
('Hàng hóa', 'Hàng tồn kho, nguyên vật liệu', 1);

-- Insert sample document definitions
INSERT INTO `document_definitions` (`doc_name`, `doc_type`, `is_required`, `description`) VALUES
('CMND/CCCD', 'Identity', 1, 'Chứng minh nhân dân hoặc Căn cước công dân'),
('Báo cáo tài chính', 'Financial', 1, 'Báo cáo tài chính 2 năm gần nhất'),
('Giấy phép kinh doanh', 'Legal', 1, 'Giấy phép đăng ký kinh doanh'),
('Hợp đồng mua bán', 'Legal', 0, 'Hợp đồng mua bán liên quan đến khoản vay');

-- Insert workflow steps for Credit workflow
INSERT INTO `workflow_steps` (`workflow_type`, `step_code`, `step_name`, `step_order`, `assigned_role`, `next_step_on_approve`, `next_step_on_reject`, `sla_hours`, `is_active`) VALUES
('Credit', 'INIT', 'Khởi tạo', 1, 'CVQHKH', 'REVIEW', 'REJECTED', 24, 1),
('Credit', 'REVIEW', 'Thẩm định', 2, 'CVTĐ', 'APPROVE', 'NEED_INFO', 48, 1),
('Credit', 'APPROVE', 'Phê duyệt', 3, 'CPD', 'APPROVED', 'REJECTED', 24, 1),
('Credit', 'APPROVE_HIGH', 'Phê duyệt cấp cao', 4, 'GDK', 'APPROVED', 'REJECTED', 48, 1),
('Credit', 'APPROVED', 'Đã phê duyệt', 5, NULL, NULL, NULL, NULL, 1),
('Credit', 'REJECTED', 'Từ chối', 6, NULL, NULL, NULL, NULL, 1),
('Credit', 'NEED_INFO', 'Yêu cầu bổ sung', 7, 'CVQHKH', 'REVIEW', 'REJECTED', 72, 1);

-- Insert workflow steps for Disbursement workflow
INSERT INTO `workflow_steps` (`workflow_type`, `step_code`, `step_name`, `step_order`, `assigned_role`, `next_step_on_approve`, `next_step_on_reject`, `sla_hours`, `is_active`) VALUES
('Disbursement', 'INIT', 'Khởi tạo', 1, 'CVQHKH', 'CHECK_CONDITIONS', 'CANCELLED', 24, 1),
('Disbursement', 'CHECK_CONDITIONS', 'Kiểm tra điều kiện', 2, 'Kiểm soát', 'APPROVE_DISB', 'NEED_INFO', 24, 1),
('Disbursement', 'APPROVE_DISB', 'Phê duyệt giải ngân', 3, 'CPD', 'EXECUTE', 'REJECTED', 24, 1),
('Disbursement', 'EXECUTE', 'Thực hiện giải ngân', 4, 'Thủ quỹ', 'COMPLETED', NULL, 4, 1),
('Disbursement', 'COMPLETED', 'Đã giải ngân', 5, NULL, NULL, NULL, NULL, 1),
('Disbursement', 'REJECTED', 'Từ chối', 6, NULL, NULL, NULL, NULL, 1);

-- Insert permissions for all roles
INSERT INTO `roles_permissions` (`role_name`, `permission_name`, `permission_description`, `is_granted`) VALUES
-- Admin permissions (full access)
('Admin', 'create_application', 'Tạo hồ sơ tín dụng mới', 1),
('Admin', 'edit_application', 'Chỉnh sửa hồ sơ tín dụng', 1),
('Admin', 'delete_application', 'Xóa hồ sơ tín dụng', 1),
('Admin', 'view_all_applications', 'Xem tất cả hồ sơ', 1),
('Admin', 'approve_application', 'Phê duyệt hồ sơ', 1),
('Admin', 'create_disbursement', 'Tạo yêu cầu giải ngân', 1),
('Admin', 'approve_disbursement', 'Phê duyệt giải ngân', 1),
('Admin', 'execute_disbursement', 'Thực hiện giải ngân', 1),
('Admin', 'manage_users', 'Quản lý người dùng', 1),
('Admin', 'manage_customers', 'Quản lý khách hàng', 1),
('Admin', 'manage_products', 'Quản lý sản phẩm', 1),
('Admin', 'view_reports', 'Xem báo cáo', 1),
('Admin', 'request_exception', 'Yêu cầu ngoại lệ', 1),
('Admin', 'approve_exception', 'Phê duyệt ngoại lệ', 1),
('Admin', 'create_escalation', 'Tạo khiếu nại/leo thang', 1),

-- CVQHKH permissions (Relationship Manager)
('CVQHKH', 'create_application', 'Tạo hồ sơ tín dụng mới', 1),
('CVQHKH', 'edit_application', 'Chỉnh sửa hồ sơ của mình', 1),
('CVQHKH', 'view_all_applications', 'Xem hồ sơ được giao', 0),
('CVQHKH', 'create_disbursement', 'Tạo yêu cầu giải ngân', 1),
('CVQHKH', 'manage_customers', 'Quản lý khách hàng', 1),
('CVQHKH', 'request_exception', 'Yêu cầu ngoại lệ', 1),
('CVQHKH', 'create_escalation', 'Tạo khiếu nại/leo thang', 1),

-- CVTĐ permissions (Credit Analyst)
('CVTĐ', 'edit_application', 'Chỉnh sửa hồ sơ được giao', 1),
('CVTĐ', 'view_all_applications', 'Xem hồ sơ được giao', 0),
('CVTĐ', 'approve_application', 'Thẩm định hồ sơ (không phải phê duyệt cuối)', 1),

-- CPD permissions (Credit Officer)
('CPD', 'view_all_applications', 'Xem hồ sơ chờ phê duyệt', 0),
('CPD', 'approve_application', 'Phê duyệt hồ sơ', 1),
('CPD', 'approve_disbursement', 'Phê duyệt giải ngân', 1),
('CPD', 'approve_exception', 'Phê duyệt ngoại lệ', 1),
('CPD', 'view_reports', 'Xem báo cáo', 1),

-- GDK permissions (General Director Credit)
('GDK', 'view_all_applications', 'Xem tất cả hồ sơ', 1),
('GDK', 'approve_application', 'Phê duyệt cấp cao', 1),
('GDK', 'approve_disbursement', 'Phê duyệt giải ngân', 1),
('GDK', 'approve_exception', 'Phê duyệt ngoại lệ', 1),
('GDK', 'view_reports', 'Xem báo cáo', 1),

-- Kiểm soát permissions (Credit Controller)
('Kiểm soát', 'view_all_applications', 'Xem hồ sơ cần kiểm tra', 0),
('Kiểm soát', 'approve_disbursement', 'Kiểm tra điều kiện giải ngân', 1),

-- Thủ quỹ permissions (Cashier)
('Thủ quỹ', 'view_all_applications', 'Xem yêu cầu giải ngân', 0),
('Thủ quỹ', 'execute_disbursement', 'Thực hiện giải ngân', 1);

-- ============================================================================
-- 4. FINALIZE
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Installation Complete!
-- ============================================================================
-- Next steps:
-- 1. Create .env file with database credentials
-- 2. Create admin user via installer or manually
-- 3. Login and configure:
--    - Users (CVQHKH, CVTĐ, CPD, GDK, Kiểm soát, Thủ quỹ)
--    - Customers
--    - More products if needed
--    - Document definitions
-- ============================================================================
