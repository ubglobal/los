-- Migration 013: Create login_attempts table
-- Description: Rate limiting và tracking failed login attempts (Security feature from v2.0)
-- Author: Claude AI
-- Date: 2025-10-30

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL COMMENT 'Username cố gắng đăng nhập',
    `ip_address` VARCHAR(50) NOT NULL COMMENT 'IP address',
    `attempt_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian thử',
    `user_agent` VARCHAR(255) DEFAULT NULL COMMENT 'Browser/User agent',
    `success` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Thành công hay thất bại',
    `failure_reason` VARCHAR(255) DEFAULT NULL COMMENT 'Lý do thất bại: wrong_password, account_locked, etc.',

    PRIMARY KEY (`id`),
    KEY `idx_username` (`username`),
    KEY `idx_ip_address` (`ip_address`),
    KEY `idx_attempt_time` (`attempt_time`),
    KEY `idx_success` (`success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Login attempts tracking for rate limiting';

-- Create index for rate limit checking (username or IP within timeframe)
CREATE INDEX `idx_rate_limit` ON `login_attempts` (`username`, `ip_address`, `attempt_time`);

-- Sample data (some failed attempts)
INSERT INTO `login_attempts` (`username`, `ip_address`, `attempt_time`, `success`, `failure_reason`) VALUES
('qhkh.an.nguyen', '192.168.1.100', DATE_SUB(NOW(), INTERVAL 2 HOUR), TRUE, NULL),
('invalid_user', '192.168.1.200', DATE_SUB(NOW(), INTERVAL 1 HOUR), FALSE, 'wrong_username'),
('qhkh.an.nguyen', '192.168.1.200', DATE_SUB(NOW(), INTERVAL 1 HOUR), FALSE, 'wrong_password'),
('qhkh.an.nguyen', '192.168.1.200', DATE_SUB(NOW(), INTERVAL 59 MINUTE), FALSE, 'wrong_password'),
('admin', '192.168.1.100', DATE_SUB(NOW(), INTERVAL 30 MINUTE), TRUE, NULL);

-- Cleanup policy: delete attempts older than 24 hours
-- (Should be run as a scheduled job)
-- DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR);
