-- =====================================================
-- DATABASE FIX - Phase 2 Missing Tables
-- Date: 2025-10-30
-- Purpose: Add tables required by functions in functions.php
-- =====================================================

USE los_db;

-- =====================================================
-- 1. APPLICATION_HISTORY TABLE
-- Used by: add_history(), get_application_history()
-- Purpose: Track all actions/changes on credit applications
-- =====================================================

CREATE TABLE IF NOT EXISTS application_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'Action type: Khởi tạo, Hoàn tất pháp lý, etc.',
    comment TEXT COMMENT 'Detailed comment about the action',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign keys
    CONSTRAINT fk_app_history_application
        FOREIGN KEY (application_id) REFERENCES credit_applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_app_history_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,

    -- Indexes for performance
    INDEX idx_application_id (application_id),
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail for credit application changes';

-- =====================================================
-- 2. CUSTOMER_CREDIT_RATINGS TABLE
-- Used by: get_credit_ratings_for_customer()
-- Purpose: Store credit rating history for customers
-- =====================================================

CREATE TABLE IF NOT EXISTS customer_credit_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    rating_date DATE NOT NULL COMMENT 'Date of rating assessment',
    credit_score INT COMMENT 'Numerical credit score (0-1000)',
    rating_grade VARCHAR(10) COMMENT 'Rating grade: AAA, AA, A, BBB, BB, B, C, D',
    rating_agency VARCHAR(100) COMMENT 'Internal or external rating agency',
    assessment_notes TEXT COMMENT 'Notes from credit assessment',
    assessed_by_id INT COMMENT 'User who performed assessment',
    validity_period_months INT DEFAULT 12 COMMENT 'How long this rating is valid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign keys
    CONSTRAINT fk_credit_rating_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT fk_credit_rating_assessor
        FOREIGN KEY (assessed_by_id) REFERENCES users(id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_customer_rating (customer_id, rating_date DESC),
    INDEX idx_rating_date (rating_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer credit rating history';

-- =====================================================
-- 3. CUSTOMER_RELATED_PARTIES TABLE
-- Used by: get_related_parties_for_customer()
-- Purpose: Track relationships between customers (parent companies, subsidiaries, etc.)
-- =====================================================

CREATE TABLE IF NOT EXISTS customer_related_parties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL COMMENT 'Primary customer ID',
    related_customer_id INT NOT NULL COMMENT 'Related customer ID',
    relationship_type VARCHAR(50) NOT NULL COMMENT 'Type: Parent Company, Subsidiary, Affiliate, Guarantor, etc.',
    relationship_details TEXT COMMENT 'Additional details about the relationship',
    ownership_percentage DECIMAL(5,2) COMMENT 'Ownership % if applicable',
    effective_date DATE COMMENT 'When relationship started',
    end_date DATE COMMENT 'When relationship ended (NULL if active)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign keys
    CONSTRAINT fk_related_party_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT fk_related_party_related
        FOREIGN KEY (related_customer_id) REFERENCES customers(id) ON DELETE CASCADE,

    -- Prevent duplicate relationships
    UNIQUE KEY uk_customer_relationship (customer_id, related_customer_id, relationship_type),

    -- Indexes
    INDEX idx_customer_id (customer_id),
    INDEX idx_related_customer_id (related_customer_id),
    INDEX idx_relationship_type (relationship_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer relationship mapping (parent/subsidiary/guarantor)';

-- =====================================================
-- 4. APPLICATION_REPAYMENT_SOURCES TABLE
-- Used by: get_repayment_sources_for_app()
-- Purpose: Track expected sources of loan repayment
-- =====================================================

CREATE TABLE IF NOT EXISTS application_repayment_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    source_type VARCHAR(50) NOT NULL COMMENT 'Type: Business Revenue, Salary, Asset Sale, Investment Return, etc.',
    source_description TEXT NOT NULL COMMENT 'Detailed description of repayment source',
    estimated_monthly_amount DECIMAL(15,2) COMMENT 'Estimated monthly income from this source',
    percentage_of_total INT COMMENT 'What % of total repayment comes from this source',
    verification_status VARCHAR(50) DEFAULT 'Chưa xác minh' COMMENT 'Chưa xác minh, Đã xác minh, Đã từ chối',
    verified_by_id INT COMMENT 'User who verified this source',
    verification_notes TEXT COMMENT 'Notes from verification process',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign keys
    CONSTRAINT fk_repay_source_application
        FOREIGN KEY (application_id) REFERENCES credit_applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_repay_source_verifier
        FOREIGN KEY (verified_by_id) REFERENCES users(id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_application_id (application_id),
    INDEX idx_source_type (source_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Expected sources of loan repayment for each application';

-- =====================================================
-- SEED DATA - Add some sample records for existing applications
-- =====================================================

-- Add application history for existing applications (retroactive)
INSERT INTO application_history (application_id, user_id, action, comment)
SELECT
    id as application_id,
    created_by_id as user_id,
    'Khởi tạo' as action,
    'Hồ sơ được tạo mới (dữ liệu demo - retroactive)' as comment
FROM credit_applications
WHERE id <= 10
LIMIT 10;

-- Note: customer_credit_ratings and customer_related_parties are optional
-- They will be populated as features are used

-- Add sample repayment sources for a few applications
INSERT INTO application_repayment_sources
    (application_id, source_type, source_description, estimated_monthly_amount, percentage_of_total, verification_status)
SELECT
    id,
    'Doanh thu kinh doanh' as source_type,
    'Thu nhập từ hoạt động kinh doanh chính' as source_description,
    amount * 0.05 as estimated_monthly_amount,
    100 as percentage_of_total,
    'Chưa xác minh' as verification_status
FROM credit_applications
WHERE id <= 5
LIMIT 5;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check tables were created
SELECT
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'los_db'
  AND TABLE_NAME IN (
    'application_history',
    'customer_credit_ratings',
    'customer_related_parties',
    'application_repayment_sources'
);

-- =====================================================
-- SUCCESS MESSAGE
-- =====================================================
SELECT '✅ Phase 2 database tables created successfully!' as Status;
