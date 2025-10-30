-- Migration 010: Alter credit_applications table
-- Description: Thêm columns mới cho trạng thái "Đã có hiệu lực", SLA tracking
-- Author: Claude AI
-- Date: 2025-10-30

-- Add new columns
ALTER TABLE `credit_applications`
ADD COLUMN `effective_date` DATE DEFAULT NULL COMMENT 'Ngày có hiệu lực (sau khi hoàn tất pháp lý)' AFTER `updated_at`,
ADD COLUMN `legal_completed` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Đã hoàn tất thủ tục pháp lý' AFTER `effective_date`,
ADD COLUMN `legal_completed_date` DATE DEFAULT NULL COMMENT 'Ngày hoàn tất pháp lý' AFTER `legal_completed`,
ADD COLUMN `legal_notes` TEXT DEFAULT NULL COMMENT 'Ghi chú pháp lý' AFTER `legal_completed_date`,

-- SLA tracking
ADD COLUMN `sla_due_date` DATETIME DEFAULT NULL COMMENT 'Hạn SLA (tính từ created_at + SLA của bước hiện tại)' AFTER `legal_notes`,
ADD COLUMN `sla_status` ENUM('On Track', 'Warning', 'Overdue') DEFAULT 'On Track' COMMENT 'Trạng thái SLA' AFTER `sla_due_date`,

-- Workflow tracking
ADD COLUMN `workflow_type` VARCHAR(50) NOT NULL DEFAULT 'Credit_Approval' COMMENT 'Loại workflow' AFTER `sla_status`,
ADD COLUMN `current_step_id` INT(11) DEFAULT NULL COMMENT 'Link to workflow_steps - bước hiện tại' AFTER `workflow_type`,
ADD COLUMN `previous_stage` VARCHAR(100) DEFAULT NULL COMMENT 'Stage trước đó (để tracking)' AFTER `current_step_id`,

-- Additional tracking
ADD COLUMN `submitted_date` DATE DEFAULT NULL COMMENT 'Ngày gửi thẩm định (lần đầu)' AFTER `previous_stage`,
ADD COLUMN `approved_date` DATE DEFAULT NULL COMMENT 'Ngày phê duyệt' AFTER `submitted_date`,
ADD COLUMN `rejected_date` DATE DEFAULT NULL COMMENT 'Ngày từ chối' AFTER `approved_date`,
ADD COLUMN `rejection_reason` TEXT DEFAULT NULL COMMENT 'Lý do từ chối' AFTER `rejected_date`;

-- Create indexes for new columns
CREATE INDEX `idx_effective_date` ON `credit_applications` (`effective_date`);
CREATE INDEX `idx_legal_completed` ON `credit_applications` (`legal_completed`);
CREATE INDEX `idx_sla_status` ON `credit_applications` (`sla_status`);
CREATE INDEX `idx_current_step` ON `credit_applications` (`current_step_id`);
CREATE INDEX `idx_approved_date` ON `credit_applications` (`approved_date`);

-- Add foreign key for current_step_id
ALTER TABLE `credit_applications`
ADD CONSTRAINT `fk_app_current_step` FOREIGN KEY (`current_step_id`)
    REFERENCES `workflow_steps` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Update existing data
-- Set approved_date for already approved applications
UPDATE `credit_applications`
SET `approved_date` = DATE(updated_at)
WHERE `status` = 'Đã phê duyệt';

-- Set rejected_date for rejected applications
UPDATE `credit_applications`
SET `rejected_date` = DATE(updated_at)
WHERE `status` = 'Đã từ chối';

-- Set effective_date and legal_completed for already approved application (HSTD 4)
-- In real scenario, this would be done manually by admin after legal completion
UPDATE `credit_applications`
SET
    `effective_date` = '2024-05-25',
    `legal_completed` = TRUE,
    `legal_completed_date` = '2024-05-25',
    `legal_notes` = 'HĐTD số 001/2024/HĐTD đã ký kết ngày 25/05/2024. Đã hoàn tất thủ tục pháp lý.'
WHERE `id` = 4;

-- Set workflow_type and sla_status for all applications
UPDATE `credit_applications`
SET
    `workflow_type` = 'Credit_Approval',
    `sla_status` = CASE
        WHEN `status` IN ('Đã phê duyệt', 'Đã từ chối') THEN 'On Track'
        WHEN DATEDIFF(NOW(), created_at) > 5 THEN 'Overdue'
        WHEN DATEDIFF(NOW(), created_at) > 3 THEN 'Warning'
        ELSE 'On Track'
    END;

-- Calculate and set sla_due_date based on stage
-- (In real system, this would be calculated by workflow engine)
UPDATE `credit_applications` SET `sla_due_date` = DATE_ADD(created_at, INTERVAL 24 HOUR) WHERE `stage` = 'Chờ thẩm định';
UPDATE `credit_applications` SET `sla_due_date` = DATE_ADD(updated_at, INTERVAL 48 HOUR) WHERE `stage` = 'Chờ phê duyệt';
UPDATE `credit_applications` SET `sla_due_date` = DATE_ADD(updated_at, INTERVAL 48 HOUR) WHERE `stage` = 'Chờ phê duyệt cấp cao';
