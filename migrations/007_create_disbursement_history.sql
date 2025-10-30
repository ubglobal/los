-- Migration 007: Create disbursement_history table
-- Description: Lịch sử workflow của hồ sơ giải ngân (tương tự application_history)
-- Author: Claude AI
-- Date: 2025-10-30

CREATE TABLE IF NOT EXISTS `disbursement_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `disbursement_id` INT(11) NOT NULL COMMENT 'Link to disbursements',
    `user_id` INT(11) NOT NULL COMMENT 'User thực hiện action',

    -- Action details
    `action` VARCHAR(100) NOT NULL COMMENT 'Save/Next/Request Info/Approve/Reject/Return/Escalate/Discard/Close',
    `action_type` ENUM('Workflow', 'Data Update', 'Comment', 'System') NOT NULL DEFAULT 'Workflow',

    -- Workflow tracking
    `from_stage` VARCHAR(100) DEFAULT NULL COMMENT 'Từ stage nào',
    `to_stage` VARCHAR(100) DEFAULT NULL COMMENT 'Đến stage nào',
    `from_status` VARCHAR(50) DEFAULT NULL,
    `to_status` VARCHAR(50) DEFAULT NULL,

    -- Comments & reasons
    `comment` TEXT DEFAULT NULL COMMENT 'Nhận xét/Lý do',
    `rejection_reason` TEXT DEFAULT NULL COMMENT 'Lý do từ chối (nếu action = Reject)',

    -- Changed data tracking
    `changed_fields` JSON DEFAULT NULL COMMENT 'Danh sách fields thay đổi {"field": "old_value -> new_value"}',

    -- IP & User Agent
    `ip_address` VARCHAR(50) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,

    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_disbursement` (`disbursement_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_timestamp` (`timestamp`),

    CONSTRAINT `fk_disb_hist_disbursement` FOREIGN KEY (`disbursement_id`)
        REFERENCES `disbursements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_disb_hist_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử giải ngân';

-- Sample data for disbursement 1 (đã giải ngân thành công)
INSERT INTO `disbursement_history` (`disbursement_id`, `user_id`, `action`, `action_type`, `from_stage`, `to_stage`, `from_status`, `to_status`, `comment`, `timestamp`) VALUES
(1, 1, 'Khởi tạo', 'Workflow', NULL, 'Khởi tạo', NULL, 'Draft', 'Tạo hồ sơ giải ngân cho HSTD CAR.2024.1004', '2024-05-28 09:00:00'),
(1, 1, 'Save', 'Data Update', 'Khởi tạo', 'Khởi tạo', 'Draft', 'Draft', 'Cập nhật thông tin người thụ hưởng', '2024-05-28 09:15:00'),
(1, 1, 'Next', 'Workflow', 'Khởi tạo', 'Kiểm tra điều kiện', 'Draft', 'Pending', 'Gửi kiểm tra điều kiện giải ngân', '2024-05-28 10:00:00'),
(1, 2, 'Save', 'Data Update', 'Kiểm tra điều kiện', 'Kiểm tra điều kiện', 'Pending', 'Pending', 'Xác nhận các điều kiện đã đáp ứng', '2024-05-29 14:30:00'),
(1, 2, 'Next', 'Workflow', 'Kiểm tra điều kiện', 'Chờ phê duyệt', 'Pending', 'In Review', 'Tất cả điều kiện giải ngân đã đủ. Trình phê duyệt.', '2024-05-29 15:00:00'),
(1, 3, 'Approve', 'Workflow', 'Chờ phê duyệt', 'Đã giải ngân', 'In Review', 'Approved', 'Phê duyệt giải ngân 500 triệu thanh toán cho đại lý Hyundai', '2024-05-30 10:30:00'),
(1, 3, 'Disbursed', 'System', 'Đã giải ngân', 'Đã giải ngân', 'Approved', 'Disbursed', 'Hệ thống Core Banking đã thực hiện chuyển khoản thành công. Ref: CB20240530001', '2024-05-30 11:00:00');
