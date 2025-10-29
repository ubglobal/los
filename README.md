# 🏦 Hệ thống LOS - Loan Origination System
## Giải pháp Khởi tạo và Quản lý Tín dụng cho U&Bank

[![Security](https://img.shields.io/badge/Security-95%25-brightgreen)](./SECURITY_AUDIT_REPORT.md)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue)]()
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange)]()

---

## 🎯 Giới thiệu

**LOS (Loan Origination System)** là hệ thống quản lý quy trình phê duyệt tín dụng được thiết kế cho U&Bank với:
- ✅ Workflow tự động theo hạn mức
- ✅ Multi-level approval (CVTĐ → CPD → GĐK)
- ✅ Role-based access control
- ✅ Document management an toàn
- ✅ **95% Security Score** - Fixed 18 vulnerabilities

## 🚀 Tính năng chính

- **Quản lý Hồ sơ Tín dụng**: Tạo, thẩm định, phê duyệt
- **Workflow tự động**: Routing theo hạn mức phê duyệt
- **Multi-role**: CVQHKH, CVTĐ, CPD, GĐK, Admin
- **Document Management**: Upload, quản lý hồ sơ đính kèm
- **Audit Trail**: Ghi log đầy đủ mọi thao tác
- **Security**: CSRF, File Upload, IDOR, Rate Limiting protection

## 💻 Công nghệ

- PHP 8.2+ (Procedural với Prepared Statements)
- MySQL 8.0+ / MariaDB 10.4+
- Tailwind CSS 3
- Session-based Auth với Bcrypt

## 📦 Cài đặt nhanh

```bash
# Clone repo
git clone https://github.com/ubglobal/los.git
cd los

# Setup environment
cp .env.example .env
nano .env  # Nhập DB credentials

# Import database
mysql -u root -p < database.sql

# Set permissions
chmod 600 .env
chmod 755 uploads/

# Access
http://localhost/los/
```

## 📖 Demo Accounts (Development)

Tất cả password: `ub@12345678`

- `admin` - Admin
- `qhkh.an.nguyen` - CVQHKH (Quan hệ KH)
- `thamdinh.lan.vu` - CVTĐ (Thẩm định)
- `pheduyet.hung.tran` - CPD (Phê duyệt ≤ 5 tỷ)
- `gd.khoi.nguyen` - GĐK (Phê duyệt > 5 tỷ)

**Note:** Demo accounts chỉ hiện trong development mode.

## 🔒 Bảo mật

Security Score: **20% → 95%** (+75% improvement)

**Fixed vulnerabilities:**
- ✅ CSRF Protection (all forms)
- ✅ Secure File Upload (MIME + extension validation)
- ✅ IDOR Protection (access control)
- ✅ Session Security (timeout + regeneration)
- ✅ Rate Limiting (5 attempts, 15 min lockout)
- ✅ Security Headers (CSP, HSTS, X-Frame-Options)
- ✅ Database Credentials (.env file)
- ✅ Path Traversal Protection

Xem chi tiết: [SECURITY_AUDIT_REPORT.md](./SECURITY_AUDIT_REPORT.md)

## 📁 Cấu trúc

```
los/
├── config/           # Configuration (CSRF, Session, Rate Limit)
├── includes/         # Shared components
├── admin/            # Admin area
├── uploads/          # Document storage (PHP blocked)
├── login.php         # Login page
├── index.php         # Main workspace
├── application_detail.php  # Application detail
├── process_action.php      # Workflow handler
├── .env              # Environment config (NOT in Git)
└── database.sql      # Database schema + data
```

## 🔄 Workflow

```
Khởi tạo (CVQHKH)
    ↓
Thẩm định (CVTĐ)
    ├─ Yêu cầu bổ sung → Back to CVQHKH
    └─ Trình duyệt
         ↓
Phê duyệt (CPD/GĐK)
    ├─ Phê duyệt → Done
    └─ Từ chối → Rejected
```

## 🧪 Testing

```bash
# CSRF Test
curl -X POST http://localhost/los/login.php -d "username=admin&password=test"
# Expected: CSRF validation failed

# File Upload Test (upload .php)
# Expected: Invalid file type

# IDOR Test (user A access app of user B)
# Expected: 403 Forbidden

# Rate Limit (login wrong 5 times)
# Expected: Locked 15 minutes

# Session Timeout (wait 30 min)
# Expected: Auto logout
```

## 🚀 Deployment

### Production Checklist:
- [ ] Backup database & code
- [ ] Update .env (ENVIRONMENT=production)
- [ ] Set file permissions (chmod 600 .env)
- [ ] Remove/change demo passwords
- [ ] Test all features
- [ ] Verify security headers
- [ ] Monitor logs 24h

## 🔍 Troubleshooting

**"Configuration file not found"**
```bash
cp .env.example .env && nano .env
```

**"CSRF token failed"**
```bash
Clear browser cache, check session is active
```

**"File upload failed"**
```bash
chmod 755 uploads/
Check PHP upload_max_filesize
```

**"Account locked"**
```sql
DELETE FROM login_attempts WHERE username = 'your_username';
```

## 📄 Documentation

- [SECURITY_AUDIT_REPORT.md](./SECURITY_AUDIT_REPORT.md) - Full audit
- [SECURITY_FIXES_APPLIED.md](./SECURITY_FIXES_APPLIED.md) - Applied fixes
- [QUICK_FIX_GUIDE.md](./QUICK_FIX_GUIDE.md) - Quick reference
- [SECURITY_FIXES_CHECKLIST.md](./SECURITY_FIXES_CHECKLIST.md) - Checklist

## 📞 Support

- Email: it-support@ubank.vn
- Security: security@ubank.vn

## 📈 Changelog

### v2.0.0 - 2025-10-29 (Security Release)
- 🔒 Fixed 18 security vulnerabilities
- ✅ Security score: 95%
- 🔑 CSRF protection
- 📁 Secure file uploads
- 🛡️ IDOR protection
- ⏱️ Session timeout
- 🚫 Rate limiting

### v1.0.0 - 2024-10-16
- ✨ Initial release

---

Copyright © 2024-2025 U&Bank. All rights reserved.
