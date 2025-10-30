-- Migration 011: Alter application_collaterals table
-- Description: Thêm warehouse management và facility activation tracking
-- Author: Claude AI
-- Date: 2025-10-30

-- Add new columns for warehouse management
ALTER TABLE `application_collaterals`
ADD COLUMN `collateral_code` VARCHAR(50) DEFAULT NULL COMMENT 'Mã TSBĐ (unique)' AFTER `id`,
ADD COLUMN `warehouse_status` ENUM('Not Received', 'In Warehouse', 'Released', 'Disposed') NOT NULL DEFAULT 'Not Received' COMMENT 'Trạng thái nhập kho' AFTER `value`,
ADD COLUMN `warehouse_location` VARCHAR(255) DEFAULT NULL COMMENT 'Vị trí lưu trữ (kho/két)' AFTER `warehouse_status`,
ADD COLUMN `warehouse_date` DATE DEFAULT NULL COMMENT 'Ngày nhập kho' AFTER `warehouse_location`,
ADD COLUMN `warehouse_received_by_id` INT(11) DEFAULT NULL COMMENT 'User nhận TSBĐ vào kho' AFTER `warehouse_date`,
ADD COLUMN `release_date` DATE DEFAULT NULL COMMENT 'Ngày xuất kho/trả lại' AFTER `warehouse_received_by_id`,
ADD COLUMN `release_to` VARCHAR(255) DEFAULT NULL COMMENT 'Trả TSBĐ cho ai' AFTER `release_date`,

-- Facility activation (TSBĐ phải nhập kho và kích hoạt mới giải ngân được)
ADD COLUMN `facility_activated` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Đã kích hoạt hạn mức' AFTER `release_to`,
ADD COLUMN `activation_date` DATE DEFAULT NULL COMMENT 'Ngày kích hoạt' AFTER `facility_activated`,
ADD COLUMN `activated_by_id` INT(11) DEFAULT NULL COMMENT 'User kích hoạt' AFTER `activation_date`,

-- Legal status
ADD COLUMN `legal_status` ENUM('Pending', 'Registered', 'Expired', 'Cancelled') NOT NULL DEFAULT 'Pending' COMMENT 'Trạng thái pháp lý (đăng ký thế chấp)' AFTER `activated_by_id`,
ADD COLUMN `registration_number` VARCHAR(100) DEFAULT NULL COMMENT 'Số đăng ký thế chấp' AFTER `legal_status`,
ADD COLUMN `registration_date` DATE DEFAULT NULL COMMENT 'Ngày đăng ký thế chấp' AFTER `registration_number`,
ADD COLUMN `registration_authority` VARCHAR(255) DEFAULT NULL COMMENT 'Cơ quan đăng ký' AFTER `registration_date`,

-- Valuation
ADD COLUMN `valuation_date` DATE DEFAULT NULL COMMENT 'Ngày định giá' AFTER `registration_authority`,
ADD COLUMN `valuation_organization` VARCHAR(255) DEFAULT NULL COMMENT 'Tổ chức định giá' AFTER `valuation_date`,
ADD COLUMN `valuation_report_number` VARCHAR(100) DEFAULT NULL COMMENT 'Số báo cáo định giá' AFTER `valuation_organization`,
ADD COLUMN `valuation_notes` TEXT DEFAULT NULL COMMENT 'Ghi chú định giá' AFTER `valuation_report_number`,

-- Insurance
ADD COLUMN `insurance_required` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Yêu cầu bảo hiểm' AFTER `valuation_notes`,
ADD COLUMN `insurance_policy_number` VARCHAR(100) DEFAULT NULL COMMENT 'Số hợp đồng bảo hiểm' AFTER `insurance_required`,
ADD COLUMN `insurance_company` VARCHAR(255) DEFAULT NULL COMMENT 'Công ty bảo hiểm' AFTER `insurance_policy_number`,
ADD COLUMN `insurance_expiry_date` DATE DEFAULT NULL COMMENT 'Ngày hết hạn BH' AFTER `insurance_company`,

-- Additional notes
ADD COLUMN `notes` TEXT DEFAULT NULL COMMENT 'Ghi chú bổ sung' AFTER `insurance_expiry_date`;

-- Create indexes
CREATE INDEX `idx_warehouse_status` ON `application_collaterals` (`warehouse_status`);
CREATE INDEX `idx_facility_activated` ON `application_collaterals` (`facility_activated`);
CREATE INDEX `idx_legal_status` ON `application_collaterals` (`legal_status`);
CREATE INDEX `idx_warehouse_date` ON `application_collaterals` (`warehouse_date`);
CREATE INDEX `idx_collateral_code` ON `application_collaterals` (`collateral_code`);

-- Add foreign keys
ALTER TABLE `application_collaterals`
ADD CONSTRAINT `fk_collateral_warehouse_by` FOREIGN KEY (`warehouse_received_by_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `fk_collateral_activated_by` FOREIGN KEY (`activated_by_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Generate collateral codes for existing records
UPDATE `application_collaterals`
SET `collateral_code` = CONCAT('COL-', LPAD(id, 6, '0'));

-- Make collateral_code unique
ALTER TABLE `application_collaterals`
ADD UNIQUE KEY `unique_collateral_code` (`collateral_code`);

-- Update existing collaterals for HSTD 4 (đã phê duyệt và giải ngân)
-- TSBĐ đã nhập kho và kích hoạt
UPDATE `application_collaterals`
SET
    `warehouse_status` = 'In Warehouse',
    `warehouse_location` = 'Két K-01, Kho CN An Giang',
    `warehouse_date` = '2024-05-26',
    `warehouse_received_by_id` = 4,  -- Admin
    `facility_activated` = TRUE,
    `activation_date` = '2024-05-27',
    `activated_by_id` = 4,
    `legal_status` = 'Registered',
    `registration_number` = 'TC-2024-0001',
    `registration_date` = '2024-05-26',
    `registration_authority` = 'Phòng CSGT An Giang',
    `valuation_date` = '2024-05-15',
    `valuation_organization` = 'Công ty TNHH Thẩm định giá ABC',
    `valuation_report_number` = 'TDG-2024-0123',
    `insurance_required` = TRUE,
    `insurance_policy_number` = '2024-AUTO-001',
    `insurance_company` = 'Bảo hiểm VNI',
    `insurance_expiry_date` = '2025-05-26'
WHERE `id` = 3;  -- TSBĐ của HSTD 4

-- Update TSBĐ của HSTD 1 (đang xử lý)
UPDATE `application_collaterals`
SET
    `warehouse_status` = 'In Warehouse',
    `warehouse_location` = 'Két K-02, Kho Hội sở',
    `warehouse_date` = '2024-10-12',
    `warehouse_received_by_id` = 4,
    `facility_activated` = TRUE,
    `activation_date` = '2024-10-13',
    `activated_by_id` = 3,  -- CPD
    `legal_status` = 'Registered',
    `registration_number` = 'TC-2024-0002',
    `registration_date` = '2024-10-11',
    `registration_authority` = 'Phòng CSGT Hà Nội',
    `valuation_date` = '2024-10-08',
    `valuation_organization` = 'Công ty TNHH Thẩm định giá XYZ',
    `valuation_report_number` = 'TDG-2024-0456',
    `insurance_required` = TRUE,
    `insurance_policy_number` = '2024-AUTO-002',
    `insurance_company` = 'Bảo hiểm PVI',
    `insurance_expiry_date` = '2025-10-12'
WHERE `id` = 1;  -- TSBĐ của HSTD 1

-- Update TSBĐ của HSTD 3 (chờ phê duyệt)
UPDATE `application_collaterals`
SET
    `warehouse_status` = 'In Warehouse',
    `warehouse_location` = 'Kho Hội sở',
    `warehouse_date` = '2024-09-17',
    `warehouse_received_by_id` = 4,
    `facility_activated` = TRUE,
    `activation_date` = '2024-09-18',
    `activated_by_id` = 3,
    `legal_status` = 'Registered',
    `valuation_date` = '2024-09-10',
    `valuation_organization` = 'Trung tâm Thẩm định giá Quốc gia',
    `valuation_report_number` = 'TDG-2024-0789'
WHERE `id` = 2;  -- TSBĐ của HSTD 3
