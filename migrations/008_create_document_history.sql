-- Migration 008: Create document_history table
-- Description: Lịch sử thay đổi tài liệu (upload, update, delete, view)
-- Author: Claude AI
-- Date: 2025-10-30

CREATE TABLE IF NOT EXISTS `document_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `document_id` INT(11) NOT NULL COMMENT 'Link to application_documents',

    -- Action details
    `action` ENUM('Upload', 'Update', 'Delete', 'View', 'Download', 'Classify') NOT NULL COMMENT 'Loại thao tác',

    -- File tracking
    `old_file_path` VARCHAR(255) DEFAULT NULL COMMENT 'File cũ (trước khi update/delete)',
    `new_file_path` VARCHAR(255) DEFAULT NULL COMMENT 'File mới (sau khi upload/update)',
    `old_document_name` VARCHAR(255) DEFAULT NULL,
    `new_document_name` VARCHAR(255) DEFAULT NULL,

    -- User & reason
    `changed_by_id` INT(11) NOT NULL COMMENT 'User thực hiện thao tác',
    `change_reason` TEXT DEFAULT NULL COMMENT 'Lý do thay đổi/xóa',

    -- QR code classification (optional feature)
    `qr_code_data` VARCHAR(255) DEFAULT NULL COMMENT 'Dữ liệu từ QR code (nếu có)',
    `auto_classified` BOOLEAN DEFAULT FALSE COMMENT 'Tự động phân loại qua QR',

    -- Additional metadata
    `file_size_bytes` BIGINT DEFAULT NULL COMMENT 'Kích thước file (bytes)',
    `mime_type` VARCHAR(100) DEFAULT NULL,

    -- IP & User Agent
    `ip_address` VARCHAR(50) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,

    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_document` (`document_id`),
    KEY `idx_changed_by` (`changed_by_id`),
    KEY `idx_action` (`action`),
    KEY `idx_timestamp` (`timestamp`),

    CONSTRAINT `fk_doc_hist_document` FOREIGN KEY (`document_id`)
        REFERENCES `application_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_doc_hist_user` FOREIGN KEY (`changed_by_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử tài liệu';

-- Sample data for existing documents
INSERT INTO `document_history` (`document_id`, `action`, `old_file_path`, `new_file_path`, `old_document_name`, `new_document_name`, `changed_by_id`, `change_reason`, `file_size_bytes`, `mime_type`, `timestamp`) VALUES
(1, 'Upload', NULL, 'sample_doc.pdf', NULL, 'Hop_dong_mua_ban_xe.pdf', 1, 'Upload tài liệu HĐMB xe', 1245678, 'application/pdf', '2024-05-15 14:20:00'),
(2, 'Upload', NULL, 'sample_doc.pdf', NULL, 'Sao_ke_luong.pdf', 1, 'Upload sao kê lương 6 tháng', 987654, 'application/pdf', '2024-05-15 14:25:00'),
(1, 'View', NULL, NULL, NULL, NULL, 2, NULL, NULL, NULL, '2024-05-18 10:30:00'),
(2, 'View', NULL, NULL, NULL, NULL, 2, NULL, NULL, NULL, '2024-05-18 10:31:00'),
(1, 'View', NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, '2024-05-20 11:15:00'),
(2, 'View', NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, '2024-05-20 11:16:00');
