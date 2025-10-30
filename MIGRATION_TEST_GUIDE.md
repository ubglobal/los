# 🧪 Migration Test Guide - LOS v3.0

Hướng dẫn từng bước để test database migrations từ v2.0 → v3.0

---

## 📋 PRE-REQUISITES

Trước khi bắt đầu, kiểm tra:

```bash
# 1. Kiểm tra MySQL đang chạy
mysql --version
# Expected: mysql  Ver 15.1 Distrib 10.4.27-MariaDB (hoặc tương tự)

# 2. Kiểm tra có thể kết nối database
mysql -u root -p -e "SHOW DATABASES LIKE 'vnbc_los';"
# Expected: Hiển thị database vnbc_los

# 3. Kiểm tra quyền user
mysql -u root -p -e "SHOW GRANTS;"
# Expected: Phải có quyền CREATE, ALTER, DROP, INSERT, UPDATE

# 4. Kiểm tra migrations folder tồn tại
ls -la /home/user/los/migrations/
# Expected: Thấy 16 files (.sql và README.md)
```

---

## 🔄 BƯỚC 1: BACKUP DATABASE HIỆN TẠI

**Quan trọng:** PHẢI backup trước khi chạy migrations!

```bash
# Tạo thư mục backup
mkdir -p /home/user/los/backups

# Backup toàn bộ database
mysqldump -u root -p vnbc_los > /home/user/los/backups/vnbc_los_v2.0_$(date +%Y%m%d_%H%M%S).sql

# Kiểm tra backup đã tạo
ls -lh /home/user/los/backups/
# Expected: File .sql có kích thước > 0 bytes

# Verify backup integrity
head -20 /home/user/los/backups/vnbc_los_v2.0_*.sql
# Expected: Thấy SQL dump header và CREATE DATABASE statement
```

**Backup thành công?** ✅
- YES → Tiếp tục bước 2
- NO → Xem troubleshooting ở cuối tài liệu

---

## 🚀 BƯỚC 2: CHẠY MIGRATIONS

### Option A: Chạy tất cả migrations cùng lúc (Recommended)

```bash
# Di chuyển vào thư mục migrations
cd /home/user/los/migrations

# Chạy master script
mysql -u root -p vnbc_los < run_all_migrations.sql

# Nhập password khi được yêu cầu
```

**Kết quả mong đợi:**
```
Running migration 001: Create facilities table...
Running migration 002: Create disbursements table...
Running migration 003: Create disbursement_conditions table...
...
==================================================
Migration completed successfully!
==================================================
```

### Option B: Chạy từng migration (nếu Option A lỗi)

```bash
cd /home/user/los/migrations

# Chạy từng file theo thứ tự
mysql -u root -p vnbc_los < 001_create_facilities.sql
mysql -u root -p vnbc_los < 002_create_disbursements.sql
mysql -u root -p vnbc_los < 003_create_disbursement_conditions.sql
mysql -u root -p vnbc_los < 004_create_approval_conditions.sql
mysql -u root -p vnbc_los < 005_create_escalations.sql
mysql -u root -p vnbc_los < 006_create_workflow_steps.sql
mysql -u root -p vnbc_los < 007_create_disbursement_history.sql
mysql -u root -p vnbc_los < 008_create_document_history.sql
mysql -u root -p vnbc_los < 009_create_roles_permissions.sql
mysql -u root -p vnbc_los < 010_alter_credit_applications.sql
mysql -u root -p vnbc_los < 011_alter_application_collaterals.sql
mysql -u root -p vnbc_los < 012_alter_application_documents.sql
mysql -u root -p vnbc_los < 013_create_login_attempts.sql
```

**Migrations chạy thành công?** ✅
- YES → Tiếp tục bước 3
- NO → Xem phần Troubleshooting Common Errors

---

## ✅ BƯỚC 3: VERIFY SCHEMA

Kiểm tra database schema đã được cập nhật đúng chưa.

### 3.1. Kiểm tra Migration Tracking

```sql
mysql -u root -p vnbc_los -e "SELECT * FROM schema_migrations ORDER BY applied_at DESC;"
```

**Expected Output:**
```
+----+---------+-------------------------------------+---------------------+
| id | version | migration_name                      | applied_at          |
+----+---------+-------------------------------------+---------------------+
| 13 | 3.0.0   | 013_create_login_attempts          | 2024-10-30 ...      |
| 12 | 3.0.0   | 012_alter_application_documents    | 2024-10-30 ...      |
| 11 | 3.0.0   | 011_alter_application_collaterals  | 2024-10-30 ...      |
...
+----+---------+-------------------------------------+---------------------+
```

✅ **PASS:** 13 rows với status = 'success'
❌ **FAIL:** < 13 rows hoặc có status = 'failed'

### 3.2. Kiểm tra Tables mới

```sql
mysql -u root -p vnbc_los -e "
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'vnbc_los'
  AND TABLE_NAME IN (
    'facilities', 'disbursements', 'disbursement_conditions',
    'approval_conditions', 'escalations', 'workflow_steps',
    'disbursement_history', 'document_history',
    'roles', 'permissions', 'role_permissions', 'user_branch_access',
    'login_attempts', 'schema_migrations'
  )
ORDER BY TABLE_NAME;
"
```

**Expected Output:**
```
+---------------------------+------------+
| TABLE_NAME                | TABLE_ROWS |
+---------------------------+------------+
| approval_conditions       |         13 |
| disbursement_conditions   |          5 |
| disbursement_history      |          7 |
| disbursements             |          1 |
| document_history          |          6 |
| escalations               |          1 |
| facilities                |          6 |
| login_attempts            |          5 |
| permissions               |         42 |
| role_permissions          |       100+ |
| roles                     |          7 |
| schema_migrations         |         13 |
| user_branch_access        |          5 |
| workflow_steps            |          8 |
+---------------------------+------------+
```

✅ **PASS:** Tất cả 14 tables tồn tại với data > 0
❌ **FAIL:** Thiếu table hoặc TABLE_ROWS = 0

### 3.3. Kiểm tra Columns mới

```sql
# Kiểm tra credit_applications
mysql -u root -p vnbc_los -e "
DESCRIBE credit_applications;
" | grep -E "(effective_date|legal_completed|sla_status|workflow_type)"
```

**Expected Output:**
```
effective_date         | date
legal_completed        | tinyint(1)
legal_completed_date   | date
sla_due_date          | datetime
sla_status            | enum(...)
workflow_type         | varchar(50)
```

```sql
# Kiểm tra application_collaterals
mysql -u root -p vnbc_los -e "
DESCRIBE application_collaterals;
" | grep -E "(warehouse_status|facility_activated|legal_status)"
```

**Expected Output:**
```
warehouse_status       | enum(...)
facility_activated     | tinyint(1)
legal_status          | enum(...)
```

```sql
# Kiểm tra application_documents
mysql -u root -p vnbc_los -e "
DESCRIBE application_documents;
" | grep -E "(document_code|qr_token|version|status)"
```

**Expected Output:**
```
document_code         | varchar(50)
qr_token             | varchar(255)
version              | int(11)
status               | enum(...)
```

✅ **PASS:** Tất cả columns mới đều tồn tại
❌ **FAIL:** Thiếu column

---

## 🧪 BƯỚC 4: TEST DATA INTEGRITY

Chạy các test queries để verify data.

### Test 1: Facilities

```sql
mysql -u root -p vnbc_los -e "
SELECT
    f.facility_code,
    f.facility_type,
    f.amount,
    f.disbursed_amount,
    f.available_amount,
    f.status,
    a.hstd_code
FROM facilities f
JOIN credit_applications a ON f.application_id = a.id
ORDER BY f.id;
"
```

**Expected Output:**
```
+---------------+-------------------------+--------------+------------------+------------------+--------+----------------+
| facility_code | facility_type           | amount       | disbursed_amount | available_amount | status | hstd_code      |
+---------------+-------------------------+--------------+------------------+------------------+--------+----------------+
| FAC-2024-0001 | Ngắn hạn               | 500000000.00 | 500000000.00     |         0.00     | Active | CAR.2024.1004  |
| FAC-2024-0002 | Trung hạn              | 700000000.00 |         0.00     | 700000000.00     | Active | CAR.2024.1001  |
| FAC-2024-0003 | Dài hạn                | 1200000000.00|         0.00     | 1200000000.00    | Active | CAR.2024.1003  |
| FAC-2024-0004 | Ngắn hạn - Vốn lưu động| 2000000000.00|         0.00     | 2000000000.00    | Pending| CORP.2024.2001 |
| FAC-2024-0005 | Ngắn hạn - Vốn lưu động| 5000000000.00|         0.00     | 5000000000.00    | Pending| CORP.2024.2002 |
| FAC-2024-0006 | Ngắn hạn - Cầm cố      | 150000000.00 |         0.00     | 150000000.00     | Pending| PLEDGE.2024... |
+---------------+-------------------------+--------------+------------------+------------------+--------+----------------+
6 rows in set
```

✅ **PASS:** 6 facilities, available_amount = amount - disbursed_amount
❌ **FAIL:** Sai số lượng hoặc available_amount không đúng

### Test 2: Disbursements

```sql
mysql -u root -p vnbc_los -e "
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
"
```

**Expected Output:**
```
+-------------------+--------------+-----------+--------------+---------------+----------------+
| disbursement_code | amount       | status    | stage        | facility_code | hstd_code      |
+-------------------+--------------+-----------+--------------+---------------+----------------+
| DISB-2024-0001    | 500000000.00 | Disbursed | Đã giải ngân | FAC-2024-0001 | CAR.2024.1004  |
+-------------------+--------------+-----------+--------------+---------------+----------------+
1 row in set
```

✅ **PASS:** 1 disbursement với status = Disbursed
❌ **FAIL:** Không có hoặc sai data

### Test 3: Approval Conditions với Exception

```sql
mysql -u root -p vnbc_los -e "
SELECT
    ac.condition_text,
    ac.allow_exception,
    ac.is_exception_requested,
    ac.exception_approved,
    a.hstd_code
FROM approval_conditions ac
JOIN credit_applications a ON ac.application_id = a.id
WHERE ac.allow_exception = TRUE;
"
```

**Expected Output:**
```
+----------------------------------------------+----------------+------------------------+--------------------+---------------+
| condition_text                               | allow_exception| is_exception_requested | exception_approved | hstd_code     |
+----------------------------------------------+----------------+------------------------+--------------------+---------------+
| Xếp hạng tín dụng nội bộ tối thiểu A        |              1 |                      1 |                  1 | CAR.2024.1001 |
+----------------------------------------------+----------------+------------------------+--------------------+---------------+
1 row in set
```

✅ **PASS:** Có điều kiện với exception được approved
❌ **FAIL:** Không có data hoặc exception_approved = 0

### Test 4: Escalations

```sql
mysql -u root -p vnbc_los -e "
SELECT
    e.reason AS reason_summary,
    e.status,
    u1.full_name AS escalated_by,
    u2.full_name AS escalated_to,
    a.hstd_code
FROM escalations e
JOIN users u1 ON e.escalated_by_id = u1.id
JOIN users u2 ON e.escalated_to_id = u2.id
LEFT JOIN credit_applications a ON e.application_id = a.id;
"
```

**Expected Output:**
```
+------------------+-------------------+-----------------+------------------+----------------+
| reason_summary   | status            | escalated_by    | escalated_to     | hstd_code      |
+------------------+-------------------+-----------------+------------------+----------------+
| Kính gửi Ban...  | Resolved-Approved | Nguyễn Văn An   | Nguyễn Minh Khôi | CAR.2024.1005  |
+------------------+-------------------+-----------------+------------------+----------------+
1 row in set
```

✅ **PASS:** 1 escalation đã resolved
❌ **FAIL:** Không có data

### Test 5: Workflow Steps

```sql
mysql -u root -p vnbc_los -e "
SELECT
    workflow_type,
    step_code,
    step_name,
    step_order,
    role_required,
    sla_hours,
    is_active
FROM workflow_steps
ORDER BY workflow_type, step_order;
"
```

**Expected Output:**
```
+------------------+--------------+------------------------+------------+---------------+-----------+-----------+
| workflow_type    | step_code    | step_name              | step_order | role_required | sla_hours | is_active |
+------------------+--------------+------------------------+------------+---------------+-----------+-----------+
| Credit_Approval  | INIT         | Khởi tạo hồ sơ         |          1 | CVQHKH        |        24 |         1 |
| Credit_Approval  | REVIEW       | Thẩm định              |          2 | CVTĐ          |        48 |         1 |
| Credit_Approval  | APPROVE_CPD  | Phê duyệt CPD          |          3 | CPD           |        24 |         1 |
| Credit_Approval  | APPROVE_GDK  | Phê duyệt GĐK          |          4 | GDK           |        48 |         1 |
| Credit_Approval  | LEGAL        | Hoàn tất pháp lý       |          5 | Admin         |        72 |         1 |
| Disbursement     | INIT         | Khởi tạo giải ngân     |          1 | CVQHKH        |        12 |         1 |
| Disbursement     | CHECK_COND..| Kiểm tra điều kiện     |          2 | CVTĐ          |        24 |         1 |
| Disbursement     | APPROVE_DISB | Phê duyệt giải ngân    |          3 | CPD           |        12 |         1 |
+------------------+--------------+------------------------+------------+---------------+-----------+-----------+
8 rows in set
```

✅ **PASS:** 8 workflow steps (5 Credit + 3 Disbursement)
❌ **FAIL:** Sai số lượng hoặc thiếu steps

### Test 6: Permissions

```sql
mysql -u root -p vnbc_los -e "
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
"
```

**Expected Output:**
```
+-------------------------------------+--------------+-----------------------+-----------------+
| role_name                           | module       | permission_code       | permission_type |
+-------------------------------------+--------------+-----------------------+-----------------+
| Chuyên viên Quan hệ Khách hàng      | Collateral   | collateral.access     | Access          |
| Chuyên viên Quan hệ Khách hàng      | Collateral   | collateral.input      | Input           |
| Chuyên viên Quan hệ Khách hàng      | Credit       | credit.access         | Access          |
| Chuyên viên Quan hệ Khách hàng      | Credit       | credit.input          | Input           |
| Chuyên viên Quan hệ Khách hàng      | Credit       | credit.update         | Update          |
...
+-------------------------------------+--------------+-----------------------+-----------------+
13 rows in set (CVQHKH có 13 permissions)
```

✅ **PASS:** CVQHKH có 13 permissions
❌ **FAIL:** Sai số lượng permissions

### Test 7: Collateral Warehouse Status

```sql
mysql -u root -p vnbc_los -e "
SELECT
    ac.description,
    ac.warehouse_status,
    ac.facility_activated,
    ac.legal_status,
    a.hstd_code
FROM application_collaterals ac
JOIN credit_applications a ON ac.application_id = a.id
WHERE ac.facility_activated = TRUE;
"
```

**Expected Output:**
```
+------------------------------------------+------------------+--------------------+--------------+----------------+
| description                              | warehouse_status | facility_activated | legal_status | hstd_code      |
+------------------------------------------+------------------+--------------------+--------------+----------------+
| Xe ô tô Hyundai Accent                   | In Warehouse     |                  1 | Registered   | CAR.2024.1004  |
| Xe ô tô Toyota Vios hình thành từ vốn vay| In Warehouse     |                  1 | Registered   | CAR.2024.1001  |
| Bất động sản tại số 55 Nguyễn Trãi       | In Warehouse     |                  1 | Registered   | CAR.2024.1003  |
+------------------------------------------+------------------+--------------------+--------------+----------------+
3 rows in set
```

✅ **PASS:** Có TSBĐ đã kích hoạt
❌ **FAIL:** Không có data

### Test 8: Credit Applications với Legal Status

```sql
mysql -u root -p vnbc_los -e "
SELECT
    hstd_code,
    status,
    legal_completed,
    effective_date,
    sla_status,
    workflow_type
FROM credit_applications
ORDER BY id
LIMIT 5;
"
```

**Expected Output:**
```
+----------------+---------------+-----------------+----------------+------------+------------------+
| hstd_code      | status        | legal_completed | effective_date | sla_status | workflow_type    |
+----------------+---------------+-----------------+----------------+------------+------------------+
| CAR.2024.1001  | Đang xử lý    |               0 | NULL           | Warning    | Credit_Approval  |
| CAR.2024.1003  | Đang xử lý    |               0 | NULL           | On Track   | Credit_Approval  |
| CAR.2024.1004  | Đã phê duyệt  |               1 | 2024-05-25     | On Track   | Credit_Approval  |
| CAR.2024.1005  | Đã từ chối    |               0 | NULL           | On Track   | Credit_Approval  |
| CORP.2024.2001 | Đang xử lý    |               0 | NULL           | Overdue    | Credit_Approval  |
+----------------+---------------+-----------------+----------------+------------+------------------+
5 rows in set
```

✅ **PASS:** Columns mới có giá trị hợp lệ
❌ **FAIL:** Columns NULL hoặc sai giá trị

---

## 📊 BƯỚC 5: SUMMARY & CHECKLIST

### Migration Checklist

Đánh dấu các mục đã pass:

- [ ] **Backup database** hoàn thành
- [ ] **Run migrations** không lỗi
- [ ] **schema_migrations** có 13 records
- [ ] **14 tables mới** tồn tại
- [ ] **Test 1 - Facilities:** 6 rows, available_amount đúng
- [ ] **Test 2 - Disbursements:** 1 row, status = Disbursed
- [ ] **Test 3 - Approval Conditions:** Exception được approved
- [ ] **Test 4 - Escalations:** 1 escalation resolved
- [ ] **Test 5 - Workflow Steps:** 8 steps (5+3)
- [ ] **Test 6 - Permissions:** CVQHKH có 13 permissions
- [ ] **Test 7 - Collateral Warehouse:** 3 TSBĐ đã kích hoạt
- [ ] **Test 8 - Credit Apps:** Legal status đúng

### Success Criteria

✅ **ALL PASS (12/12)** → Migration thành công! Chuyển sang Phase 2.

⚠️ **PARTIAL PASS (8-11/12)** → Migration OK nhưng có vài issues. Review và fix minor issues.

❌ **FAIL (<8/12)** → Migration có vấn đề. Cần rollback và debug.

---

## 🔧 TROUBLESHOOTING COMMON ERRORS

### Error 1: "Table already exists"

**Error message:**
```
ERROR 1050 (42S01): Table 'facilities' already exists
```

**Solution:**
```sql
-- Drop table và chạy lại
DROP TABLE IF EXISTS facilities;

-- Hoặc rollback toàn bộ
mysql -u root -p vnbc_los < rollback_all_migrations.sql
```

### Error 2: "Cannot add foreign key constraint"

**Error message:**
```
ERROR 1215 (HY000): Cannot add foreign key constraint
```

**Solution:**
```sql
-- Kiểm tra referenced table tồn tại chưa
SHOW TABLES LIKE 'credit_applications';

-- Kiểm tra column type khớp nhau
DESCRIBE credit_applications;
DESCRIBE facilities;

-- Chạy lại migrations theo đúng thứ tự
```

### Error 3: "Duplicate column name"

**Error message:**
```
ERROR 1060 (42S21): Duplicate column name 'effective_date'
```

**Solution:**
```sql
-- Kiểm tra column đã tồn tại
SHOW COLUMNS FROM credit_applications LIKE 'effective_date';

-- Nếu đã có, skip migration ALTER hoặc dùng:
ALTER TABLE credit_applications ADD COLUMN IF NOT EXISTS effective_date DATE;
```

### Error 4: "Data truncated for column"

**Error message:**
```
ERROR 1265 (01000): Data truncated for column 'sla_status'
```

**Solution:**
```sql
-- Kiểm tra data hiện tại
SELECT DISTINCT stage FROM credit_applications;

-- Clean data trước khi ALTER
UPDATE credit_applications SET sla_status = 'On Track' WHERE sla_status IS NULL;
```

### Error 5: "Access denied"

**Error message:**
```
ERROR 1045 (28000): Access denied for user 'root'@'localhost'
```

**Solution:**
```bash
# Reset MySQL password hoặc dùng user khác có đủ quyền
mysql -u admin -p vnbc_los < run_all_migrations.sql
```

---

## 🔙 ROLLBACK (Nếu cần)

### Khi nào cần rollback?

- Migration fail nhiều lần
- Data bị corrupt
- Muốn quay lại v2.0 để fix issues

### Cách rollback

```bash
# Option 1: Restore từ backup (Recommended)
mysql -u root -p vnbc_los < /home/user/los/backups/vnbc_los_v2.0_YYYYMMDD_HHMMSS.sql

# Option 2: Chạy rollback script
cd /home/user/los/migrations
mysql -u root -p vnbc_los < rollback_all_migrations.sql

# Verify rollback
mysql -u root -p vnbc_los -e "SHOW TABLES LIKE '%facilities%';"
# Expected: Empty set (không còn table facilities)
```

---

## 📞 NEXT STEPS

### Nếu tất cả tests PASS ✅

Chúc mừng! Database đã sẵn sàng cho Phase 2.

**Next actions:**
1. Backup database v3.0 mới
2. Update .env config nếu cần
3. Bắt đầu Phase 2 - Core Business Logic
4. Thông báo team về database changes

### Nếu có tests FAIL ❌

1. Review error messages
2. Check troubleshooting section
3. Tạo GitHub issue với error details
4. Liên hệ support (hoặc hỏi tôi)

---

## 📝 Test Report Template

Sau khi test xong, tạo report:

```markdown
# Migration Test Report

Date: YYYY-MM-DD
Tester: [Your Name]
Environment: Development/Staging/Production

## Test Results

| Test | Status | Notes |
|------|--------|-------|
| Backup | ✅/❌ | |
| Run Migrations | ✅/❌ | |
| Schema Migrations | ✅/❌ | 13/13 records |
| Test 1 - Facilities | ✅/❌ | |
| Test 2 - Disbursements | ✅/❌ | |
| Test 3 - Approval Conditions | ✅/❌ | |
| Test 4 - Escalations | ✅/❌ | |
| Test 5 - Workflow Steps | ✅/❌ | |
| Test 6 - Permissions | ✅/❌ | |
| Test 7 - Collateral Warehouse | ✅/❌ | |
| Test 8 - Credit Apps | ✅/❌ | |

## Issues Found

[Liệt kê các issues nếu có]

## Conclusion

Migration: ✅ SUCCESS / ❌ FAILED
Ready for Phase 2: YES/NO
```

---

**Generated by:** Claude AI
**Date:** 2024-10-30
**Version:** 3.0.0
