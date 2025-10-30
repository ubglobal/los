# AUDIT PLAN - LOS v3.0 System-wide Code Audit & Bug Fixing

**Ngày bắt đầu:** 2025-10-30
**Mục tiêu:** Kiểm toán toàn diện và sửa lỗi từng file để đảm bảo hệ thống chạy hoàn hảo

---

## 📋 TỔNG QUAN DỰ ÁN

### Cấu trúc Files (39 PHP files)

**Core System (7 files) - PRIORITY: CRITICAL**
- ✅ config/db.php - Database connection
- ⬜ config/session.php - Session management
- ⬜ config/csrf.php - CSRF protection
- ⬜ config/rate_limit.php - Rate limiting
- ⬜ includes/functions.php - Core functions
- ⬜ includes/security_init.php - Security initialization
- ⬜ includes/header.php - Main header

**Authentication & Authorization (4 files) - PRIORITY: CRITICAL**
- ⬜ login.php - Login page
- ⬜ logout.php - Logout handler
- ⬜ index.php - Dashboard
- ⬜ includes/permission_functions.php - Permission checks

**Application Management Module (3 files) - PRIORITY: HIGH**
- ⬜ create_application.php - Create new application
- ⬜ application_detail.php - View/edit application
- ⬜ process_action.php - Process application actions

**Disbursement Module (4 files) - PRIORITY: HIGH**
- ⬜ disbursement_create.php - Create disbursement
- ⬜ disbursement_detail.php - View/edit disbursement
- ⬜ disbursement_action.php - Process disbursement actions
- ⬜ includes/disbursement_functions.php - Disbursement helper functions

**Facility Module (1 file) - PRIORITY: HIGH**
- ⬜ includes/facility_functions.php - Facility management functions

**Workflow & Business Logic (2 files) - PRIORITY: HIGH**
- ⬜ includes/workflow_engine.php - Workflow automation
- ⬜ includes/exception_escalation_functions.php - Exception handling

**Admin Module (11 files) - PRIORITY: MEDIUM**
- ⬜ admin/index.php - Admin dashboard
- ⬜ admin/manage_users.php - User management
- ⬜ admin/manage_customers.php - Customer management
- ⬜ admin/manage_products.php - Product management
- ⬜ admin/manage_collaterals.php - Collateral type management
- ⬜ admin/manage_document_definitions.php - Document definition management
- ⬜ admin/customer_detail.php - Customer detail view
- ⬜ admin/includes/admin_init.php - Admin initialization
- ⬜ admin/includes/header.php - Admin header
- ⬜ admin/includes/footer.php - Admin footer
- ⬜ admin/debug_admin.php - Debug tool (can be removed)

**Reports & Analytics (1 file) - PRIORITY: MEDIUM**
- ⬜ reports.php - Reporting functionality

**Utility & Setup (5 files) - PRIORITY: LOW**
- ✅ install.php - Installation wizard
- ⬜ includes/footer.php - Main footer
- ⬜ uploads/index.php - Upload directory protection
- ⬜ comprehensive_audit.php - Schema audit tool
- ⬜ validate_schema.php - Schema validator

**Temporary/Debug Files (2 files) - TO REMOVE**
- ⬜ admin/index_simple.php - Simple admin index (duplicate)
- ⬜ generate_hash.php - Password hash generator (utility)

---

## 🎯 GIAI ĐOẠN 1: PHÂN TÍCH VÀ LẬP KẾ HOẠCH (HOÀN THÀNH)

### ✅ Đã hoàn thành:
- [x] Quét cấu trúc dự án
- [x] Phân loại files theo module
- [x] Xác định độ ưu tiên

### 🔄 Đang làm:
- [ ] Tạo system_tester.php - Công cụ test tự động

---

## 🔧 GIAI ĐOẠN 2: AUDIT CORE & FOUNDATION (30-45 phút)

**Mục tiêu:** Đảm bảo nền tảng hệ thống hoạt động ổn định

### 2.1 Database & Configuration (15 phút)
**Files kiểm tra:**
- [ ] config/db.php
  - Kiểm tra: Connection handling, error reporting, charset
  - Test: Connection success/failure scenarios

- [ ] config/session.php
  - Kiểm tra: Session security, timeout, cookie settings
  - Test: Session persistence, timeout behavior

- [ ] database.sql
  - Kiểm tra: Foreign keys, indexes, constraints
  - Chạy: comprehensive_audit.php để verify schema

**Checklist:**
- [ ] Database connection works properly
- [ ] Session starts correctly
- [ ] All foreign keys valid
- [ ] Indexes optimized

### 2.2 Authentication & Security (15 phút)
**Files kiểm tra:**
- [ ] login.php
  - Kiểm tra: SQL injection protection, password verification, rate limiting
  - Test: Valid login, invalid login, brute force protection

- [ ] logout.php
  - Kiểm tra: Session cleanup, redirect
  - Test: Logout clears session properly

- [ ] includes/security_init.php
  - Kiểm tra: Security headers, input sanitization
  - Test: XSS prevention, CSRF token generation

- [ ] config/csrf.php
  - Kiểm tra: Token generation, validation
  - Test: CSRF protection works

- [ ] config/rate_limit.php
  - Kiểm tra: Rate limiting logic
  - Test: Blocks after threshold

**Checklist:**
- [ ] Login works with correct credentials
- [ ] Login fails with wrong credentials
- [ ] Rate limiting prevents brute force
- [ ] CSRF tokens validate correctly
- [ ] Session hijacking prevented
- [ ] Logout clears all session data

### 2.3 Core Functions & Permissions (15 phút)
**Files kiểm tra:**
- [ ] includes/functions.php
  - Kiểm tra: All helper functions, SQL queries, user fetching
  - Test: Each function independently

- [ ] includes/permission_functions.php
  - Kiểm tra: Permission checks for each role
  - Test: Access control for each role

**Checklist:**
- [ ] User fetching functions work
- [ ] Permission checks accurate for all roles
- [ ] Error handling in place

---

## 📱 GIAI ĐOẠN 3: AUDIT TỪNG MODULE THEO WORKFLOW (1-2 giờ)

### 3.1 Customer Management Module (15 phút)
**Files kiểm tra:**
- [ ] admin/manage_customers.php
  - Test: List, search, add, edit, delete customer

- [ ] admin/customer_detail.php
  - Test: View customer details, related applications

**Test scenarios:**
- [ ] Create new individual customer
- [ ] Create new company customer
- [ ] Edit customer information
- [ ] Search customer by name/code/phone
- [ ] View customer applications
- [ ] Cannot delete customer with active applications

**SQL Queries to verify:**
- [ ] All INSERT statements include required fields
- [ ] All SELECT statements match table schema
- [ ] All UPDATE statements include WHERE clause

### 3.2 Product Management Module (10 phút)
**Files kiểm tra:**
- [ ] admin/manage_products.php
  - Test: List, add, edit, delete, activate/deactivate

**Test scenarios:**
- [ ] Create new product
- [ ] Edit product description
- [ ] Activate/deactivate product
- [ ] Cannot delete product used in applications

### 3.3 Credit Application Module (30 phút)
**Files kiểm tra:**
- [ ] create_application.php
  - Test: Form validation, data submission, draft save

- [ ] application_detail.php
  - Test: View, edit, status changes, workflow progression

- [ ] process_action.php
  - Test: All actions (submit, review, approve, reject, etc.)

**Test scenarios:**
- [ ] Create draft application
- [ ] Submit application (draft → processing)
- [ ] Assign to reviewer
- [ ] Add documents
- [ ] Add collaterals
- [ ] Review and approve
- [ ] Review and reject (with reason)
- [ ] Request more information
- [ ] Cancel application

**Workflow tests:**
- [ ] CVQHKH can create and submit
- [ ] CVTĐ can review
- [ ] CPD can approve (≤5B)
- [ ] GDK can approve (>5B)
- [ ] Status transitions correct
- [ ] Email notifications sent (if enabled)

### 3.4 Facility Management Module (15 phút)
**Files kiểm tra:**
- [ ] includes/facility_functions.php
  - Test: Create facility, update disbursed amount, status changes

**Test scenarios:**
- [ ] Create facility after approval
- [ ] Calculate available amount correctly
- [ ] Update disbursed amount after disbursement
- [ ] Prevent over-disbursement
- [ ] Facility expiry handling

### 3.5 Disbursement Module (30 phút)
**Files kiểm tra:**
- [ ] disbursement_create.php
  - Test: Form validation, beneficiary types, conditions

- [ ] disbursement_detail.php
  - Test: View, edit, status progression

- [ ] disbursement_action.php
  - Test: All actions (check conditions, approve, execute, reject)

- [ ] includes/disbursement_functions.php
  - Test: Helper functions, validations

**Test scenarios:**
- [ ] Create disbursement request
- [ ] Check collateral activation
- [ ] Check legal completion
- [ ] Check available facility amount
- [ ] Kiểm soát approves conditions
- [ ] CPD/GDK approves disbursement
- [ ] Thủ quỹ executes disbursement
- [ ] Update facility disbursed amount
- [ ] Multiple disbursements for same facility
- [ ] Prevent over-disbursement

**Workflow tests:**
- [ ] Correct role can perform each action
- [ ] Status transitions validated
- [ ] SLA tracking works

### 3.6 Document & Collateral Management (15 phút)
**Files kiểm tra:**
- [ ] admin/manage_document_definitions.php
  - Test: CRUD operations

- [ ] admin/manage_collaterals.php
  - Test: CRUD operations

**Test scenarios:**
- [ ] Upload document to application
- [ ] Version control for documents
- [ ] Add collateral to application
- [ ] Warehouse in collateral
- [ ] Activate collateral
- [ ] Calculate collateral value

### 3.7 User Management Module (10 phút)
**Files kiểm tra:**
- [ ] admin/manage_users.php
  - Test: Create, edit, activate/deactivate users

**Test scenarios:**
- [ ] Create user with each role
- [ ] Set approval limit for CPD/GDK
- [ ] Edit user information
- [ ] Deactivate user
- [ ] Cannot delete user with activity

### 3.8 Workflow Engine & Exception Handling (15 phút)
**Files kiểm tra:**
- [ ] includes/workflow_engine.php
  - Test: Auto-assignment, escalation, notifications

- [ ] includes/exception_escalation_functions.php
  - Test: SLA tracking, escalation rules

**Test scenarios:**
- [ ] Auto-assign based on workload
- [ ] SLA warning triggers
- [ ] SLA overdue detection
- [ ] Escalation to supervisor
- [ ] Exception handling

---

## 🔗 GIAI ĐOẠN 4: INTEGRATION TESTING (30 phút)

### 4.1 End-to-End Workflow Test (20 phút)
**Complete workflow cho từng loại sản phẩm:**

- [ ] **Vốn lưu động ngắn hạn (Individual customer)**
  1. CVQHKH creates application
  2. Uploads documents
  3. Adds collateral
  4. Submits for review
  5. CVTĐ reviews and approves
  6. CPD approves
  7. Facility created
  8. Kiểm soát warehouses collateral
  9. Kiểm soát activates collateral
  10. Create disbursement
  11. Kiểm soát checks conditions
  12. CPD approves disbursement
  13. Thủ quỹ executes

- [ ] **Đầu tư dài hạn (Company customer, >5B)**
  1. Same workflow but GDK approval required
  2. Multiple disbursements

- [ ] **Thấu chi tài khoản**
  1. Approval workflow
  2. Multiple withdrawals within limit

### 4.2 Permission & Access Control Test (10 phút)
- [ ] Each role can only access allowed pages
- [ ] Each role can only perform allowed actions
- [ ] Admin can access admin panel
- [ ] Non-admin cannot access admin panel

### 4.3 Data Integrity Test
- [ ] Foreign key constraints work
- [ ] Cannot delete referenced records
- [ ] Cascade deletes work correctly
- [ ] Transaction rollback on errors

---

## ⚡ GIAI ĐOẠN 5: PERFORMANCE & POLISH (30 phút)

### 5.1 Performance Optimization (15 phút)
- [ ] Add indexes for frequently queried columns
- [ ] Optimize N+1 query problems
- [ ] Add pagination where needed
- [ ] Cache static data

### 5.2 UI/UX Improvements (10 phút)
- [ ] Loading indicators
- [ ] Error messages clear and helpful
- [ ] Success messages consistent
- [ ] Form validation messages
- [ ] Responsive design check

### 5.3 Security Hardening (5 phút)
- [ ] Remove debug files
- [ ] Remove commented code
- [ ] Verify all inputs sanitized
- [ ] Check all outputs escaped
- [ ] Review file upload security

---

## 📊 THEO DÕI TIẾN ĐỘ

### Tiến độ tổng thể:
- Giai đoạn 1: ✅ HOÀN THÀNH
- Giai đoạn 2: ⬜ CHƯA BẮT ĐẦU (0/3 sections)
- Giai đoạn 3: ⬜ CHƯA BẮT ĐẦU (0/8 modules)
- Giai đoạn 4: ⬜ CHƯA BẮT ĐẦU (0/3 tests)
- Giai đoạn 5: ⬜ CHƯA BẮT ĐẦU (0/3 tasks)

### Files đã audit: 0/39 (0%)

---

## 🐛 BUG TRACKING

### Critical Bugs (Blocker):
*Sẽ cập nhật khi phát hiện*

### High Priority Bugs:
*Sẽ cập nhật khi phát hiện*

### Medium Priority Bugs:
*Sẽ cập nhật khi phát hiện*

### Low Priority Issues:
*Sẽ cập nhật khi phát hiện*

---

## 📝 GHI CHÚ

**Nguyên tắc audit:**
1. Đọc code kỹ từng dòng
2. Kiểm tra SQL injection, XSS
3. Verify business logic
4. Test mọi edge cases
5. Document tất cả bugs tìm thấy
6. Fix và test lại

**Công cụ hỗ trợ:**
- comprehensive_audit.php - Schema verification
- system_tester.php - Automated testing (sẽ tạo)
- Debug tools - Enable khi cần

**Cập nhật lần cuối:** 2025-10-30
