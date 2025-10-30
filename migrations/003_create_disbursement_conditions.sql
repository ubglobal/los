-- Migration 003: Create disbursement_conditions table
-- Description: Điều kiện giải ngân - checklist phải đáp ứng trước khi giải ngân
-- Author: Claude AI
-- Date: 2025-10-30

CREATE TABLE IF NOT EXISTS `disbursement_conditions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `disbursement_id` INT(11) NOT NULL COMMENT 'Link to disbursements',
    `condition_text` TEXT NOT NULL COMMENT 'Nội dung điều kiện',
    `condition_type` ENUM('Legal', 'Collateral', 'Insurance', 'Documentation', 'Other') NOT NULL DEFAULT 'Other',
    `is_mandatory` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Bắt buộc hay không',
    `is_met` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Đã đáp ứng chưa',
    `met_date` DATE DEFAULT NULL COMMENT 'Ngày đáp ứng',
    `met_by_id` INT(11) DEFAULT NULL COMMENT 'User xác nhận đã đáp ứng',
    `verification_document` VARCHAR(255) DEFAULT NULL COMMENT 'File chứng từ xác minh',
    `notes` TEXT DEFAULT NULL COMMENT 'Ghi chú',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_disbursement` (`disbursement_id`),
    KEY `idx_is_met` (`is_met`),
    KEY `idx_condition_type` (`condition_type`),

    CONSTRAINT `fk_disb_cond_disbursement` FOREIGN KEY (`disbursement_id`)
        REFERENCES `disbursements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_disb_cond_met_by` FOREIGN KEY (`met_by_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Điều kiện giải ngân';

-- Sample data
INSERT INTO `disbursement_conditions` (`disbursement_id`, `condition_text`, `condition_type`, `is_mandatory`, `is_met`, `met_date`, `met_by_id`, `notes`) VALUES
(1, 'Hợp đồng tín dụng đã ký kết và có hiệu lực', 'Legal', TRUE, TRUE, '2024-05-25', 2, 'HĐTD số 001/2024/HĐTD đã ký ngày 25/05/2024'),
(1, 'TSBĐ (xe ô tô) đã được đăng ký thế chấp tại cơ quan có thẩm quyền', 'Collateral', TRUE, TRUE, '2024-05-28', 2, 'Giấy chứng nhận đăng ký xe đã đóng dấu thế chấp'),
(1, 'Hợp đồng bảo hiểm vật chất xe ô tô thụ hưởng cho Ngân hàng', 'Insurance', TRUE, TRUE, '2024-05-30', 2, 'Hợp đồng BH số 2024-AUTO-001'),
(1, 'Hợp đồng mua bán xe ô tô giữa khách hàng và đại lý', 'Documentation', TRUE, TRUE, '2024-05-26', 1, 'HĐMB số 123/2024'),
(1, 'Khách hàng mở tài khoản thanh toán tại Ngân hàng', 'Other', TRUE, TRUE, '2024-05-20', 1, 'TK số 1234567890');
