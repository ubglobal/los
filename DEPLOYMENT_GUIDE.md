# LOS v3.0 - HƯỚNG DẪN CÀI ĐẶT MỚI (PRODUCTION)

**Phiên bản:** 3.0 (Clean Production Version)
**Ngày:** 2025-10-30
**Trạng thái:** ✅ Production Ready

---

## 📋 YÊU CẦU HỆ THỐNG

### Server Requirements

**Bắt buộc:**
- **PHP:** 7.4 hoặc cao hơn (khuyến nghị: PHP 8.0+)
- **MySQL:** 5.7 hoặc cao hơn (khuyến nghị: MySQL 8.0+)
- **Web Server:** Apache 2.4+ hoặc Nginx 1.18+
- **Disk Space:** Tối thiểu 100MB
- **Memory:** Tối thiểu 256MB RAM

**PHP Extensions cần thiết:**
```bash
php-mysqli
php-json
php-session
php-fileinfo
php-mbstring
```

**Apache Modules (nếu dùng Apache):**
```bash
mod_rewrite
mod_headers
```

---

## 📦 CẤU TRÚC THỨ MỤC

Sau khi cài đặt, cấu trúc sẽ như sau:

```
los/
├── admin/                      # Admin interface
│   ├── includes/              # Admin-only includes
│   ├── customer_detail.php
│   ├── index.php              # Admin dashboard
│   ├── manage_*.php           # Admin management pages
├── config/                     # Configuration files
│   ├── db.php                 # Database config (auto-generated)
│   ├── session.php
│   ├── csrf.php
│   ├── rate_limit.php
├── includes/                   # Shared functions
│   ├── functions.php
│   ├── workflow_engine.php
│   ├── exception_escalation_functions.php
│   ├── disbursement_functions.php
│   ├── facility_functions.php
│   ├── permission_functions.php
│   ├── header.php
│   ├── footer.php
│   ├── security_init.php
├── migrations/                 # Database migration scripts
│   └── phase3_all_fixes_migration.sql
├── uploads/                    # Document uploads (auto-created)
│   ├── .htaccess              # Security protection
│   └── index.php              # Prevent directory listing
├── .htaccess                   # Apache configuration
├── .env.example                # Environment config template
├── .gitignore                  # Git ignore rules
├── database.sql                # Main database schema
├── demo_data.sql               # Demo data (optional)
├── generate_hash.php           # Password hash generator
├── install.php                 # Web installer
├── test_phase3_fixes.php       # Post-install verification
├── index.php                   # Main dashboard
├── login.php                   # Login page
├── logout.php                  # Logout handler
├── create_application.php      # Create credit application
├── application_detail.php      # Application details
├── disbursement_*.php          # Disbursement management
├── process_action.php          # Action processor
├── download_document.php       # Secure document download
├── reports.php                 # Reports page
├── robots.txt                  # SEO configuration
├── README.md                   # Project documentation
├── INSTALLATION_GUIDE.md       # Detailed installation guide
├── V3.0_RELEASE_NOTES.md       # Release notes
└── DEPLOYMENT_GUIDE.md         # This file
```

---

## 🚀 HƯỚNG DẪN CÀI ĐẶT

### Phương Án 1: Cài Đặt Tự Động (Khuyến Nghị)

#### Bước 1: Upload Code

```bash
# Extract và upload tất cả files lên server
# Hoặc clone từ git:
git clone [repository-url] los
cd los
```

#### Bước 2: Cấu Hình Permissions

```bash
# Set ownership (thay www-data bằng user web server của bạn)
chown -R www-data:www-data .

# Set permissions
chmod -R 755 .
chmod -R 775 uploads/
chmod 600 .env
```

#### Bước 3: Chạy Web Installer

1. Truy cập: `http://your-domain.com/install.php`
2. Nhập thông tin database:
   - Database Host
   - Database Name
   - Database Username
   - Database Password
3. Nhập thông tin Admin account:
   - Username
   - Email
   - Password
   - Full Name
4. Click "Install"
5. **XÓA file install.php sau khi cài đặt xong!**

```bash
rm install.php
```

---

### Phương Án 2: Cài Đặt Thủ Công

#### Bước 1: Tạo Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE los_v3 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'los_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON los_v3.* TO 'los_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Bước 2: Import Database Schema

```bash
# Import schema chính
mysql -u los_user -p los_v3 < database.sql

# (Optional) Import demo data
mysql -u los_user -p los_v3 < demo_data.sql
```

#### Bước 3: Tạo File .env

```bash
cp .env.example .env
nano .env
```

Cấu hình nội dung:
```env
DB_HOST=localhost
DB_NAME=los_v3
DB_USER=los_user
DB_PASS=your_strong_password
```

#### Bước 4: Tạo Admin User

```bash
# Generate password hash
php generate_hash.php
# Enter your desired password
```

Sau đó insert vào database:
```sql
INSERT INTO users (username, email, password_hash, full_name, role, branch, created_at)
VALUES (
    'admin',
    'admin@yourdomain.com',
    'YOUR_GENERATED_HASH',
    'System Administrator',
    'Admin',
    'Hội sở',
    NOW()
);
```

#### Bước 5: Cấu Hình Permissions

```bash
chmod -R 755 .
chmod -R 775 uploads/
chmod 600 .env
chown -R www-data:www-data .
```

---

## ✅ KIỂM TRA SAU CÀI ĐẶT

### 1. Chạy Test Script

```bash
php test_phase3_fixes.php
```

**Kết quả mong đợi:**
```
Total Tests:  14
Passed:       14 ✅
Failed:       0 ❌
Pass Rate:    100.00%

🎉 ALL TESTS PASSED! Phase 3 fixes are working correctly.
✅ System is ready for production use.
```

### 2. Kiểm Tra Web Interface

- [ ] Truy cập `http://your-domain.com/`
- [ ] Login với admin account
- [ ] Kiểm tra Dashboard hiển thị đúng
- [ ] Test tạo customer mới
- [ ] Test tạo application mới
- [ ] Upload document thử
- [ ] Kiểm tra workflow transitions

### 3. Kiểm Tra Security

- [ ] Verify .env không accessible từ web
- [ ] Verify uploads/ chỉ allow download qua download_document.php
- [ ] Test CSRF protection
- [ ] Test session timeout
- [ ] Test SQL injection protection (dùng test input)

---

## 🔒 BẢO MẬT PRODUCTION

### Bắt Buộc Phải Làm:

1. **Đổi tất cả passwords mặc định**
2. **Xóa file install.php**
3. **Xóa file generate_hash.php** (sau khi tạo admin user)
4. **Cấu hình HTTPS (SSL/TLS)**
5. **Backup database định kỳ**
6. **Giới hạn truy cập admin/** (IP whitelist)
7. **Enable error logging (không hiện ra màn hình)**

### Cấu Hình Apache (.htaccess đã có)

File `.htaccess` đã được cấu hình với:
- Prevent directory listing
- Protect sensitive files (.env, .git, etc.)
- Force HTTPS (uncomment nếu có SSL)
- Security headers

### Cấu Hình PHP (php.ini hoặc .user.ini)

```ini
# Production settings
display_errors = Off
log_errors = On
error_log = /path/to/logs/php_errors.log

# Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

# Upload limits
upload_max_filesize = 10M
post_max_size = 10M

# Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

---

## 🔧 CẤU HÌNH NÂNG CAO

### Crontab (Scheduled Tasks)

Thêm vào crontab để chạy maintenance tasks:

```bash
crontab -e
```

```cron
# Daily cleanup of old sessions (3 AM)
0 3 * * * php /path/to/los/cleanup_sessions.php

# Daily database backup (2 AM)
0 2 * * * mysqldump -u los_user -p'password' los_v3 | gzip > /backups/los_$(date +\%Y\%m\%d).sql.gz

# Weekly cleanup old uploads (Sunday 4 AM)
0 4 * * 0 find /path/to/los/uploads -mtime +365 -type f -delete
```

### Email Configuration

Để gửi notifications, cấu hình SMTP trong `config/email.php` (tạo file mới):

```php
<?php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'LOS System');
```

### Database Optimization

```sql
-- Add after initial setup
-- Optimize tables monthly
OPTIMIZE TABLE users, customers, credit_applications, disbursements;

-- Update statistics
ANALYZE TABLE users, customers, credit_applications;

-- Check indexes
SHOW INDEX FROM credit_applications;
```

---

## 📊 MONITORING & LOGS

### Log Files Locations

```
/var/log/apache2/error.log          # Apache errors
/var/log/mysql/error.log            # MySQL errors
/path/to/los/logs/php_errors.log    # PHP errors (nếu cấu hình)
/path/to/los/logs/application.log   # Application logs (tạo mới)
```

### Monitoring Checklist

- [ ] Disk space (uploads/ folder)
- [ ] Database size
- [ ] Error logs
- [ ] Session cleanup
- [ ] Backup verification
- [ ] SSL certificate expiry
- [ ] User activity logs

---

## 🔄 BACKUP & RESTORE

### Backup Strategy

**Daily Backups:**
```bash
#!/bin/bash
# backup_daily.sh

DATE=$(date +%Y%m%d)
BACKUP_DIR="/backups/los"

# Database backup
mysqldump -u los_user -p'password' los_v3 | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /path/to/los/uploads/

# Keep only last 7 days
find $BACKUP_DIR -mtime +7 -delete
```

### Restore Process

```bash
# Restore database
gunzip -c /backups/los/db_20251030.sql.gz | mysql -u los_user -p los_v3

# Restore files
tar -xzf /backups/los/files_20251030.tar.gz -C /
```

---

## 🚨 TROUBLESHOOTING

### Common Issues

**Issue 1: Không login được**
- Kiểm tra database connection (.env)
- Verify password hash đúng
- Check session folder permissions

**Issue 2: Upload file không được**
- Check uploads/ folder permissions (775)
- Verify PHP upload_max_filesize
- Check disk space

**Issue 3: Workflow không hoạt động**
- Chạy: `php test_phase3_fixes.php`
- Verify workflow_steps table có data
- Check current_step_id foreign key

**Issue 4: Lỗi 500 Internal Server Error**
- Check Apache/Nginx error logs
- Verify .htaccess syntax
- Check PHP version compatibility

**Issue 5: CSRF token invalid**
- Clear sessions
- Check session.save_path permissions
- Verify session timeout settings

---

## 📞 SUPPORT

**Documentation:**
- README.md - Tổng quan dự án
- INSTALLATION_GUIDE.md - Hướng dẫn chi tiết
- V3.0_RELEASE_NOTES.md - Ghi chú phát hành

**Testing:**
- test_phase3_fixes.php - Verification script
- migrations/ - Database migration scripts

**Code Quality:**
- ✅ 100% SQL injection protected (prepared statements)
- ✅ CSRF protection on all forms
- ✅ XSS protection (htmlspecialchars)
- ✅ Session security
- ✅ Role-based access control
- ✅ Audit trail for all actions

---

## 🎯 POST-DEPLOYMENT CHECKLIST

### Security Checklist
- [ ] Changed default admin password
- [ ] Deleted install.php
- [ ] Deleted generate_hash.php
- [ ] Configured HTTPS/SSL
- [ ] Set up firewall rules
- [ ] Configured backup system
- [ ] Set proper file permissions
- [ ] Verified .env is protected
- [ ] Enabled error logging (not display)
- [ ] Set up monitoring

### Functionality Checklist
- [ ] Ran test_phase3_fixes.php (100% pass)
- [ ] Tested user login/logout
- [ ] Created test customer
- [ ] Created test application
- [ ] Uploaded test document
- [ ] Tested workflow transitions
- [ ] Tested disbursement creation
- [ ] Verified email notifications (if configured)
- [ ] Tested reports generation
- [ ] Checked all admin functions

### Documentation Checklist
- [ ] Updated database credentials docs
- [ ] Documented custom configurations
- [ ] Created admin user guide
- [ ] Set up monitoring dashboard
- [ ] Prepared incident response plan

---

## ✅ KHAI BÁO SẴN SÀNG

Khi tất cả checklist trên hoàn thành:

**Hệ thống LOS v3.0 sẵn sàng cho Production!** 🎉

---

**Deployed by:** _________________
**Date:** _________________
**Verified by:** _________________
**Signature:** _________________

---

**Version:** LOS v3.0 Production
**Last Updated:** 2025-10-30
**Status:** ✅ Production Ready
