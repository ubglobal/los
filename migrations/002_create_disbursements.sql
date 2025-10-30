-- Migration 002: Create disbursements table
-- Description: Hồ sơ giải ngân - workflow riêng, link với facility
-- Author: Claude AI
-- Date: 2025-10-30

CREATE TABLE IF NOT EXISTS `disbursements` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `disbursement_code` VARCHAR(50) NOT NULL COMMENT 'Mã hồ sơ giải ngân (unique)',
    `application_id` INT(11) NOT NULL COMMENT 'Link to credit_applications',
    `facility_id` INT(11) NOT NULL COMMENT 'Link to facilities - giải ngân từ hạn mức nào',
    `disbursement_type` ENUM('Lần đầu', 'Rút vốn', 'Giải ngân theo tiến độ') NOT NULL DEFAULT 'Lần đầu',
    `amount` DECIMAL(20,2) NOT NULL COMMENT 'Số tiền giải ngân',
    `currency` VARCHAR(3) NOT NULL DEFAULT 'VND',
    `purpose` TEXT NOT NULL COMMENT 'Mục đích giải ngân',

    -- Thông tin thụ hưởng
    `beneficiary_type` ENUM('Chính chủ', 'Bên thứ 3') NOT NULL DEFAULT 'Chính chủ',
    `beneficiary_name` VARCHAR(255) NOT NULL COMMENT 'Tên người thụ hưởng',
    `beneficiary_account` VARCHAR(50) DEFAULT NULL COMMENT 'Số tài khoản',
    `beneficiary_bank` VARCHAR(255) DEFAULT NULL COMMENT 'Ngân hàng',

    -- Workflow
    `status` ENUM('Draft', 'Pending', 'In Review', 'Approved', 'Rejected', 'Disbursed', 'Cancelled') NOT NULL DEFAULT 'Draft',
    `stage` VARCHAR(100) NOT NULL DEFAULT 'Khởi tạo' COMMENT 'Bước hiện tại: Khởi tạo/Kiểm điều kiện/Chờ duyệt/Đã giải ngân',
    `assigned_to_id` INT(11) DEFAULT NULL COMMENT 'User đang xử lý',

    -- Users involved
    `created_by_id` INT(11) NOT NULL,
    `reviewed_by_id` INT(11) DEFAULT NULL COMMENT 'User kiểm tra điều kiện',
    `approved_by_id` INT(11) DEFAULT NULL COMMENT 'User phê duyệt cuối',

    -- Dates
    `requested_date` DATE NOT NULL COMMENT 'Ngày đề xuất giải ngân',
    `approved_date` DATE DEFAULT NULL,
    `disbursement_date` DATE DEFAULT NULL COMMENT 'Ngày thực tế giải ngân',
    `expected_disbursement_date` DATE DEFAULT NULL COMMENT 'Dự kiến giải ngân',

    -- SLA
    `sla_due_date` DATETIME DEFAULT NULL,
    `sla_status` ENUM('On Track', 'Warning', 'Overdue') DEFAULT 'On Track',

    -- Notes
    `notes` TEXT DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_disbursement_code` (`disbursement_code`),
    KEY `idx_application` (`application_id`),
    KEY `idx_facility` (`facility_id`),
    KEY `idx_status` (`status`),
    KEY `idx_stage` (`stage`),
    KEY `idx_assigned_to` (`assigned_to_id`),
    KEY `idx_requested_date` (`requested_date`),
    KEY `idx_disbursement_date` (`disbursement_date`),

    CONSTRAINT `fk_disb_application` FOREIGN KEY (`application_id`)
        REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_disb_facility` FOREIGN KEY (`facility_id`)
        REFERENCES `facilities` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_disb_assigned_to` FOREIGN KEY (`assigned_to_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_disb_created_by` FOREIGN KEY (`created_by_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_disb_reviewed_by` FOREIGN KEY (`reviewed_by_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_disb_approved_by` FOREIGN KEY (`approved_by_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk_disb_amount` CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hồ sơ giải ngân';

-- Sample data (sẽ được tạo sau khi có facilities)
INSERT INTO `disbursements` (`disbursement_code`, `application_id`, `facility_id`, `disbursement_type`, `amount`, `purpose`, `beneficiary_type`, `beneficiary_name`, `beneficiary_account`, `beneficiary_bank`, `status`, `stage`, `created_by_id`, `requested_date`) VALUES
('DISB-2024-0001', 4, 1, 'Lần đầu', 500000000.00, 'Thanh toán tiền mua xe Hyundai Accent theo hợp đồng số 123/2024', 'Bên thứ 3', 'Công ty TNHH Hyundai Thành Công Việt Nam', '1234567890', 'Vietcombank', 'Disbursed', 'Đã giải ngân', 1, '2024-06-01');
