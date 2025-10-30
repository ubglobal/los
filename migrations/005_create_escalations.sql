-- Migration 005: Create escalations table
-- Description: Khiếu nại/Leo thang - khi hồ sơ bị reject, RM có thể escalate lên cấp cao hơn
-- Author: Claude AI
-- Date: 2025-10-30

CREATE TABLE IF NOT EXISTS `escalations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,

    -- Link to either credit application or disbursement
    `application_id` INT(11) DEFAULT NULL COMMENT 'Link to credit_applications (nếu khiếu nại HSTD)',
    `disbursement_id` INT(11) DEFAULT NULL COMMENT 'Link to disbursements (nếu khiếu nại giải ngân)',
    `escalation_type` ENUM('Credit', 'Disbursement') NOT NULL COMMENT 'Loại khiếu nại',

    -- Escalation details
    `reason` TEXT NOT NULL COMMENT 'Lý do khiếu nại',
    `supporting_documents` TEXT DEFAULT NULL COMMENT 'Danh sách tài liệu hỗ trợ (JSON array)',
    `urgency_level` ENUM('Normal', 'High', 'Critical') NOT NULL DEFAULT 'Normal',

    -- Users involved
    `escalated_by_id` INT(11) NOT NULL COMMENT 'User tạo khiếu nại (thường là RM)',
    `escalated_to_id` INT(11) NOT NULL COMMENT 'User nhận khiếu nại (cấp cao hơn người từ chối)',
    `original_rejector_id` INT(11) DEFAULT NULL COMMENT 'User đã từ chối hồ sơ ban đầu',

    -- Status & Resolution
    `status` ENUM('Pending', 'Under Review', 'Resolved - Approved', 'Resolved - Rejected', 'Cancelled') NOT NULL DEFAULT 'Pending',
    `resolution` TEXT DEFAULT NULL COMMENT 'Kết luận xử lý khiếu nại',
    `resolved_by_id` INT(11) DEFAULT NULL COMMENT 'User giải quyết khiếu nại',
    `resolved_date` DATETIME DEFAULT NULL,

    -- Dates
    `escalated_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày tạo khiếu nại',
    `expected_response_date` DATETIME DEFAULT NULL COMMENT 'Hạn phản hồi',

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_application` (`application_id`),
    KEY `idx_disbursement` (`disbursement_id`),
    KEY `idx_escalation_type` (`escalation_type`),
    KEY `idx_status` (`status`),
    KEY `idx_escalated_to` (`escalated_to_id`),
    KEY `idx_escalated_by` (`escalated_by_id`),

    CONSTRAINT `fk_esc_application` FOREIGN KEY (`application_id`)
        REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_esc_disbursement` FOREIGN KEY (`disbursement_id`)
        REFERENCES `disbursements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_esc_escalated_by` FOREIGN KEY (`escalated_by_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_esc_escalated_to` FOREIGN KEY (`escalated_to_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_esc_original_rejector` FOREIGN KEY (`original_rejector_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_esc_resolved_by` FOREIGN KEY (`resolved_by_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,

    -- Business rule: phải link với application HOẶC disbursement (không được cả hai hoặc không có)
    CONSTRAINT `chk_escalation_target` CHECK (
        (`application_id` IS NOT NULL AND `disbursement_id` IS NULL) OR
        (`application_id` IS NULL AND `disbursement_id` IS NOT NULL)
    ),

    -- Business rule: escalation_type phải khớp với target
    CONSTRAINT `chk_escalation_type_match` CHECK (
        (`escalation_type` = 'Credit' AND `application_id` IS NOT NULL) OR
        (`escalation_type` = 'Disbursement' AND `disbursement_id` IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Khiếu nại/Leo thang';

-- Sample data
-- Case: HSTD số 5 bị reject bởi CPD, RM escalate lên GDK
INSERT INTO `escalations` (`application_id`, `disbursement_id`, `escalation_type`, `reason`, `urgency_level`, `escalated_by_id`, `escalated_to_id`, `original_rejector_id`, `status`, `resolution`, `resolved_by_id`, `resolved_date`, `escalated_date`, `expected_response_date`) VALUES
(5, NULL, 'Credit',
'Kính gửi Ban lãnh đạo,\n\nTôi xin escalate hồ sơ CAR.2024.1005 của khách hàng Vũ Đức Thắng đã bị từ chối bởi CPD với lý do "Không đủ khả năng trả nợ".\n\nLý do escalate:\n1. Khách hàng có thu nhập ổn định 35 triệu/tháng từ lương và kinh doanh phụ\n2. TSBĐ (xe Honda CRV trị giá 1.2 tỷ) đủ mạnh, LTV chỉ 67%\n3. Khách hàng có mối quan hệ tốt với ngân hàng, có tiết kiệm 200 triệu\n4. Đã bổ sung thêm người bảo lãnh (vợ khách hàng, có thu nhập 20 triệu/tháng)\n\nĐề nghị Ban lãnh đạo xem xét lại hồ sơ này.\n\nTrân trọng,\nNguyễn Văn An (RM)',
'High', 1, 5, 3, 'Resolved - Approved',
'Sau khi xem xét kỹ lưỡng:\n- Thu nhập kết hợp của vợ chồng khách hàng: 55 triệu/tháng\n- TSBĐ đủ mạnh\n- Khách hàng có lịch sử giao dịch tốt\n\nQuyết định: CHẤP THUẬN escalation, yêu cầu CPD xem xét lại với điều kiện bổ sung người đồng vay (vợ).\n\nNguyễn Minh Khôi\nGiám đốc Khối',
5, '2024-08-20 14:30:00', '2024-08-18 09:15:00', '2024-08-20 17:00:00');
