# LOS v3.0 Installation Guide

**Version:** 3.0.0
**Release Date:** 2025-10-30
**Author:** Claude AI
**Document Type:** Installation & Deployment Guide

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Pre-Installation Checklist](#pre-installation-checklist)
3. [Installation Methods](#installation-methods)
   - [Method 1: Web-Based Installer (Recommended)](#method-1-web-based-installer-recommended)
   - [Method 2: Manual Installation](#method-2-manual-installation)
4. [Post-Installation Steps](#post-installation-steps)
5. [Configuration](#configuration)
6. [Troubleshooting](#troubleshooting)
7. [Upgrade from v2.0](#upgrade-from-v20)
8. [Security Best Practices](#security-best-practices)

---

## System Requirements

### Minimum Requirements

| Component | Requirement | Recommended |
|-----------|-------------|-------------|
| **PHP Version** | 7.4+ | 8.0+ or 8.2+ |
| **MySQL/MariaDB** | 5.7+ / 10.2+ | 8.0+ / 10.6+ |
| **Web Server** | Apache 2.4+ / Nginx 1.18+ | Apache 2.4+ with mod_rewrite |
| **Disk Space** | 50 MB | 200 MB (with logs/uploads) |
| **Memory (PHP)** | 128 MB | 256 MB |
| **Max Upload Size** | 10 MB | 50 MB |

### Required PHP Extensions

- ✅ **mysqli** - MySQL database connectivity
- ✅ **session** - Session management
- ✅ **json** - JSON data processing
- ✅ **mbstring** - Multi-byte string functions
- ✅ **fileinfo** - File type detection
- ✅ **openssl** - Encryption (for HTTPS)

### Optional PHP Extensions

- 📦 **gd** or **imagick** - Image manipulation (for future features)
- 📦 **zip** - Archive handling
- 📦 **curl** - HTTP client (for API integration)

### Web Server Configuration

**Apache:**
- mod_rewrite enabled
- .htaccess files allowed (AllowOverride All)
- PHP running as mod_php or PHP-FPM

**Nginx:**
- PHP-FPM configured
- Custom rewrite rules (see nginx.conf.example)

---

## Pre-Installation Checklist

Before starting installation, ensure you have:

- [x] **Database Access**
  - MySQL/MariaDB server running
  - Database username and password
  - CREATE DATABASE permission

- [x] **Web Server Access**
  - Document root configured
  - Write permissions on installation directory
  - HTTPS configured (recommended for production)

- [x] **Domain/URL**
  - Domain name or IP address
  - DNS configured (if using domain)

- [x] **SMTP Server** (Optional, for email notifications)
  - SMTP host and credentials
  - Note: Email features not yet implemented in v3.0

- [x] **SSL Certificate** (Recommended for production)
  - SSL certificate installed
  - HTTPS enabled

---

## Installation Methods

### Method 1: Web-Based Installer (Recommended)

This is the easiest and recommended method for most users.

#### Step 1: Download and Extract

```bash
# Download LOS v3.0 package
wget https://github.com/your-repo/los-v3.0/archive/refs/heads/main.zip

# Extract files
unzip main.zip
cd los-v3.0-main

# Or clone from git
git clone https://github.com/your-repo/los-v3.0.git
cd los-v3.0
```

#### Step 2: Set File Permissions

```bash
# Grant write permissions to necessary directories
chmod 755 uploads/
chmod 755 migrations/
chmod 755 config/

# If on shared hosting
chmod 777 uploads/
chmod 777 .
```

#### Step 3: Access Web Installer

1. Open your web browser
2. Navigate to: `http://your-domain.com/install.php`
3. Follow the 5-step installation wizard

#### Step 4: Complete Installation Wizard

**Step 1: System Requirements Check**
- Installer automatically checks PHP version, extensions, and file permissions
- All items must show green checkmark
- If any item fails, fix the issue and refresh the page

**Step 2: Database Configuration**
- Enter your database credentials:
  - **Host:** `localhost` or `127.0.0.1`
  - **Username:** Your MySQL username (e.g., `root`)
  - **Password:** Your MySQL password
  - **Database Name:** `vnbc_los` (or custom name)
- Click "Test Connection"
- Database will be created automatically if it doesn't exist

**Step 3: Application Configuration**
- **App URL:** Full URL to your installation (e.g., `https://loan.example.com`)
- **Environment:** Choose `production` for live server, `development` for testing

**Step 4: Create Admin Account**
- **Full Name:** Your administrator name
- **Email:** Admin email address
- **Password:** Minimum 8 characters (use strong password!)
- **Confirm Password:** Re-enter password
- Click "Start Installation"

**Step 5: Installation Complete**
- Installation process will:
  - Create all database tables (15 tables)
  - Run 13 migrations
  - Insert initial data (roles, permissions, workflow steps)
  - Create `.env` configuration file
  - Create admin user account
  - Lock installer with `.installed` file
- Click "Go to Login" to access the system

#### Step 5: Post-Installation Security

```bash
# Recommended: Delete or rename installer
rm install.php
# Or rename
mv install.php install.php.bak

# Verify .htaccess protection
cat .htaccess

# Check file permissions
ls -la .env  # Should not be world-readable
```

---

### Method 2: Manual Installation

For advanced users who prefer command-line installation.

#### Step 1: Prepare Database

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE vnbc_los CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create database user (recommended)
CREATE USER 'losuser'@'localhost' IDENTIFIED BY 'your-secure-password';
GRANT ALL PRIVILEGES ON vnbc_los.* TO 'losuser'@'localhost';
FLUSH PRIVILEGES;

# Exit MySQL
EXIT;
```

#### Step 2: Import Database Schema

```bash
# Import main schema
mysql -u losuser -p vnbc_los < database.sql

# Run migrations
cd migrations/
mysql -u losuser -p vnbc_los < 001_create_facilities.sql
mysql -u losuser -p vnbc_los < 002_create_disbursements.sql
mysql -u losuser -p vnbc_los < 003_create_disbursement_conditions.sql
mysql -u losuser -p vnbc_los < 004_create_approval_conditions.sql
mysql -u losuser -p vnbc_los < 005_create_escalations.sql
mysql -u losuser -p vnbc_los < 006_create_workflow_steps.sql
mysql -u losuser -p vnbc_los < 007_create_disbursement_history.sql
mysql -u losuser -p vnbc_los < 008_create_document_history.sql
mysql -u losuser -p vnbc_los < 009_create_roles_permissions.sql
mysql -u losuser -p vnbc_los < 010_alter_credit_applications.sql
mysql -u losuser -p vnbc_los < 011_alter_application_collaterals.sql
mysql -u losuser -p vnbc_los < 012_alter_application_documents.sql
mysql -u losuser -p vnbc_los < 013_create_login_attempts.sql

# Or run all at once
mysql -u losuser -p vnbc_los < run_all_migrations.sql
```

#### Step 3: Create Configuration File

```bash
# Copy template
cp .env.example .env

# Edit configuration
nano .env
```

Edit `.env` file:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=losuser
DB_PASSWORD=your-secure-password
DB_NAME=vnbc_los

# Application Configuration
APP_URL=https://your-domain.com
ENVIRONMENT=production
```

#### Step 4: Set File Permissions

```bash
# Set directory permissions
chmod 755 uploads/
chmod 755 config/
chmod 755 includes/
chmod 755 admin/

# Set file permissions
chmod 644 .env
chmod 644 .htaccess
chmod 644 *.php

# Ensure .env is not publicly accessible
# (already protected by .htaccess)
```

#### Step 5: Create Admin User

```bash
# Generate password hash
php generate_hash.php

# Enter your desired password when prompted
# Copy the generated hash
```

Then insert admin user into database:

```sql
INSERT INTO users (username, email, password, full_name, role, is_active, created_at)
VALUES (
    'admin',
    'admin@vnbc.vn',
    'PASTE_HASH_HERE',
    'Administrator',
    'Admin',
    1,
    NOW()
);
```

#### Step 6: Create Lock File

```bash
# Create lock file to prevent installer access
echo "$(date)" > .installed
```

---

## Post-Installation Steps

### 1. Access the System

1. Navigate to your application URL: `https://your-domain.com`
2. You will be redirected to `login.php`
3. Login with admin credentials

### 2. Initial Configuration

After first login as Admin:

#### a) Configure Users
1. Go to **Admin Panel** → **Manage Users**
2. Create users for each role:
   - CVQHKH (Relationship Managers)
   - CVTĐ (Credit Analysts)
   - CPD (Credit Officers)
   - GDK (General Director Credit)
   - Kiểm soát (Credit Controllers)
   - Thủ quỹ (Cashiers)

#### b) Configure Customers
1. Go to **Admin Panel** → **Manage Customers**
2. Add your customers/borrowers

#### c) Configure Products
1. Go to **Admin Panel** → **Manage Products**
2. Define your loan products:
   - Short-term working capital
   - Long-term investment
   - Trade finance
   - etc.

#### d) Configure Collateral Types
1. Go to **Admin Panel** → **Manage Collaterals**
2. Add collateral categories:
   - Real estate
   - Vehicles
   - Equipment
   - Inventory
   - etc.

#### e) Configure Document Definitions
1. Go to **Admin Panel** → **Manage Document Definitions**
2. Define required documents:
   - Identity documents (CMND, CCCD, Passport)
   - Financial documents (Financial statements, tax returns)
   - Legal documents (Business licenses, contracts)
   - Collateral documents (Property deeds, vehicle registrations)

### 3. Test Workflows

#### Test Credit Application Workflow:
1. Login as **CVQHKH** role
2. Create a new credit application
3. Upload documents
4. Submit for review
5. Login as **CVTĐ** → Review application
6. Login as **CPD/GDK** → Approve/Reject
7. Verify workflow transitions

#### Test Disbursement Workflow:
1. Login as **CVQHKH** with an approved application
2. Activate facility (complete legal requirements)
3. Create disbursement request
4. Login as **Kiểm soát** → Check conditions
5. Login as **CPD/GDK** → Approve disbursement
6. Login as **Thủ quỹ** → Execute disbursement
7. Verify balance updates

### 4. Configure Backups

```bash
# Create backup script
nano /usr/local/bin/los-backup.sh
```

Example backup script:

```bash
#!/bin/bash
# LOS v3.0 Backup Script

BACKUP_DIR="/backups/los"
DB_USER="losuser"
DB_PASS="your-password"
DB_NAME="vnbc_los"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Backup uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz uploads/

# Backup .env
cp .env $BACKUP_DIR/env_$DATE

# Delete backups older than 30 days
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

Set up cron job:

```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * /usr/local/bin/los-backup.sh
```

---

## Configuration

### Environment Variables (.env)

All configuration is stored in the `.env` file:

```env
# Database Configuration
DB_HOST=localhost          # MySQL host
DB_USER=losuser           # MySQL username
DB_PASSWORD=secret        # MySQL password
DB_NAME=vnbc_los          # Database name

# Application Configuration
APP_URL=https://loan.vnbc.vn    # Full application URL
ENVIRONMENT=production          # production, development, staging
```

### Session Configuration (config/session.php)

Default settings:

```php
SESSION_TIMEOUT = 1800;              // 30 minutes inactivity
SESSION_ABSOLUTE_TIMEOUT = 28800;    // 8 hours absolute
```

To modify, edit `config/session.php`:

```php
define('SESSION_TIMEOUT', 3600);              // 1 hour inactivity
define('SESSION_ABSOLUTE_TIMEOUT', 43200);    // 12 hours absolute
```

### Rate Limiting (config/rate_limit.php)

Default settings:

```php
MAX_LOGIN_ATTEMPTS = 5;     // Failed login attempts
LOCKOUT_DURATION = 900;     // 15 minutes lockout
```

### Upload Limits (uploads/.htaccess)

Default allowed file types:

```apache
# Allowed extensions
.pdf, .jpg, .jpeg, .png, .doc, .docx, .xls, .xlsx
```

To modify, edit `uploads/.htaccess`:

```apache
<FilesMatch "\.(php|phtml|php3|php4|php5|php7|phps|cgi|pl|exe|sh)$">
    Deny from all
</FilesMatch>

# Allow only specific file types
<FilesMatch "\.(pdf|jpg|jpeg|png|doc|docx|xls|xlsx|ppt|pptx|txt)$">
    Allow from all
</FilesMatch>
```

---

## Troubleshooting

### Common Issues

#### 1. Database Connection Error

**Error:** "Không thể kết nối database"

**Solutions:**
```bash
# Check MySQL is running
systemctl status mysql
# or
systemctl status mariadb

# Verify credentials
mysql -u losuser -p vnbc_los

# Check .env file
cat .env

# Verify host (try 127.0.0.1 instead of localhost)
```

#### 2. Permission Denied Errors

**Error:** "Warning: fopen(): Permission denied"

**Solutions:**
```bash
# Fix uploads directory
chmod 755 uploads/
chown www-data:www-data uploads/

# Fix .env file
chmod 644 .env
chown www-data:www-data .env

# On shared hosting
chmod 777 uploads/
```

#### 3. Session Not Working

**Error:** "Session could not be started"

**Solutions:**
```bash
# Check session directory
php -i | grep session.save_path

# Create session directory if needed
mkdir /var/lib/php/sessions
chmod 777 /var/lib/php/sessions

# Or modify php.ini
session.save_path = "/tmp"
```

#### 4. CSRF Token Validation Failed

**Error:** "CSRF token validation failed"

**Solutions:**
- Clear browser cookies
- Ensure cookies are enabled
- Check if session is working
- Verify `session.cookie_samesite` is set correctly

#### 5. Charts Not Displaying

**Error:** Charts not rendering on dashboard

**Solutions:**
- Ensure internet connection (Chart.js loads from CDN)
- Check browser console for JavaScript errors
- Verify Chart.js CDN is accessible:
  ```
  https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js
  ```
- Try clearing browser cache

#### 6. File Upload Fails

**Error:** "Lỗi tải file lên"

**Solutions:**
```bash
# Check upload_max_filesize
php -i | grep upload_max_filesize

# Edit php.ini
upload_max_filesize = 50M
post_max_size = 50M

# Restart web server
systemctl restart apache2
# or
systemctl restart nginx && systemctl restart php-fpm
```

#### 7. Installer Already Installed Error

**Error:** "Application is already installed"

**Solutions:**
```bash
# To reinstall, delete lock file
rm .installed

# Then access install.php again
```

---

## Upgrade from v2.0

If you have an existing LOS v2.0 installation:

### Backup First!

```bash
# Backup v2.0 database
mysqldump -u root -p vnbc_los > vnbc_los_v2_backup_$(date +%Y%m%d).sql

# Backup v2.0 files
tar -czf los_v2_backup_$(date +%Y%m%d).tar.gz /path/to/los/

# Backup uploads
cp -r /path/to/los/uploads /backup/uploads_v2
```

### Migration Steps

1. **Run Migrations Only** (Don't run database.sql)

```bash
cd migrations/
mysql -u root -p vnbc_los < run_all_migrations.sql
```

2. **Update Configuration**

```bash
# Create .env file
cp .env.example .env
nano .env

# Add your existing database credentials
DB_HOST=localhost
DB_USER=your_user
DB_PASSWORD=your_password
DB_NAME=vnbc_los
```

3. **Copy New Files**

```bash
# Copy new PHP files (without overwriting database.sql)
cp -n new_los_v3/* /path/to/los/

# Update includes/
cp -r new_los_v3/includes/* /path/to/los/includes/

# Update config/
cp -r new_los_v3/config/* /path/to/los/config/

# Update admin/
cp -r new_los_v3/admin/* /path/to/los/admin/
```

4. **Test Upgrade**

- Login to system
- Verify existing data is intact
- Test new features (facilities, disbursements, charts)
- Check for any errors in PHP error log

5. **Rollback if Needed**

```bash
# If upgrade fails, rollback
mysql -u root -p vnbc_los < migrations/rollback_all_migrations.sql

# Restore v2.0 backup
mysql -u root -p vnbc_los < vnbc_los_v2_backup_YYYYMMDD.sql
```

---

## Security Best Practices

### 1. HTTPS Only

```apache
# In .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 2. Strong Passwords

- Admin password: Minimum 12 characters, mixed case, numbers, symbols
- User passwords: Minimum 8 characters (enforced by system)
- Change default admin password after installation

### 3. File Permissions

```bash
# Restrictive permissions (recommended)
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 755 uploads/
chmod 600 .env
```

### 4. Database Security

```sql
-- Create dedicated database user (not root)
CREATE USER 'losuser'@'localhost' IDENTIFIED BY 'complex-password';
GRANT ALL PRIVILEGES ON vnbc_los.* TO 'losuser'@'localhost';

-- Remove test databases
DROP DATABASE IF EXISTS test;

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';
FLUSH PRIVILEGES;
```

### 5. Hide Installer

```bash
# Delete installer after installation
rm install.php

# Or password-protect it
htpasswd -c /etc/apache2/.htpasswd admin
```

### 6. Regular Updates

```bash
# Keep PHP updated
apt update && apt upgrade php

# Keep MySQL updated
apt update && apt upgrade mysql-server

# Monitor security advisories
```

### 7. Monitoring & Logging

```bash
# Enable PHP error logging
# Edit php.ini
log_errors = On
error_log = /var/log/php/error.log

# Monitor access logs
tail -f /var/log/apache2/access.log

# Monitor error logs
tail -f /var/log/apache2/error.log
```

### 8. Firewall Configuration

```bash
# Allow only HTTP, HTTPS, and SSH
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable

# Block direct MySQL access from outside
ufw deny 3306/tcp
```

### 9. Regular Backups

- Daily database backups
- Weekly full system backups
- Store backups off-site
- Test restore process quarterly

### 10. Security Headers

Already configured in `.htaccess`:

```apache
Header set X-Frame-Options "DENY"
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline';"
```

---

## Support & Documentation

### Documentation Files

- **README.md** - Project overview
- **INSTALLATION_GUIDE.md** (this file) - Installation instructions
- **PHASE2_SUMMARY.md** - Phase 2 features documentation
- **PHASE3_SUMMARY.md** - Phase 3 UI/UX documentation
- **PHASE4_SUMMARY.md** - Phase 4 dashboard & reporting
- **MIGRATION_TEST_GUIDE.md** - Migration testing procedures
- **SECURITY_AUDIT_REPORT.md** - Security analysis

### Getting Help

- **Issues:** Report bugs and issues on GitHub
- **Documentation:** Check all *_SUMMARY.md files
- **Community:** Join our support forum (if available)

### Version Information

- **Current Version:** 3.0.0
- **Release Date:** 2025-10-30
- **PHP Support:** 7.4 - 8.2
- **Database:** MySQL 5.7+ / MariaDB 10.2+

---

**Installation Complete!** 🎉

Your LOS v3.0 system is now ready for use. Refer to PHASE2_SUMMARY.md, PHASE3_SUMMARY.md, and PHASE4_SUMMARY.md for detailed feature documentation.
