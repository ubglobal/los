-- Migration 009: Create roles and permissions tables
-- Description: Hệ thống phân quyền nâng cao (Access/Input/Update/Delete/Approve)
-- Author: Claude AI
-- Date: 2025-10-30

-- ============================================
-- Table: roles (Nhóm quyền)
-- ============================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `role_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã role (CVQHKH, CVTĐ, CPD, GDK, Admin)',
    `role_name` VARCHAR(100) NOT NULL COMMENT 'Tên hiển thị',
    `description` TEXT DEFAULT NULL,
    `is_system_role` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Role hệ thống (không được xóa)',
    `approval_limit` DECIMAL(20,2) DEFAULT NULL COMMENT 'Hạn mức phê duyệt mặc định',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_role_code` (`role_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhóm quyền';

-- Insert system roles
INSERT INTO `roles` (`role_code`, `role_name`, `description`, `is_system_role`, `approval_limit`) VALUES
('CVQHKH', 'Chuyên viên Quan hệ Khách hàng', 'Relationship Manager - Khởi tạo và theo dõi hồ sơ', TRUE, NULL),
('CVTĐ', 'Chuyên viên Thẩm định', 'Credit Analyst - Thẩm định và đánh giá rủi ro', TRUE, NULL),
('CPD', 'Cấp Phê Duyệt', 'Approver - Phê duyệt hồ sơ dưới 5 tỷ', TRUE, 5000000000.00),
('GDK', 'Giám đốc Khối', 'Director - Phê duyệt hồ sơ trên 5 tỷ và xử lý escalation', TRUE, 20000000000.00),
('Admin', 'Quản trị viên', 'Administrator - Quản lý hệ thống', TRUE, NULL),
('Kiểm soát', 'Kiểm soát giải ngân', 'Disbursement Controller - Kiểm tra điều kiện giải ngân', TRUE, NULL),
('Thủ quỹ', 'Thủ quỹ', 'Cashier - Thực hiện giải ngân', TRUE, NULL);

-- ============================================
-- Table: permissions (Quyền)
-- ============================================
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `module` VARCHAR(50) NOT NULL COMMENT 'Module: Credit, Disbursement, Customer, Collateral, Document, Report, Admin',
    `permission_code` VARCHAR(100) NOT NULL COMMENT 'Mã quyền: credit.access, credit.input, credit.approve, ...',
    `permission_type` ENUM('Access', 'Input', 'Update', 'Delete', 'Approve', 'View', 'Export') NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_permission_code` (`permission_code`),
    KEY `idx_module` (`module`),
    KEY `idx_permission_type` (`permission_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quyền hệ thống';

-- Insert permissions
INSERT INTO `permissions` (`module`, `permission_code`, `permission_type`, `description`) VALUES
-- Credit Application permissions
('Credit', 'credit.access', 'Access', 'Truy cập module hồ sơ tín dụng'),
('Credit', 'credit.input', 'Input', 'Tạo mới hồ sơ tín dụng'),
('Credit', 'credit.update', 'Update', 'Chỉnh sửa hồ sơ tín dụng'),
('Credit', 'credit.delete', 'Delete', 'Xóa hồ sơ tín dụng'),
('Credit', 'credit.approve', 'Approve', 'Phê duyệt hồ sơ tín dụng'),
('Credit', 'credit.view_all', 'View', 'Xem tất cả hồ sơ (không phụ thuộc chi nhánh)'),
('Credit', 'credit.export', 'Export', 'Xuất báo cáo hồ sơ tín dụng'),

-- Disbursement permissions
('Disbursement', 'disbursement.access', 'Access', 'Truy cập module giải ngân'),
('Disbursement', 'disbursement.input', 'Input', 'Tạo yêu cầu giải ngân'),
('Disbursement', 'disbursement.update', 'Update', 'Chỉnh sửa yêu cầu giải ngân'),
('Disbursement', 'disbursement.delete', 'Delete', 'Xóa yêu cầu giải ngân'),
('Disbursement', 'disbursement.approve', 'Approve', 'Phê duyệt giải ngân'),
('Disbursement', 'disbursement.check_conditions', 'Approve', 'Kiểm tra điều kiện giải ngân'),
('Disbursement', 'disbursement.view_all', 'View', 'Xem tất cả giải ngân'),

-- Customer permissions
('Customer', 'customer.access', 'Access', 'Truy cập danh sách khách hàng'),
('Customer', 'customer.input', 'Input', 'Tạo khách hàng mới'),
('Customer', 'customer.update', 'Update', 'Chỉnh sửa thông tin khách hàng'),
('Customer', 'customer.delete', 'Delete', 'Xóa khách hàng'),
('Customer', 'customer.view_all', 'View', 'Xem khách hàng của tất cả chi nhánh'),

-- Collateral permissions
('Collateral', 'collateral.access', 'Access', 'Truy cập quản lý TSBĐ'),
('Collateral', 'collateral.input', 'Input', 'Thêm TSBĐ mới'),
('Collateral', 'collateral.update', 'Update', 'Chỉnh sửa TSBĐ'),
('Collateral', 'collateral.delete', 'Delete', 'Xóa TSBĐ'),
('Collateral', 'collateral.warehouse', 'Approve', 'Quản lý kho TSBĐ (nhập/xuất)'),

-- Document permissions
('Document', 'document.access', 'Access', 'Truy cập tài liệu'),
('Document', 'document.upload', 'Input', 'Upload tài liệu'),
('Document', 'document.update', 'Update', 'Cập nhật tài liệu'),
('Document', 'document.delete', 'Delete', 'Xóa tài liệu'),
('Document', 'document.view_history', 'View', 'Xem lịch sử tài liệu'),

-- Facility permissions
('Facility', 'facility.access', 'Access', 'Truy cập quản lý hạn mức'),
('Facility', 'facility.input', 'Input', 'Tạo hạn mức mới'),
('Facility', 'facility.update', 'Update', 'Chỉnh sửa hạn mức'),
('Facility', 'facility.delete', 'Delete', 'Xóa hạn mức'),
('Facility', 'facility.activate', 'Approve', 'Kích hoạt hạn mức'),

-- Report permissions
('Report', 'report.access', 'Access', 'Truy cập báo cáo'),
('Report', 'report.credit', 'View', 'Xem báo cáo tín dụng'),
('Report', 'report.disbursement', 'View', 'Xem báo cáo giải ngân'),
('Report', 'report.export', 'Export', 'Xuất báo cáo'),

-- Admin permissions
('Admin', 'admin.access', 'Access', 'Truy cập module quản trị'),
('Admin', 'admin.user_management', 'Approve', 'Quản lý người dùng'),
('Admin', 'admin.role_management', 'Approve', 'Quản lý phân quyền'),
('Admin', 'admin.system_config', 'Approve', 'Cấu hình hệ thống'),
('Admin', 'admin.audit_log', 'View', 'Xem audit log');

-- ============================================
-- Table: role_permissions (Gán quyền cho role)
-- ============================================
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `role_id` INT(11) NOT NULL,
    `permission_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
    KEY `idx_role` (`role_id`),
    KEY `idx_permission` (`permission_id`),

    CONSTRAINT `fk_role_perm_role` FOREIGN KEY (`role_id`)
        REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_role_perm_permission` FOREIGN KEY (`permission_id`)
        REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gán quyền cho role';

-- Assign permissions to roles
-- CVQHKH (RM)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM permissions WHERE permission_code IN (
    'credit.access', 'credit.input', 'credit.update',
    'disbursement.access', 'disbursement.input',
    'customer.access', 'customer.input', 'customer.update',
    'collateral.access', 'collateral.input',
    'document.access', 'document.upload', 'document.update',
    'facility.access',
    'report.access', 'report.credit', 'report.disbursement'
);

-- CVTĐ (Credit Analyst)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM permissions WHERE permission_code IN (
    'credit.access', 'credit.update',
    'disbursement.access', 'disbursement.check_conditions',
    'customer.access',
    'collateral.access',
    'document.access', 'document.view_history',
    'facility.access',
    'report.access', 'report.credit'
);

-- CPD (Approver)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM permissions WHERE permission_code IN (
    'credit.access', 'credit.approve',
    'disbursement.access', 'disbursement.approve',
    'customer.access',
    'collateral.access',
    'document.access',
    'facility.access', 'facility.activate',
    'report.access', 'report.credit', 'report.disbursement'
);

-- GDK (Director)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM permissions WHERE permission_code IN (
    'credit.access', 'credit.approve', 'credit.view_all',
    'disbursement.access', 'disbursement.approve', 'disbursement.view_all',
    'customer.access', 'customer.view_all',
    'collateral.access',
    'document.access',
    'facility.access', 'facility.activate',
    'report.access', 'report.credit', 'report.disbursement', 'report.export'
);

-- Admin
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM permissions;

-- Kiểm soát
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 6, id FROM permissions WHERE permission_code IN (
    'disbursement.access', 'disbursement.update', 'disbursement.check_conditions',
    'credit.access',
    'customer.access',
    'collateral.access',
    'document.access',
    'facility.access'
);

-- Thủ quỹ
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 7, id FROM permissions WHERE permission_code IN (
    'disbursement.access', 'disbursement.approve',
    'credit.access',
    'document.access',
    'facility.access'
);

-- ============================================
-- Table: user_branch_access (Quyền truy cập theo chi nhánh)
-- ============================================
CREATE TABLE IF NOT EXISTS `user_branch_access` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `branch` VARCHAR(100) NOT NULL COMMENT 'Chi nhánh được phép truy cập',
    `can_access_customers` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Truy cập khách hàng của chi nhánh',
    `can_access_collaterals` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Truy cập TSBĐ của chi nhánh',
    `can_access_facilities` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Truy cập hạn mức của chi nhánh',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_branch` (`user_id`, `branch`),
    KEY `idx_user` (`user_id`),
    KEY `idx_branch` (`branch`),

    CONSTRAINT `fk_user_branch_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quyền truy cập theo chi nhánh';

-- Assign branch access to users (based on their home branch + cross-branch if needed)
INSERT INTO `user_branch_access` (`user_id`, `branch`, `can_access_customers`, `can_access_collaterals`, `can_access_facilities`) VALUES
(1, 'CN An Giang', TRUE, TRUE, TRUE),  -- RM An Giang có quyền truy cập chi nhánh của mình
(2, 'Hội sở', TRUE, TRUE, TRUE),       -- CVTĐ Hội sở
(3, 'Hội sở', TRUE, TRUE, TRUE),       -- CPD Hội sở
(4, 'Hội sở', TRUE, TRUE, TRUE),       -- Admin có tất cả
(5, 'Hội sở', TRUE, TRUE, TRUE);       -- GDK Hội sở

-- GDK và Admin có quyền truy cập cross-branch
INSERT INTO `user_branch_access` (`user_id`, `branch`, `can_access_customers`, `can_access_collaterals`, `can_access_facilities`)
SELECT 4, DISTINCT branch, TRUE, TRUE, TRUE FROM users WHERE branch != 'Hội sở';

INSERT INTO `user_branch_access` (`user_id`, `branch`, `can_access_customers`, `can_access_collaterals`, `can_access_facilities`)
SELECT 5, DISTINCT branch, TRUE, TRUE, TRUE FROM users WHERE branch != 'Hội sở';
