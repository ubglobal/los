-- Migration 012: Alter application_documents table
-- Description: Thêm QR code support, document classification, versioning
-- Author: Claude AI
-- Date: 2025-10-30

-- Add new columns for document management
ALTER TABLE `application_documents`
ADD COLUMN `document_code` VARCHAR(50) DEFAULT NULL COMMENT 'Mã tài liệu chuẩn hóa (unique)' AFTER `id`,
ADD COLUMN `document_category` VARCHAR(100) DEFAULT NULL COMMENT 'Phân loại: CMND, Hợp đồng, Sao kê, Giấy tờ xe, ...' AFTER `document_name`,
ADD COLUMN `document_type` ENUM('Required', 'Optional', 'Supporting') NOT NULL DEFAULT 'Required' COMMENT 'Loại: Bắt buộc/Tùy chọn/Hỗ trợ' AFTER `document_category`,

-- QR Code support (optional feature)
ADD COLUMN `qr_token` VARCHAR(255) DEFAULT NULL COMMENT 'Token từ QR code' AFTER `file_path`,
ADD COLUMN `qr_data` TEXT DEFAULT NULL COMMENT 'Dữ liệu JSON từ QR code' AFTER `qr_token`,
ADD COLUMN `auto_classified` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Tự động phân loại qua QR' AFTER `qr_data`,
ADD COLUMN `classification_confidence` DECIMAL(3,2) DEFAULT NULL COMMENT 'Độ tin cậy (0.00-1.00)' AFTER `auto_classified`,

-- File metadata
ADD COLUMN `file_size_bytes` BIGINT DEFAULT NULL COMMENT 'Kích thước file (bytes)' AFTER `classification_confidence`,
ADD COLUMN `mime_type` VARCHAR(100) DEFAULT NULL COMMENT 'MIME type' AFTER `file_size_bytes`,
ADD COLUMN `file_hash` VARCHAR(64) DEFAULT NULL COMMENT 'SHA-256 hash để detect duplicate' AFTER `mime_type`,

-- Versioning
ADD COLUMN `version` INT(11) NOT NULL DEFAULT 1 COMMENT 'Version của tài liệu' AFTER `file_hash`,
ADD COLUMN `is_latest_version` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Có phải version mới nhất' AFTER `version`,
ADD COLUMN `previous_version_id` INT(11) DEFAULT NULL COMMENT 'Link to previous version' AFTER `is_latest_version`,

-- Status & Verification
ADD COLUMN `status` ENUM('Draft', 'Submitted', 'Verified', 'Rejected', 'Expired') NOT NULL DEFAULT 'Submitted' AFTER `previous_version_id`,
ADD COLUMN `verified_by_id` INT(11) DEFAULT NULL COMMENT 'User xác minh tài liệu' AFTER `status`,
ADD COLUMN `verified_date` DATE DEFAULT NULL AFTER `verified_by_id`,
ADD COLUMN `verification_notes` TEXT DEFAULT NULL AFTER `verified_date`,

-- Expiry tracking (for documents with expiry like insurance, licenses)
ADD COLUMN `has_expiry` BOOLEAN NOT NULL DEFAULT FALSE AFTER `verification_notes`,
ADD COLUMN `expiry_date` DATE DEFAULT NULL COMMENT 'Ngày hết hạn' AFTER `has_expiry`,
ADD COLUMN `expiry_alert_sent` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Đã gửi cảnh báo hết hạn' AFTER `expiry_date`,

-- Additional metadata
ADD COLUMN `description` TEXT DEFAULT NULL COMMENT 'Mô tả tài liệu' AFTER `expiry_alert_sent`,
ADD COLUMN `tags` JSON DEFAULT NULL COMMENT 'Tags để tìm kiếm: ["passport", "id_card", "vehicle"]' AFTER `description`;

-- Create indexes
CREATE INDEX `idx_document_code` ON `application_documents` (`document_code`);
CREATE INDEX `idx_document_category` ON `application_documents` (`document_category`);
CREATE INDEX `idx_document_type` ON `application_documents` (`document_type`);
CREATE INDEX `idx_qr_token` ON `application_documents` (`qr_token`);
CREATE INDEX `idx_file_hash` ON `application_documents` (`file_hash`);
CREATE INDEX `idx_status` ON `application_documents` (`status`);
CREATE INDEX `idx_expiry_date` ON `application_documents` (`expiry_date`);
CREATE INDEX `idx_latest_version` ON `application_documents` (`is_latest_version`);

-- Add foreign keys
ALTER TABLE `application_documents`
ADD CONSTRAINT `fk_doc_verified_by` FOREIGN KEY (`verified_by_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `fk_doc_previous_version` FOREIGN KEY (`previous_version_id`)
    REFERENCES `application_documents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Generate document codes for existing records
UPDATE `application_documents`
SET `document_code` = CONCAT('DOC-', DATE_FORMAT(uploaded_at, '%Y%m%d'), '-', LPAD(id, 4, '0'));

-- Make document_code unique
ALTER TABLE `application_documents`
ADD UNIQUE KEY `unique_document_code` (`document_code`);

-- Update existing documents with categories
UPDATE `application_documents`
SET
    `document_category` = CASE
        WHEN document_name LIKE '%CMND%' OR document_name LIKE '%CCCD%' THEN 'Giấy tờ tùy thân'
        WHEN document_name LIKE '%hop_dong%' OR document_name LIKE 'Hop_dong%' THEN 'Hợp đồng'
        WHEN document_name LIKE '%sao_ke%' OR document_name LIKE 'Sao_ke%' THEN 'Sao kê'
        WHEN document_name LIKE '%luong%' THEN 'Thu nhập'
        WHEN document_name LIKE '%bao_hiem%' THEN 'Bảo hiểm'
        ELSE 'Khác'
    END,
    `document_type` = 'Required',
    `status` = 'Verified',
    `verified_by_id` = 2,  -- CVTĐ
    `verified_date` = DATE(uploaded_at),
    `mime_type` = 'application/pdf';

-- Set expiry for insurance documents
UPDATE `application_documents`
SET
    `has_expiry` = TRUE,
    `expiry_date` = DATE_ADD(uploaded_at, INTERVAL 1 YEAR)
WHERE document_name LIKE '%bao_hiem%';

-- Add tags
UPDATE `application_documents`
SET `tags` = JSON_ARRAY('contract', 'vehicle', 'purchase_agreement')
WHERE document_name LIKE '%hop_dong%mua%ban%xe%';

UPDATE `application_documents`
SET `tags` = JSON_ARRAY('salary', 'income', 'bank_statement')
WHERE document_name LIKE '%sao_ke%luong%';
