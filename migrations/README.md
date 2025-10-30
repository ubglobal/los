# Database Migrations - LOS v2.0 → v3.0

Bộ migration scripts để nâng cấp hệ thống LOS từ phiên bản 2.0 (Security-Hardened) lên phiên bản 3.0 (Full BA Specification).

---

## 📊 Tổng quan

### Thay đổi chính

**13 bảng mới:**
- `facilities` - Quản lý hạn mức tín dụng
- `disbursements` - Quy trình giải ngân
- `disbursement_conditions` - Điều kiện giải ngân
- `approval_conditions` - Điều kiện phê duyệt (hỗ trợ ngoại lệ)
- `escalations` - Khiếu nại/Leo thang
- `workflow_steps` - Định nghĩa quy trình
- `disbursement_history` - Lịch sử giải ngân
- `document_history` - Lịch sử tài liệu
- `roles` - Nhóm quyền
- `permissions` - Quyền hệ thống
- `role_permissions` - Gán quyền
- `user_branch_access` - Quyền theo chi nhánh
- `login_attempts` - Rate limiting
- `schema_migrations` - Tracking migrations

**3 bảng được nâng cấp:**
- `credit_applications` - Thêm trạng thái "Đã có hiệu lực", SLA tracking
- `application_collaterals` - Quản lý kho TSBĐ, kích hoạt hạn mức
- `application_documents` - QR code, versioning, expiry tracking

---

## 🚀 Cách sử dụng

### Yêu cầu

- MySQL/MariaDB 5.7+
- Quyền CREATE, ALTER, DROP tables
- **Đã backup database!**

### 1. Backup Database

```bash
# Backup toàn bộ database
mysqldump -u root -p vnbc_los > backup_vnbc_los_v2.0_$(date +%Y%m%d_%H%M%S).sql

# Hoặc backup cụ thể
mysqldump -u root -p vnbc_los --no-data > schema_v2.0.sql
mysqldump -u root -p vnbc_los --no-create-info > data_v2.0.sql
```

### 2. Chạy Migrations

#### Option A: Chạy tất cả migrations (Recommended)

```bash
cd /path/to/los/migrations
mysql -u root -p vnbc_los < run_all_migrations.sql
```

#### Option B: Chạy từng migration (Manual)

```bash
mysql -u root -p vnbc_los < 001_create_facilities.sql
mysql -u root -p vnbc_los < 002_create_disbursements.sql
# ... tiếp tục với các file khác
```

### 3. Kiểm tra kết quả

```sql
-- Kiểm tra migration tracking
SELECT * FROM schema_migrations ORDER BY applied_at DESC;

-- Kiểm tra số lượng bảng
SHOW TABLES;

-- Kiểm tra schema của bảng mới
DESCRIBE facilities;
DESCRIBE disbursements;

-- Kiểm tra data mẫu
SELECT * FROM facilities;
SELECT * FROM disbursements LIMIT 5;
SELECT * FROM workflow_steps;
```

### 4. Rollback (nếu cần)

⚠️ **WARNING: Rollback sẽ XÓA tất cả dữ liệu v3.0!**

```bash
# Restore từ backup
mysql -u root -p vnbc_los < backup_vnbc_los_v2.0_YYYYMMDD.sql

# Hoặc chạy rollback script
mysql -u root -p vnbc_los < rollback_all_migrations.sql
```

---

## 📋 Danh sách Migrations

| # | File | Mô tả | Phụ thuộc |
|---|------|-------|-----------|
| 001 | `001_create_facilities.sql` | Tạo bảng hạn mức tín dụng | credit_applications, products |
| 002 | `002_create_disbursements.sql` | Tạo bảng giải ngân | facilities |
| 003 | `003_create_disbursement_conditions.sql` | Điều kiện giải ngân | disbursements |
| 004 | `004_create_approval_conditions.sql` | Điều kiện phê duyệt + ngoại lệ | credit_applications |
| 005 | `005_create_escalations.sql` | Khiếu nại/Leo thang | credit_applications, disbursements |
| 006 | `006_create_workflow_steps.sql` | Định nghĩa workflow | - |
| 007 | `007_create_disbursement_history.sql` | Lịch sử giải ngân | disbursements |
| 008 | `008_create_document_history.sql` | Lịch sử tài liệu | application_documents |
| 009 | `009_create_roles_permissions.sql` | Hệ thống phân quyền | users |
| 010 | `010_alter_credit_applications.sql` | Nâng cấp bảng HSTD | workflow_steps |
| 011 | `011_alter_application_collaterals.sql` | Nâng cấp bảng TSBĐ | users |
| 012 | `012_alter_application_documents.sql` | Nâng cấp bảng tài liệu | users |
| 013 | `013_create_login_attempts.sql` | Rate limiting | - |

---

## 🧪 Testing

### Test Plan

```sql
-- Test 1: Kiểm tra facilities
SELECT
    f.facility_code,
    f.amount,
    f.disbursed_amount,
    f.available_amount,
    f.status,
    a.hstd_code
FROM facilities f
JOIN credit_applications a ON f.application_id = a.id;

-- Test 2: Kiểm tra disbursements
SELECT
    d.disbursement_code,
    d.amount,
    d.status,
    d.stage,
    f.facility_code,
    a.hstd_code
FROM disbursements d
JOIN facilities f ON d.facility_id = f.id
JOIN credit_applications a ON d.application_id = a.id;

-- Test 3: Kiểm tra approval conditions với ngoại lệ
SELECT
    ac.condition_text,
    ac.allow_exception,
    ac.is_exception_requested,
    ac.exception_approved,
    a.hstd_code
FROM approval_conditions ac
JOIN credit_applications a ON ac.application_id = a.id
WHERE ac.allow_exception = TRUE;

-- Test 4: Kiểm tra escalations
SELECT
    e.reason,
    e.status,
    e.resolution,
    u1.full_name AS escalated_by,
    u2.full_name AS escalated_to,
    a.hstd_code
FROM escalations e
JOIN users u1 ON e.escalated_by_id = u1.id
JOIN users u2 ON e.escalated_to_id = u2.id
LEFT JOIN credit_applications a ON e.application_id = a.id;

-- Test 5: Kiểm tra workflow_steps
SELECT
    workflow_type,
    step_code,
    step_name,
    step_order,
    role_required,
    allowed_actions,
    sla_hours
FROM workflow_steps
ORDER BY workflow_type, step_order;

-- Test 6: Kiểm tra permissions
SELECT
    r.role_name,
    p.module,
    p.permission_code,
    p.permission_type
FROM role_permissions rp
JOIN roles r ON rp.role_id = r.id
JOIN permissions p ON rp.permission_id = p.id
WHERE r.role_code = 'CVQHKH'
ORDER BY p.module, p.permission_type;

-- Test 7: Kiểm tra TSBĐ warehouse status
SELECT
    ac.description AS collateral,
    ac.warehouse_status,
    ac.facility_activated,
    ac.legal_status,
    a.hstd_code
FROM application_collaterals ac
JOIN credit_applications a ON ac.application_id = a.id;

-- Test 8: Kiểm tra credit_applications mới
SELECT
    hstd_code,
    status,
    legal_completed,
    effective_date,
    sla_status,
    workflow_type
FROM credit_applications;
```

### Expected Results

- Facilities: Tất cả HSTD đã phê duyệt phải có ít nhất 1 facility
- Disbursements: HSTD 4 phải có 1 disbursement với status = 'Disbursed'
- Approval Conditions: HSTD 1 phải có 1 điều kiện với exception_approved = TRUE
- Escalations: HSTD 5 phải có 1 escalation
- Workflow Steps: Credit_Approval có 5 steps, Disbursement có 3 steps
- Permissions: CVQHKH có 13 quyền, Admin có tất cả quyền
- Collaterals: HSTD 4 có facility_activated = TRUE
- Credit Applications: Các field mới phải có giá trị hợp lệ

---

## ⚠️ Common Issues & Solutions

### Issue 1: Foreign key constraint fails

**Error:**
```
Cannot add or update a child row: a foreign key constraint fails
```

**Solution:**
- Chạy lại từ đầu với `SET FOREIGN_KEY_CHECKS=0;`
- Hoặc chạy migrations theo đúng thứ tự

### Issue 2: Table already exists

**Error:**
```
Table 'facilities' already exists
```

**Solution:**
```sql
-- Drop table và chạy lại
DROP TABLE IF EXISTS facilities;
-- Sau đó chạy lại migration
```

### Issue 3: Column already exists

**Error:**
```
Duplicate column name 'effective_date'
```

**Solution:**
```sql
-- Check nếu đã có column
SHOW COLUMNS FROM credit_applications LIKE 'effective_date';

-- Nếu có rồi, skip migration đó hoặc dùng ALTER ... ADD COLUMN IF NOT EXISTS (MySQL 8.0+)
```

### Issue 4: Data migration fails

**Error:**
```
Data truncated for column 'sla_status'
```

**Solution:**
- Kiểm tra data hiện tại
- Sửa giá trị không hợp lệ trước khi chạy migration
- Hoặc dùng `UPDATE ... SET ... WHERE` để cleanup

---

## 📐 Database Schema Diagram

### Quan hệ chính

```
credit_applications (1) ─→ (n) facilities
                    (1) ─→ (n) approval_conditions
                    (1) ─→ (n) escalations (optional)

facilities (1) ─→ (n) disbursements

disbursements (1) ─→ (n) disbursement_conditions
              (1) ─→ (n) disbursement_history
              (1) ─→ (n) escalations (optional)

application_documents (1) ─→ (n) document_history

users (1) ─→ (n) user_branch_access
      (1) ─→ (n) role_permissions via roles

roles (1) ─→ (n) role_permissions ─→ (n) permissions
```

### Business Rules Enforced by Schema

1. **Facility activation:** `facility_activated = TRUE` chỉ khi `warehouse_status = 'In Warehouse'`
2. **Disbursement creation:** Chỉ khi application có `effective_date NOT NULL AND legal_completed = TRUE`
3. **Exception request:** Chỉ khi `allow_exception = TRUE`
4. **Escalation target:** Phải link đến application HOẶC disbursement (không được cả hai)
5. **Available amount:** `facilities.available_amount = amount - disbursed_amount` (GENERATED COLUMN)

---

## 🔄 Migration History

| Version | Date | Changes | Migration Count |
|---------|------|---------|-----------------|
| v2.0 | 2024-10-29 | Security hardening (CSRF, IDOR, Session, Rate Limit) | - |
| v3.0 | 2024-10-30 | Full BA spec (Disbursement, Facilities, Escalation, Permissions) | 13 |

---

## 📚 Additional Resources

- **BA Document:** `/home/user/los/UPGRADE_ANALYSIS.md`
- **Security Audit:** `/home/user/los/SECURITY_AUDIT_REPORT.md`
- **Main README:** `/home/user/los/README.md`

---

## 👥 Support

Nếu gặp vấn đề:
1. Kiểm tra section **Common Issues** ở trên
2. Review migration logs: `SELECT * FROM schema_migrations;`
3. Check MySQL error log
4. Restore từ backup và thử lại

---

## ✅ Checklist sau khi Migration

- [ ] Tất cả 13 migrations đã chạy thành công
- [ ] `schema_migrations` có 13 records với status = 'success'
- [ ] Test queries ở phần Testing chạy không lỗi
- [ ] Application code đã được update để sử dụng features mới
- [ ] Backup database v3.0 mới
- [ ] Update documentation
- [ ] Thông báo cho team về changes mới

---

**Generated by:** Claude AI
**Date:** 2024-10-30
**Version:** 3.0.0
