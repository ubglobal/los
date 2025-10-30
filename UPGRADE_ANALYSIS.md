# Phân tích Nâng cấp Hệ thống LOS v2.0 → v3.0

**Ngày:** 30/10/2025
**Phiên bản hiện tại:** v2.0 (Security-Hardened)
**Phiên bản mục tiêu:** v3.0 (Full LOS BA Specification)

---

## 📊 TỔNG QUAN

### Hệ thống hiện tại (v2.0)
- ✅ Quy trình phê duyệt tín dụng cơ bản (4 roles: CVQHKH → CVTĐ → CPD → GDK)
- ✅ Quản lý khách hàng (cá nhân & doanh nghiệp)
- ✅ Quản lý TSBĐ (collaterals)
- ✅ Upload/quản lý tài liệu
- ✅ Thao tác workflow cơ bản (Save, Approve, Reject, Return)
- ✅ Security score 95% (CSRF, IDOR, Session, Rate Limit)
- ✅ Admin area hoàn chỉnh

### Yêu cầu nâng cấp (v3.0 - theo BA)
- ➕ **Quy trình Giải ngân** (hoàn toàn mới)
- ➕ **Hệ thống Hạn mức (Facilities)**
- ➕ **Ngoại lệ & Khiếu nại (Exceptions & Escalation)**
- ➕ **Quản lý HSTD sau phê duyệt**
- ➕ **Trạng thái nâng cao** ("Đã có hiệu lực", TSBĐ đã kích hoạt)
- ➕ **Thao tác workflow nâng cao** (Discard, Escalate, Close, Request Info nâng cao)
- ➕ **Phân quyền chi tiết** (Access/Input/Update/Delete/Approve + Access to resources)
- ➕ **Audit log chi tiết** với lịch sử đầy đủ
- ➕ **UI/UX chuẩn LOS** (3-zone layout, status bar)
- ➕ **QR Code cho tài liệu** (optional)
- ➕ **SLA & Cảnh báo** (optional)

---

## 🔍 GAP ANALYSIS CHI TIẾT

### 1. DATABASE SCHEMA

#### ✅ Đã có (v2.0)
```sql
users                           -- Đầy đủ với role, branch, approval_limit
customers                       -- Cá nhân & doanh nghiệp
products                        -- Sản phẩm tín dụng
collateral_types                -- Loại TSBĐ
credit_applications             -- HSTD với status, stage
customer_credit_ratings         -- Xếp hạng tín dụng
customer_related_parties        -- Người liên quan
application_collaterals         -- TSBĐ của HSTD
application_documents           -- Tài liệu
application_repayment_sources   -- Nguồn trả nợ
application_history             -- Lịch sử thao tác
```

#### ❌ Cần bổ sung (v3.0)

**1. Facilities (Hạn mức) - CRITICAL**
```sql
CREATE TABLE facilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    facility_code VARCHAR(50) UNIQUE NOT NULL,
    facility_type VARCHAR(100) NOT NULL,          -- Ngắn hạn/Dài hạn/LC/BL
    amount DECIMAL(20,2) NOT NULL,
    disbursed_amount DECIMAL(20,2) DEFAULT 0,
    available_amount DECIMAL(20,2),               -- amount - disbursed_amount
    status ENUM('Pending', 'Active', 'Inactive', 'Closed') DEFAULT 'Pending',
    start_date DATE,
    end_date DATE,
    collateral_required BOOLEAN DEFAULT FALSE,
    collateral_activated BOOLEAN DEFAULT FALSE,   -- Quan trọng cho giải ngân
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES credit_applications(id)
);
```

**2. Disbursements (Giải ngân) - CRITICAL**
```sql
CREATE TABLE disbursements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    disbursement_code VARCHAR(50) UNIQUE NOT NULL,
    application_id INT NOT NULL,
    facility_id INT NOT NULL,
    amount DECIMAL(20,2) NOT NULL,
    purpose TEXT,
    beneficiary_account VARCHAR(50),
    beneficiary_name VARCHAR(255),
    status ENUM('Draft', 'Pending', 'Approved', 'Rejected', 'Disbursed') DEFAULT 'Draft',
    stage VARCHAR(100),                            -- Bước workflow
    assigned_to_id INT,
    created_by_id INT NOT NULL,
    approved_by_id INT,
    disbursement_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES credit_applications(id),
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (assigned_to_id) REFERENCES users(id),
    FOREIGN KEY (created_by_id) REFERENCES users(id),
    FOREIGN KEY (approved_by_id) REFERENCES users(id)
);
```

**3. Disbursement Conditions (Điều kiện giải ngân)**
```sql
CREATE TABLE disbursement_conditions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    disbursement_id INT NOT NULL,
    condition_text TEXT NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    is_met BOOLEAN DEFAULT FALSE,
    met_date DATE,
    met_by_id INT,
    notes TEXT,
    FOREIGN KEY (disbursement_id) REFERENCES disbursements(id),
    FOREIGN KEY (met_by_id) REFERENCES users(id)
);
```

**4. Approval Conditions (Điều kiện phê duyệt)**
```sql
CREATE TABLE approval_conditions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    condition_text TEXT NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    allow_exception BOOLEAN DEFAULT FALSE,        -- Cho phép xin ngoại lệ
    is_exception_requested BOOLEAN DEFAULT FALSE,
    exception_reason TEXT,
    exception_approved BOOLEAN DEFAULT FALSE,
    exception_approved_by_id INT,
    exception_approved_date DATE,
    is_met BOOLEAN DEFAULT FALSE,
    met_date DATE,
    FOREIGN KEY (application_id) REFERENCES credit_applications(id),
    FOREIGN KEY (exception_approved_by_id) REFERENCES users(id)
);
```

**5. Escalations (Khiếu nại/Leo thang)**
```sql
CREATE TABLE escalations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT,
    disbursement_id INT,
    escalation_type ENUM('Credit', 'Disbursement') NOT NULL,
    reason TEXT NOT NULL,
    escalated_by_id INT NOT NULL,
    escalated_to_id INT NOT NULL,
    status ENUM('Pending', 'Reviewing', 'Resolved', 'Rejected') DEFAULT 'Pending',
    resolution TEXT,
    resolved_by_id INT,
    resolved_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES credit_applications(id),
    FOREIGN KEY (disbursement_id) REFERENCES disbursements(id),
    FOREIGN KEY (escalated_by_id) REFERENCES users(id),
    FOREIGN KEY (escalated_to_id) REFERENCES users(id),
    FOREIGN KEY (resolved_by_id) REFERENCES users(id)
);
```

**6. Workflow Definitions (Định nghĩa quy trình)**
```sql
CREATE TABLE workflow_steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    workflow_type ENUM('Credit_Approval', 'Disbursement') NOT NULL,
    step_name VARCHAR(100) NOT NULL,
    step_order INT NOT NULL,
    role_required VARCHAR(50) NOT NULL,
    approval_limit DECIMAL(20,2),                  -- Hạn mức phê duyệt của bước
    sla_hours INT,                                 -- SLA tính bằng giờ
    is_active BOOLEAN DEFAULT TRUE
);
```

**7. Disbursement History (Lịch sử giải ngân)**
```sql
CREATE TABLE disbursement_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    disbursement_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,                  -- Save/Next/Request Info/Approve/Reject/Return/Escalate
    comment TEXT,
    from_stage VARCHAR(100),
    to_stage VARCHAR(100),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (disbursement_id) REFERENCES disbursements(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**8. Document History (Lịch sử tài liệu)**
```sql
CREATE TABLE document_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    action ENUM('Upload', 'Update', 'Delete', 'View') NOT NULL,
    old_file_path VARCHAR(255),
    new_file_path VARCHAR(255),
    changed_by_id INT NOT NULL,
    change_reason TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES application_documents(id),
    FOREIGN KEY (changed_by_id) REFERENCES users(id)
);
```

**9. Enhanced Permissions (Phân quyền nâng cao)**
```sql
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module VARCHAR(50) NOT NULL,                   -- Credit, Disbursement, Customer, etc.
    permission_type ENUM('Access', 'Input', 'Update', 'Delete', 'Approve') NOT NULL,
    description TEXT
);

CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id),
    UNIQUE KEY unique_role_permission (role_id, permission_id)
);

CREATE TABLE user_branch_access (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    branch VARCHAR(100) NOT NULL,
    can_access_customers BOOLEAN DEFAULT FALSE,
    can_access_collaterals BOOLEAN DEFAULT FALSE,
    can_access_facilities BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**10. QR Code Documents (Optional)**
```sql
ALTER TABLE application_documents ADD COLUMN (
    document_code VARCHAR(50),                     -- Mã tài liệu
    qr_token VARCHAR(255),                         -- Token từ QR code
    auto_classified BOOLEAN DEFAULT FALSE,         -- Tự động phân loại
    classification_confidence DECIMAL(3,2)         -- Độ tin cậy (0.00-1.00)
);
```

**11. Cập nhật bảng credit_applications**
```sql
ALTER TABLE credit_applications
ADD COLUMN effective_date DATE AFTER updated_at,           -- Ngày có hiệu lực
ADD COLUMN legal_completed BOOLEAN DEFAULT FALSE,          -- Hoàn tất pháp lý
ADD COLUMN sla_due_date DATETIME,                          -- Hạn SLA
ADD COLUMN sla_status ENUM('On Track', 'Warning', 'Overdue') DEFAULT 'On Track',
ADD COLUMN workflow_type VARCHAR(50) DEFAULT 'Credit_Approval';
```

**12. Cập nhật bảng application_collaterals**
```sql
ALTER TABLE application_collaterals
ADD COLUMN warehouse_status ENUM('Not Received', 'In Warehouse', 'Released') DEFAULT 'Not Received',
ADD COLUMN warehouse_date DATE,
ADD COLUMN facility_activated BOOLEAN DEFAULT FALSE,
ADD COLUMN activation_date DATE;
```

---

### 2. BUSINESS LOGIC & WORKFLOW

#### ❌ Cần implement (v3.0)

**A. Quy trình Giải ngân**
- Khởi tạo hồ sơ giải ngân từ HSTD đã phê duyệt
- Kiểm tra điều kiện tiên quyết:
  - HSTD status = "Đã có hiệu lực" (effective_date NOT NULL AND legal_completed = TRUE)
  - Nếu có TSBĐ: warehouse_status = "In Warehouse" AND facility_activated = TRUE
  - Facility có available_amount đủ
- Workflow: Save → Next → Request Info → Approve/Reject/Return → Disbursed
- Cập nhật disbursed_amount và available_amount của facility

**B. Hệ thống Hạn mức (Facilities)**
- Một HSTD có nhiều hạn mức (1-n)
- Mỗi hạn mức có: loại, số tiền, trạng thái, collateral_activated
- Giải ngân phải gắn đúng facility_id
- Kiểm soát available_amount

**C. Ngoại lệ & Khiếu nại**
- Điều kiện phê duyệt: is_mandatory, allow_exception
- RM có thể xin ngoại lệ cho điều kiện allow_exception = TRUE
- Approver có thể "Cho phép ngoại lệ" (exception_approved = TRUE)
- Escalate: khi bị reject, RM có thể khiếu nại lên cấp cao hơn

**D. Quản lý HSTD sau phê duyệt**
- Màn hình riêng: `/post_approval_management.php`
- Chỉ cho phép chỉnh sửa khi status = "Đã phê duyệt" HOẶC "Đã có hiệu lực"
- Có thể cập nhật: facilities, collaterals, repayment_sources, conditions
- Xem lịch sử phê duyệt đầy đủ

**E. Trạng thái nâng cao**
```
Workflow Credit:
Draft → Chờ thẩm định → Chờ phê duyệt → Đã phê duyệt → Đã có hiệu lực
                                              ↓
                                         Đã từ chối

Workflow Disbursement:
Draft → Kiểm tra điều kiện → Chờ phê duyệt → Đã giải ngân
                                  ↓
                             Đã từ chối
```

**F. Thao tác workflow nâng cao**
- Save: Lưu nháp
- Next: Gửi bước tiếp theo
- Request Info: Yêu cầu bổ sung (trả về bước trước)
- Approve: Phê duyệt
- Reject: Từ chối
- Return: Trả lại bước trước (khác Request Info)
- Escalate: Khiếu nại/Leo thang lên cấp cao
- Discard: Hủy bỏ hồ sơ
- Close: Đóng hồ sơ

---

### 3. UI/UX IMPROVEMENTS

#### ❌ Cần redesign (v3.0)

**A. Layout chuẩn LOS (3-zone)**
```
┌─────────────────────────────────────────────────────────┐
│  Header Navigation + User Info                          │
├─────────────────────────────────────────────────────────┤
│  Status Bar (HSTD Code, Status, SLA, Actions)          │
├───────────┬─────────────────────────────────────────────┤
│           │                                             │
│    Tab    │         Content Area                        │
│   List    │    (Form fields, tables, etc.)              │
│           │                                             │
│  - Thông  │                                             │
│    tin KH │                                             │
│  - TSBĐ   │                                             │
│  - Hạn    │                                             │
│    mức    │                                             │
│  - Tài    │                                             │
│    liệu   │                                             │
│  - Điều   │                                             │
│    kiện   │                                             │
│  - Lịch   │                                             │
│    sử     │                                             │
│           │                                             │
└───────────┴─────────────────────────────────────────────┘
```

**B. Workspace / Tạo yêu cầu mới**
- Hiển thị danh sách quy trình theo phân quyền
- 2 quy trình chính:
  1. Quy trình Phê duyệt Tín dụng
  2. Quy trình Giải ngân
- Click để khởi tạo

**C. Action buttons chuẩn**
```
[Save] [Close] [Next] [Request Info] [Discard]
[Return] [Approve] [Reject] [Escalate]
```
Hiển thị theo role và bước workflow

**D. Status indicators**
- Visual status bar với màu sắc:
  - 🟢 Green: Approved, Effective
  - 🟡 Yellow: Pending, In Progress
  - 🔴 Red: Rejected, Overdue
  - 🔵 Blue: Draft
- SLA countdown timer
- Progress bar (workflow steps)

---

### 4. SECURITY & PERMISSIONS

#### ✅ Đã có (v2.0)
- CSRF protection
- Session security
- Rate limiting
- IDOR protection
- Security headers

#### ➕ Cần nâng cấp (v3.0)
- Role-based permissions (Access/Input/Update/Delete/Approve)
- Resource-level permissions (Access to customer/collateral/facility)
- Branch-based access control
- Audit log chi tiết (mọi thao tác)

---

## 📋 IMPLEMENTATION ROADMAP

### Phase 1: Database Foundation (Tuần 1)
- ✅ Tạo migration files cho tất cả tables mới
- ✅ Update existing tables với columns mới
- ✅ Tạo seed data cho testing
- ✅ Test foreign key constraints

### Phase 2: Core Business Logic (Tuần 2-3)
- ✅ Implement Facilities management
- ✅ Implement Disbursement workflow
- ✅ Implement Exceptions & Escalations
- ✅ Update Credit workflow với trạng thái mới

### Phase 3: UI/UX Redesign (Tuần 4-5)
- ✅ Redesign application_detail.php (3-zone layout)
- ✅ Create disbursement_detail.php
- ✅ Create post_approval_management.php
- ✅ Create workspace.php (Tạo yêu cầu mới)
- ✅ Implement status bar component
- ✅ Implement action buttons component

### Phase 4: Advanced Features (Tuần 6)
- ✅ Enhanced permissions system
- ✅ Detailed audit logging
- ✅ SLA tracking & alerts (optional)
- ✅ QR code integration (optional)

### Phase 5: Testing & Documentation (Tuần 7)
- ✅ Unit testing
- ✅ Integration testing
- ✅ UAT với case scenarios từ BA
- ✅ User guide & documentation

---

## 🎯 MVP SCOPE (Minimum Viable Product)

### Must Have (Release v3.0)
1. ✅ Facilities management
2. ✅ Disbursement workflow (full)
3. ✅ Exceptions & Escalations
4. ✅ Post-approval management
5. ✅ Enhanced status tracking
6. ✅ 3-zone UI layout
7. ✅ All action buttons (Save/Next/Request/Approve/Reject/Return/Escalate/Discard/Close)
8. ✅ Basic audit logging

### Should Have (Release v3.1)
1. ✅ Advanced permissions (Access/Input/Update/Delete/Approve)
2. ✅ Branch-based access control
3. ✅ SLA tracking
4. ✅ Detailed audit logs with search

### Nice to Have (Release v3.2)
1. ➕ QR code document classification
2. ➕ SLA alerts & notifications
3. ➕ Dashboard với charts
4. ➕ Mobile responsive design
5. ➕ Export reports (PDF/Excel)

---

## 🧪 TEST CASES (UAT)

### Case 1: Vay tiêu dùng có TSBĐ (End-to-End)
1. RM tạo HSTD → Upload tài liệu → Xin ngoại lệ 1 điều kiện
2. CVTĐ thẩm định → Next
3. CPD/GDK "Cho phép ngoại lệ" → Approve
4. HSTD chuyển sang "Đã phê duyệt"
5. Admin đánh dấu TSBĐ "đã nhập kho" + kích hoạt facility
6. Admin đánh dấu "Đã có hiệu lực" (legal_completed = TRUE, effective_date = TODAY)
7. RM tạo hồ sơ giải ngân
8. Kiểm soát kiểm tra điều kiện → Next
9. Thủ quỹ Approve → Giải ngân thành công
10. Kiểm tra disbursed_amount và available_amount đã cập nhật

### Case 2: Vay tín chấp bị Return/Reject với Escalate
1. RM tạo HSTD → Next
2. CVTĐ Return yêu cầu bổ sung
3. RM Request Info về bước trước → Bổ sung → Next
4. CVTĐ OK → Next
5. CPD Reject
6. RM Escalate lên GDK
7. GDK xem xét → Approve override
8. HSTD chuyển "Đã phê duyệt"

### Case 3: Điều chỉnh sau phê duyệt
1. Login Admin
2. Vào "Quản lý HSTD" → Chọn HSTD đã phê duyệt
3. Chỉnh sửa nguồn trả nợ
4. Thêm TSBĐ bổ sung
5. Cập nhật facility amount
6. Xem lịch sử phê duyệt đầy đủ
7. Verify audit log ghi đủ thông tin

---

## 💾 FILES TO CREATE/MODIFY

### New Files (19 files)
```
migrations/
  ├── 001_create_facilities.sql
  ├── 002_create_disbursements.sql
  ├── 003_create_disbursement_conditions.sql
  ├── 004_create_approval_conditions.sql
  ├── 005_create_escalations.sql
  ├── 006_create_workflow_steps.sql
  ├── 007_create_disbursement_history.sql
  ├── 008_create_document_history.sql
  ├── 009_create_roles_permissions.sql
  ├── 010_alter_credit_applications.sql
  ├── 011_alter_application_collaterals.sql
  └── 012_alter_application_documents.sql

includes/
  ├── workflow_engine.php         # Core workflow logic
  ├── facility_functions.php      # Facility management
  ├── disbursement_functions.php  # Disbursement functions
  └── permission_functions.php    # Permission checking

components/
  ├── status_bar.php              # Status bar component
  ├── action_buttons.php          # Action buttons component
  └── tab_navigation.php          # Tab navigation component

workspace.php                      # Tạo yêu cầu mới
disbursement_detail.php            # Chi tiết giải ngân
post_approval_management.php       # Quản lý HSTD sau phê duyệt
```

### Modified Files (8 files)
```
application_detail.php             # Redesign 3-zone layout
process_action.php                 # Add new actions
create_application.php             # Add facilities, conditions
index.php                          # Update dashboard
admin/manage_facilities.php        # Admin facility management
includes/functions.php             # Add new helper functions
includes/header.php                # Add workspace link
database.sql                       # Full schema v3.0
```

---

## 📊 ESTIMATED EFFORT

| Phase | Tasks | Effort | Priority |
|-------|-------|--------|----------|
| Phase 1: Database | 12 migrations | 2 days | P0 |
| Phase 2: Core Logic | Facilities + Disbursement + Exceptions | 5 days | P0 |
| Phase 3: UI/UX | Redesign 5 pages + components | 5 days | P0 |
| Phase 4: Advanced | Permissions + Audit + SLA | 3 days | P1 |
| Phase 5: Testing | UAT + Documentation | 3 days | P0 |
| **TOTAL** | | **18 days** | |

---

## ✅ ACCEPTANCE CRITERIA

### Functional Requirements
- [ ] Có thể tạo và quản lý nhiều hạn mức cho 1 HSTD
- [ ] Có thể tạo hồ sơ giải ngân từ HSTD đã có hiệu lực
- [ ] Hệ thống chặn giải ngân khi điều kiện chưa đáp ứng
- [ ] Có thể xin ngoại lệ và duyệt ngoại lệ
- [ ] Có thể Escalate khi bị reject
- [ ] Có thể quản lý HSTD sau phê duyệt
- [ ] Tất cả 9 action buttons hoạt động đúng theo role
- [ ] Audit log ghi đủ mọi thao tác

### Non-functional Requirements
- [ ] Performance: < 2s load time cho mọi trang
- [ ] Security: Maintain 95% security score
- [ ] Usability: Sinh viên có thể hoàn thành UAT cases không cần hỗ trợ
- [ ] Maintainability: Code có comments đầy đủ
- [ ] Compatibility: Works on Chrome, Firefox, Safari

---

## 🚀 NEXT STEPS

1. **Review & Approval** - Xem xét và phê duyệt phân tích này
2. **Phase 1 Start** - Bắt đầu tạo database migrations
3. **Iterative Development** - Develop theo từng phase, test liên tục
4. **UAT** - Test với case scenarios từ BA
5. **Production Deployment** - Deploy lên production

---

**Prepared by:** Claude AI
**Date:** 30/10/2025
**Status:** Pending Approval
