# CHECKLIST KHẮC PHỤC BẢO MẬT - LOS SYSTEM

## ✅ TIẾN ĐỘ THỰC HIỆN

### 🔴 CRITICAL - KHẮC PHỤC NGAY (0/4 hoàn thành)

- [ ] **#1: CSRF Protection**
  - [ ] Tạo file `config/csrf.php`
  - [ ] Thêm CSRF token vào `login.php`
  - [ ] Thêm CSRF token vào `create_application.php`
  - [ ] Thêm CSRF token vào `process_action.php`
  - [ ] Thêm CSRF token vào `application_detail.php`
  - [ ] Thêm CSRF token vào tất cả admin forms
  - [ ] Test: Thử submit form không có token (phải bị reject)

- [ ] **#2: File Upload Security**
  - [ ] Thêm kiểm tra MIME type trong `process_action.php`
  - [ ] Thêm kiểm tra file extension
  - [ ] Thêm giới hạn file size (10MB)
  - [ ] Tạo tên file random thay vì dùng tên gốc
  - [ ] Tạo file `uploads/.htaccess` để chặn PHP execution
  - [ ] Test: Thử upload file .php (phải bị reject)
  - [ ] Test: Thử upload file .pdf (phải thành công)

- [ ] **#3: IDOR - Access Control**
  - [ ] Thêm kiểm tra quyền truy cập vào `application_detail.php`
  - [ ] Verify user có được assign hồ sơ không
  - [ ] Verify user là người tạo hồ sơ không
  - [ ] Kiểm tra lịch sử tham gia xử lý
  - [ ] Admin bypass check
  - [ ] Test: User A không thể xem hồ sơ của User B
  - [ ] Test: Admin có thể xem tất cả hồ sơ

- [ ] **#4: Database Credentials**
  - [ ] Tạo file `.env` với DB credentials
  - [ ] Sửa `config/db.php` để đọc từ `.env`
  - [ ] Thêm `.env` vào `.gitignore`
  - [ ] Tạo `.env.example` với dummy values
  - [ ] Tạo `uploads/.htaccess` để bảo vệ .env
  - [ ] Test: Không thể truy cập `.env` từ browser
  - [ ] Remove hardcoded password từ Git history (nếu cần)

---

### 🟠 HIGH - TRONG TUẦN NÀY (0/6 hoàn thành)

- [ ] **#5: Session Fixation**
  - [ ] Thêm `session_regenerate_id(true)` sau login trong `login.php`
  - [ ] Thêm IP check vào session
  - [ ] Thêm User-Agent check
  - [ ] Thêm login_time vào session
  - [ ] Test: Session ID phải thay đổi sau login

- [ ] **#6: Session Timeout**
  - [ ] Tạo `config/session.php` với timeout logic
  - [ ] Thêm inactivity timeout (30 phút)
  - [ ] Thêm absolute timeout (8 giờ)
  - [ ] Thêm `check_session_timeout()` vào tất cả protected pages
  - [ ] Test: Session timeout sau 30 phút không hoạt động
  - [ ] Hiển thị warning khi gần timeout (tùy chọn)

- [ ] **#7: Path Traversal**
  - [ ] Sửa logic xóa file trong `process_action.php`
  - [ ] Sử dụng `basename()` để strip path
  - [ ] Sử dụng `realpath()` để verify path
  - [ ] Kiểm tra file nằm trong uploads/ directory
  - [ ] Test: Không thể xóa file ngoài uploads/

- [ ] **#8: Rate Limiting**
  - [ ] Tạo `config/rate_limit.php`
  - [ ] Tạo bảng `login_attempts`
  - [ ] Thêm check vào `login.php`
  - [ ] Lock account sau 5 lần sai
  - [ ] Lockout time 15 phút
  - [ ] Test: Lock sau 5 lần đăng nhập sai
  - [ ] Thêm CAPTCHA (optional)

- [ ] **#9: Demo Credentials**
  - [ ] PRODUCTION: Xóa toàn bộ demo section trong `login.php`
  - [ ] Hoặc: Thêm environment check
  - [ ] Đổi password tất cả demo accounts
  - [ ] Xóa hoặc disable demo accounts trong DB
  - [ ] Test: Không hiển thị credentials trong production

- [ ] **#10: Security Headers**
  - [ ] Thêm headers vào `includes/header.php`
  - [ ] X-Frame-Options: DENY
  - [ ] X-Content-Type-Options: nosniff
  - [ ] X-XSS-Protection: 1; mode=block
  - [ ] Content-Security-Policy
  - [ ] Strict-Transport-Security (nếu có HTTPS)
  - [ ] Test: Verify headers với securityheaders.com

---

### 🟡 MEDIUM - TRONG THÁNG (0/5 hoàn thành)

- [ ] **#11: Username Enumeration**
  - [ ] Thêm random delay vào login failure
  - [ ] Test: Timing attack không phân biệt được user tồn tại hay không

- [ ] **#12: Input Length Validation**
  - [ ] Thêm maxlength vào tất cả input fields
  - [ ] Server-side validation độ dài
  - [ ] Test: Không thể submit input quá dài

- [ ] **#13: Error Disclosure**
  - [ ] Tắt display_errors trong production
  - [ ] Sử dụng error_log
  - [ ] Generic error messages cho user
  - [ ] Detailed errors vào log file

- [ ] **#14: SQL Table Name**
  - [ ] Thêm validation cho whitelist
  - [ ] Error logging cho invalid attempts

- [ ] **#15: Directory Listing**
  - [ ] Tạo `uploads/index.php` với 403
  - [ ] Thêm `Options -Indexes` vào .htaccess
  - [ ] Test: Không thể browse uploads/

---

### ⚪ LOW - KHI CÓ THỜI GIAN (0/3 hoàn thành)

- [ ] **#16: HTTPS Enforcement**
  - [ ] Tạo `config/https.php`
  - [ ] Redirect HTTP → HTTPS
  - [ ] Test: HTTP auto redirect

- [ ] **#17: Predictable IDs**
  - [ ] Thay rand() bằng random_int()
  - [ ] Hoặc sử dụng UUID

- [ ] **#18: Password Complexity**
  - [ ] Thêm validation trong `admin/manage_users.php`
  - [ ] Min 12 ký tự
  - [ ] Uppercase, lowercase, number, special char
  - [ ] Test: Không thể tạo weak password

---

## 📋 CODE FILES CẦN SỬA

### Files mới tạo:
- [ ] `config/csrf.php`
- [ ] `config/session.php`
- [ ] `config/rate_limit.php`
- [ ] `config/https.php`
- [ ] `.env`
- [ ] `.env.example`
- [ ] `.gitignore`
- [ ] `uploads/.htaccess`
- [ ] `uploads/index.php`
- [ ] `.htaccess` (root)

### Files cần sửa:
- [ ] `config/db.php`
- [ ] `login.php`
- [ ] `logout.php`
- [ ] `index.php`
- [ ] `create_application.php`
- [ ] `application_detail.php`
- [ ] `process_action.php`
- [ ] `includes/header.php`
- [ ] `includes/functions.php`
- [ ] `admin/manage_users.php`
- [ ] `admin/manage_customers.php`
- [ ] `admin/manage_products.php`
- [ ] `admin/manage_collaterals.php`
- [ ] `admin/includes/header.php`

---

## 🧪 TESTING CHECKLIST

### Manual Testing:
- [ ] CSRF: Thử submit form từ domain khác
- [ ] File Upload: Thử upload .php, .exe, .sh
- [ ] IDOR: Thử access application của user khác
- [ ] SQL Injection: Test tất cả inputs (should be safe)
- [ ] XSS: Test với `<script>alert(1)</script>`
- [ ] Session: Test timeout sau 30 phút
- [ ] Rate Limit: Đăng nhập sai 5 lần
- [ ] Path Traversal: Thử delete `../../config/db.php`

### Automated Scanning:
- [ ] Run OWASP ZAP scan
- [ ] Run Nikto scan
- [ ] Test với Burp Suite
- [ ] Check security headers
- [ ] Check SSL/TLS config

---

## 📊 PROGRESS TRACKING

| Priority | Total | Done | % |
|----------|-------|------|---|
| 🔴 CRITICAL | 4 | 0 | 0% |
| 🟠 HIGH | 6 | 0 | 0% |
| 🟡 MEDIUM | 5 | 0 | 0% |
| ⚪ LOW | 3 | 0 | 0% |
| **TOTAL** | **18** | **0** | **0%** |

---

## 🎯 MILESTONE TARGETS

- [ ] **Milestone 1 (End of Week 1):** All CRITICAL issues fixed (4/4)
- [ ] **Milestone 2 (End of Week 2):** All HIGH issues fixed (6/6)
- [ ] **Milestone 3 (End of Week 3):** All MEDIUM issues fixed (5/5)
- [ ] **Milestone 4 (End of Week 4):** All LOW issues fixed (3/3)
- [ ] **Milestone 5 (End of Week 5):** Penetration test & deployment

---

## 📞 SUPPORT & ESCALATION

**Nếu gặp vấn đề khi implement:**
1. Review lại SECURITY_AUDIT_REPORT.md
2. Tham khảo OWASP guidelines
3. Test trên development environment trước
4. Backup database và code trước khi deploy
5. Có rollback plan

**Production Deployment:**
- [ ] Backup database
- [ ] Backup codebase
- [ ] Deploy vào off-peak hours
- [ ] Monitor logs sau deploy
- [ ] Có plan B nếu có issue

---

**Last Updated:** 29/10/2025
**Owner:** Development Team
**Reviewer:** Security Team
