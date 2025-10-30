-- Migration 004: Create approval_conditions table
-- Description: Điều kiện phê duyệt tín dụng - có thể xin ngoại lệ
-- Author: Claude AI
-- Date: 2025-10-30

CREATE TABLE IF NOT EXISTS `approval_conditions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `application_id` INT(11) NOT NULL COMMENT 'Link to credit_applications',
    `condition_code` VARCHAR(50) DEFAULT NULL COMMENT 'Mã điều kiện (nếu có chuẩn hóa)',
    `condition_text` TEXT NOT NULL COMMENT 'Nội dung điều kiện phê duyệt',
    `condition_category` ENUM('Credit Rating', 'Income', 'Collateral', 'Legal', 'Policy', 'Other') NOT NULL DEFAULT 'Other',

    -- Status điều kiện
    `is_mandatory` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Bắt buộc phải đáp ứng',
    `allow_exception` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Cho phép xin ngoại lệ',

    -- Exception request (xin ngoại lệ)
    `is_exception_requested` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Đã xin ngoại lệ chưa',
    `exception_reason` TEXT DEFAULT NULL COMMENT 'Lý do xin ngoại lệ',
    `exception_requested_by_id` INT(11) DEFAULT NULL COMMENT 'User xin ngoại lệ (thường là RM)',
    `exception_requested_date` DATE DEFAULT NULL,

    -- Exception approval (duyệt ngoại lệ)
    `exception_approved` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Ngoại lệ đã được chấp thuận',
    `exception_approved_by_id` INT(11) DEFAULT NULL COMMENT 'User duyệt ngoại lệ (CPD/GDK)',
    `exception_approved_date` DATE DEFAULT NULL,
    `exception_rejection_reason` TEXT DEFAULT NULL COMMENT 'Lý do từ chối ngoại lệ',

    -- Compliance status
    `is_met` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Điều kiện đã đáp ứng',
    `met_date` DATE DEFAULT NULL,
    `met_by_id` INT(11) DEFAULT NULL COMMENT 'User xác nhận đã đáp ứng',
    `verification_notes` TEXT DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_application` (`application_id`),
    KEY `idx_exception_status` (`is_exception_requested`, `exception_approved`),
    KEY `idx_is_met` (`is_met`),
    KEY `idx_category` (`condition_category`),

    CONSTRAINT `fk_appr_cond_application` FOREIGN KEY (`application_id`)
        REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_appr_cond_exception_req_by` FOREIGN KEY (`exception_requested_by_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_appr_cond_exception_appr_by` FOREIGN KEY (`exception_approved_by_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_appr_cond_met_by` FOREIGN KEY (`met_by_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE

    -- Business rule: nếu xin ngoại lệ thì điều kiện phải cho phép ngoại lệ
    -- NOTE: CHECK constraint removed for MySQL compatibility
    -- This rule is enforced in application layer (exception_escalation_functions.php)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Điều kiện phê duyệt tín dụng';

-- Sample data
INSERT INTO `approval_conditions` (`application_id`, `condition_code`, `condition_text`, `condition_category`, `is_mandatory`, `allow_exception`, `is_exception_requested`, `exception_reason`, `exception_requested_by_id`, `exception_requested_date`, `exception_approved`, `exception_approved_by_id`, `exception_approved_date`, `is_met`, `met_date`, `met_by_id`) VALUES
-- HSTD 4 (Đã phê duyệt) - tất cả điều kiện đã đáp ứng
(4, 'CRD-001', 'Xếp hạng tín dụng nội bộ tối thiểu A', 'Credit Rating', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-05-20', 2),
(4, 'INC-001', 'Thu nhập ổn định tối thiểu 25 triệu/tháng', 'Income', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-05-20', 2),
(4, 'COL-001', 'Tài sản bảo đảm có giá trị tối thiểu 120% giá trị khoản vay', 'Collateral', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-05-22', 2),
(4, 'LEG-001', 'Không có nợ xấu tại các TCTD khác', 'Legal', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-05-18', 2),

-- HSTD 1 (Đang xử lý) - có 1 điều kiện xin ngoại lệ và đã được duyệt
(1, 'CRD-001', 'Xếp hạng tín dụng nội bộ tối thiểu A', 'Credit Rating', TRUE, TRUE, TRUE, 'Khách hàng mới, chưa có lịch sử giao dịch tại ngân hàng nên chưa có xếp hạng. Tuy nhiên thu nhập ổn định và TSBĐ đủ mạnh.', 1, '2024-10-10', TRUE, 3, '2024-10-12', FALSE, NULL, NULL),
(1, 'INC-001', 'Thu nhập ổn định tối thiểu 25 triệu/tháng', 'Income', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-10-08', 2),
(1, 'COL-001', 'Tài sản bảo đảm có giá trị tối thiểu 120% giá trị khoản vay', 'Collateral', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-10-09', 2),
(1, 'LEG-001', 'Không có nợ xấu tại các TCTD khác', 'Legal', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-10-08', 2),

-- HSTD 3 (Chờ phê duyệt)
(3, 'CRD-001', 'Xếp hạng tín dụng nội bộ tối thiểu AA', 'Credit Rating', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-09-15', 2),
(3, 'INC-001', 'Thu nhập ổn định tối thiểu 35 triệu/tháng', 'Income', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-09-15', 2),
(3, 'COL-001', 'Tài sản bảo đảm có giá trị tối thiểu 150% giá trị khoản vay', 'Collateral', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-09-16', 2),
(3, 'POL-001', 'Tỷ lệ Loan-to-Value (LTV) không quá 70%', 'Policy', TRUE, FALSE, FALSE, NULL, NULL, NULL, FALSE, NULL, NULL, TRUE, '2024-09-16', 2);
