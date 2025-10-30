-- Migration 006: Create workflow_steps table
-- Description: Định nghĩa các bước trong quy trình (workflow configuration)
-- Author: Claude AI
-- Date: 2025-10-30

CREATE TABLE IF NOT EXISTS `workflow_steps` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `workflow_type` ENUM('Credit_Approval', 'Disbursement') NOT NULL COMMENT 'Loại quy trình',
    `step_code` VARCHAR(50) NOT NULL COMMENT 'Mã bước (unique trong workflow)',
    `step_name` VARCHAR(100) NOT NULL COMMENT 'Tên bước hiển thị',
    `step_order` INT(11) NOT NULL COMMENT 'Thứ tự bước (1, 2, 3, ...)',
    `role_required` VARCHAR(50) NOT NULL COMMENT 'Role yêu cầu để xử lý bước này',

    -- Approval limits (for approval steps)
    `approval_limit_min` DECIMAL(20,2) DEFAULT NULL COMMENT 'Hạn mức tối thiểu (VD: CPD duyệt từ 0-5 tỷ)',
    `approval_limit_max` DECIMAL(20,2) DEFAULT NULL COMMENT 'Hạn mức tối đa',

    -- Actions available in this step
    `allowed_actions` JSON NOT NULL COMMENT 'Danh sách actions cho phép: ["Save","Next","Approve","Reject","Return","Request Info","Escalate","Discard"]',

    -- SLA
    `sla_hours` INT(11) DEFAULT NULL COMMENT 'SLA tính bằng giờ',
    `sla_warning_hours` INT(11) DEFAULT NULL COMMENT 'Cảnh báo trước khi quá hạn (giờ)',

    -- Next steps logic
    `next_step_on_approve` INT(11) DEFAULT NULL COMMENT 'Bước tiếp theo nếu Approve',
    `next_step_on_reject` INT(11) DEFAULT NULL COMMENT 'Bước tiếp theo nếu Reject (thường NULL = kết thúc)',
    `return_to_step` INT(11) DEFAULT NULL COMMENT 'Bước quay lại nếu Return',

    `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Bước này có đang hoạt động',
    `description` TEXT DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_workflow_step` (`workflow_type`, `step_code`),
    KEY `idx_workflow_type` (`workflow_type`),
    KEY `idx_step_order` (`step_order`),
    KEY `idx_role` (`role_required`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Định nghĩa quy trình workflow';

-- ============================================
-- WORKFLOW: Credit Approval (Phê duyệt tín dụng)
-- ============================================
INSERT INTO `workflow_steps` (`workflow_type`, `step_code`, `step_name`, `step_order`, `role_required`, `approval_limit_min`, `approval_limit_max`, `allowed_actions`, `sla_hours`, `sla_warning_hours`, `next_step_on_approve`, `next_step_on_reject`, `return_to_step`, `is_active`, `description`) VALUES
-- Step 1: Khởi tạo (RM)
('Credit_Approval', 'INIT', 'Khởi tạo hồ sơ', 1, 'CVQHKH', NULL, NULL,
 '["Save", "Next", "Discard"]',
 24, 4, 2, NULL, NULL, TRUE,
 'CVQHKH/RM tạo hồ sơ, nhập thông tin khách hàng, TSBĐ, hạn mức, điều kiện. Actions: Save (lưu nháp), Next (gửi thẩm định), Discard (hủy)'),

-- Step 2: Thẩm định (Credit Analyst)
('Credit_Approval', 'REVIEW', 'Thẩm định', 2, 'CVTĐ', NULL, NULL,
 '["Save", "Next", "Return", "Request Info"]',
 48, 8, 3, NULL, 1, TRUE,
 'CVTĐ thẩm định hồ sơ, kiểm tra tính đầy đủ và hợp lệ. Actions: Next (trình phê duyệt), Return (trả lại RM), Request Info (yêu cầu bổ sung)'),

-- Step 3: Phê duyệt cấp CPD (0 - 5 tỷ)
('Credit_Approval', 'APPROVE_CPD', 'Phê duyệt CPD', 3, 'CPD', 0, 5000000000.00,
 '["Save", "Approve", "Reject", "Return", "Request Info", "Next"]',
 24, 4, NULL, NULL, 2, TRUE,
 'CPD phê duyệt hồ sơ dưới 5 tỷ. Actions: Approve (phê duyệt), Reject (từ chối), Return (trả lại thẩm định), Next (trình GDK nếu vượt hạn mức)'),

-- Step 4: Phê duyệt cấp GDK (trên 5 tỷ)
('Credit_Approval', 'APPROVE_GDK', 'Phê duyệt GĐK', 4, 'GDK', 5000000000.01, NULL,
 '["Save", "Approve", "Reject", "Return", "Request Info"]',
 48, 12, NULL, NULL, 2, TRUE,
 'GĐK phê duyệt hồ sơ trên 5 tỷ hoặc escalation. Actions: Approve (phê duyệt), Reject (từ chối), Return (trả lại)'),

-- Step 5: Hoàn tất pháp lý (Admin/Legal)
('Credit_Approval', 'LEGAL', 'Hoàn tất pháp lý', 5, 'Admin', NULL, NULL,
 '["Save", "Next"]',
 72, 24, NULL, NULL, NULL, TRUE,
 'Admin/Legal hoàn tất thủ tục pháp lý, đánh dấu "Đã có hiệu lực". Sau bước này, HSTD có thể giải ngân.');

-- ============================================
-- WORKFLOW: Disbursement (Giải ngân)
-- ============================================
INSERT INTO `workflow_steps` (`workflow_type`, `step_code`, `step_name`, `step_order`, `role_required`, `approval_limit_min`, `approval_limit_max`, `allowed_actions`, `sla_hours`, `sla_warning_hours`, `next_step_on_approve`, `next_step_on_reject`, `return_to_step`, `is_active`, `description`) VALUES
-- Step 1: Khởi tạo giải ngân (RM)
('Disbursement', 'INIT', 'Khởi tạo giải ngân', 1, 'CVQHKH', NULL, NULL,
 '["Save", "Next", "Discard"]',
 12, 2, 7, NULL, NULL, TRUE,
 'RM tạo hồ sơ giải ngân từ HSTD đã có hiệu lực. Nhập thông tin người thụ hưởng, mục đích, điều kiện giải ngân.'),

-- Step 2: Kiểm tra điều kiện giải ngân (Kiểm soát)
('Disbursement', 'CHECK_CONDITIONS', 'Kiểm tra điều kiện', 2, 'CVTĐ', NULL, NULL,
 '["Save", "Next", "Return", "Request Info"]',
 24, 4, 8, NULL, 6, TRUE,
 'Kiểm soát kiểm tra tất cả điều kiện giải ngân đã đáp ứng chưa. Nếu thiếu thì Request Info về RM.'),

-- Step 3: Phê duyệt giải ngân (Thủ quỹ/CPD)
('Disbursement', 'APPROVE_DISB', 'Phê duyệt giải ngân', 3, 'CPD', NULL, NULL,
 '["Approve", "Reject", "Return", "Request Info"]',
 12, 2, NULL, NULL, 7, TRUE,
 'Thủ quỹ/CPD phê duyệt giải ngân. Actions: Approve (giải ngân), Reject (từ chối), Return (trả lại kiểm tra).');

-- Add foreign key self-references for next steps
-- (will be done after all records are inserted)
-- ALTER TABLE workflow_steps
--   ADD CONSTRAINT fk_next_step_approve FOREIGN KEY (next_step_on_approve) REFERENCES workflow_steps(id),
--   ADD CONSTRAINT fk_next_step_reject FOREIGN KEY (next_step_on_reject) REFERENCES workflow_steps(id),
--   ADD CONSTRAINT fk_return_step FOREIGN KEY (return_to_step) REFERENCES workflow_steps(id);
