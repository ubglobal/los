-- Migration 001: Create facilities table
-- Description: Hạn mức tín dụng - một HSTD có thể có nhiều hạn mức
-- Author: Claude AI
-- Date: 2025-10-30

CREATE TABLE IF NOT EXISTS `facilities` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `application_id` INT(11) NOT NULL COMMENT 'Link to credit_applications',
    `facility_code` VARCHAR(50) NOT NULL COMMENT 'Mã hạn mức (unique)',
    `facility_type` VARCHAR(100) NOT NULL COMMENT 'Loại hạn mức: Ngắn hạn/Dài hạn/LC/BL/Bảo lãnh',
    `product_id` INT(11) NOT NULL COMMENT 'Sản phẩm tín dụng',
    `amount` DECIMAL(20,2) NOT NULL COMMENT 'Số tiền hạn mức',
    `disbursed_amount` DECIMAL(20,2) NOT NULL DEFAULT 0 COMMENT 'Đã giải ngân',
    `available_amount` DECIMAL(20,2) GENERATED ALWAYS AS (`amount` - `disbursed_amount`) STORED COMMENT 'Còn lại',
    `currency` VARCHAR(3) NOT NULL DEFAULT 'VND' COMMENT 'Đơn vị tiền tệ',
    `status` ENUM('Pending', 'Active', 'Inactive', 'Closed', 'Expired') NOT NULL DEFAULT 'Pending' COMMENT 'Trạng thái hạn mức',
    `start_date` DATE DEFAULT NULL COMMENT 'Ngày bắt đầu hiệu lực',
    `end_date` DATE DEFAULT NULL COMMENT 'Ngày kết thúc',
    `interest_rate` DECIMAL(5,2) DEFAULT NULL COMMENT 'Lãi suất (%/năm)',
    `collateral_required` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Yêu cầu TSBĐ',
    `collateral_activated` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'TSBĐ đã kích hoạt',
    `created_by_id` INT(11) NOT NULL,
    `approved_by_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_facility_code` (`facility_code`),
    KEY `idx_application` (`application_id`),
    KEY `idx_status` (`status`),
    KEY `idx_facility_type` (`facility_type`),
    CONSTRAINT `fk_facility_application` FOREIGN KEY (`application_id`)
        REFERENCES `credit_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_facility_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_facility_created_by` FOREIGN KEY (`created_by_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_facility_approved_by` FOREIGN KEY (`approved_by_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE

    -- Business rule: amount >= 0 AND disbursed_amount >= 0 AND disbursed_amount <= amount
    -- NOTE: CHECK constraint removed for MySQL compatibility
    -- This rule is enforced in application layer (facility_functions.php)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hạn mức tín dụng';

-- Indexes for performance
CREATE INDEX `idx_facility_dates` ON `facilities` (`start_date`, `end_date`);
CREATE INDEX `idx_collateral_status` ON `facilities` (`collateral_required`, `collateral_activated`);

-- Sample data
INSERT INTO `facilities` (`application_id`, `facility_code`, `facility_type`, `product_id`, `amount`, `disbursed_amount`, `status`, `start_date`, `end_date`, `interest_rate`, `collateral_required`, `collateral_activated`, `created_by_id`, `approved_by_id`) VALUES
(4, 'FAC-2024-0001', 'Ngắn hạn', 1, 500000000.00, 500000000.00, 'Active', '2024-06-01', '2025-06-01', 8.50, TRUE, TRUE, 1, 3),
(1, 'FAC-2024-0002', 'Trung hạn', 1, 700000000.00, 0.00, 'Active', '2024-10-15', '2027-10-15', 9.00, TRUE, TRUE, 1, 2),
(3, 'FAC-2024-0003', 'Dài hạn', 1, 1200000000.00, 0.00, 'Active', '2024-09-01', '2029-09-01', 9.50, TRUE, TRUE, 1, 3),
(6, 'FAC-2024-0004', 'Ngắn hạn - Vốn lưu động', 2, 2000000000.00, 0.00, 'Pending', NULL, NULL, 7.80, TRUE, FALSE, 1, NULL),
(7, 'FAC-2024-0005', 'Ngắn hạn - Vốn lưu động', 2, 5000000000.00, 0.00, 'Pending', NULL, NULL, 7.50, TRUE, FALSE, 1, NULL),
(11, 'FAC-2024-0006', 'Ngắn hạn - Cầm cố', 3, 150000000.00, 0.00, 'Pending', NULL, NULL, 6.50, TRUE, FALSE, 1, NULL);
