# HƯỚNG DẪN KHẮC PHỤC NHANH - TOP 4 LỖ HỔNG NGHIÊM TRỌNG

## 🚨 CÁC LỖ HỔNG CẦN FIX NGAY HÔM NAY

---

## 1️⃣ CSRF PROTECTION (30 phút)

### Bước 1: Tạo file config/csrf.php
```php
<?php
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('CSRF token validation failed.');
    }
}
?>
```

### Bước 2: Thêm vào login.php (sau dòng 88)
```php
<?php require_once 'config/csrf.php'; ?>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <!-- existing form fields -->
</form>
```

### Bước 3: Verify ở đầu POST handler (dòng 13)
```php
if($_SERVER["REQUEST_METHOD"] == "POST"){
    verify_csrf_token($_POST['csrf_token'] ?? '');
    // existing code...
}
```

### Bước 4: Áp dụng cho TẤT CẢ forms
- create_application.php
- process_action.php (application_detail.php form)
- admin/manage_users.php
- admin/manage_customers.php
- admin/manage_products.php
- admin/manage_collaterals.php

---

## 2️⃣ FILE UPLOAD SECURITY (45 phút)

### Thay thế toàn bộ section upload trong process_action.php (dòng 68-96):

```php
if ($action == 'upload_document') {
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $document_name = trim($_POST['document_name']);

        // 1. Kiểm tra size (max 10MB)
        if ($_FILES['document_file']['size'] > 10 * 1024 * 1024) {
            header("location: application_detail.php?id=" . $application_id . "&error=file_too_large");
            exit;
        }

        // 2. Kiểm tra MIME type
        $allowed_types = [
            'application/pdf', 'image/jpeg', 'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['document_file']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_types)) {
            header("location: application_detail.php?id=" . $application_id . "&error=invalid_type");
            exit;
        }

        // 3. Kiểm tra extension
        $ext = strtolower(pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'])) {
            header("location: application_detail.php?id=" . $application_id . "&error=invalid_ext");
            exit;
        }

        // 4. Tên file random
        $new_name = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = 'uploads/' . $new_name;

        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target)) {
            $sql = "INSERT INTO application_documents (application_id, document_name, file_path, uploaded_by_id) VALUES (?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "issi", $application_id, $document_name, $new_name, $user_id);
                mysqli_stmt_execute($stmt);
            }
        }
    }
    header("location: application_detail.php?id=" . $application_id);
    exit;
}
```

### Tạo uploads/.htaccess
```apache
php_flag engine off
Options -Indexes
<FilesMatch "\.(pdf|jpg|jpeg|png|doc|docx)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
```

---

## 3️⃣ IDOR - ACCESS CONTROL (20 phút)

### Thêm vào application_detail.php sau dòng 19:

```php
$app = get_application_details($link, $application_id);
if (!$app) die("Không tìm thấy hồ sơ.");

// KIỂM TRA QUYỀN TRUY CẬP
$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];

if ($user_role !== 'Admin') {
    $has_access = false;

    // User được assign hoặc là người tạo
    if ($app['assigned_to_id'] == $user_id || $app['created_by_id'] == $user_id) {
        $has_access = true;
    }

    // Kiểm tra history
    $sql = "SELECT COUNT(*) as cnt FROM application_history WHERE application_id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $application_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        if ($row['cnt'] > 0) $has_access = true;
    }

    if (!$has_access) {
        die("Bạn không có quyền truy cập hồ sơ này. (403 Forbidden)");
    }
}

// Tiếp tục code hiện tại...
```

---

## 4️⃣ DATABASE CREDENTIALS (15 phút)

### Bước 1: Tạo file .env
```bash
DB_SERVER=localhost
DB_USERNAME=vnbc_los
DB_PASSWORD=4LyTKPdAY3ek6pZD3BEd
DB_NAME=vnbc_los
```

### Bước 2: Tạo .gitignore
```
.env
*.log
uploads/*
!uploads/.htaccess
```

### Bước 3: Sửa config/db.php
```php
<?php
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    define('DB_SERVER', $env['DB_SERVER']);
    define('DB_USERNAME', $env['DB_USERNAME']);
    define('DB_PASSWORD', $env['DB_PASSWORD']);
    define('DB_NAME', $env['DB_NAME']);
} else {
    die('Configuration file not found.');
}

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($link === false){
    error_log("DB connection failed: " . mysqli_connect_error());
    die("Hệ thống đang bảo trì.");
}

mysqli_set_charset($link, "utf8mb4");
?>
```

### Bước 4: Tạo .htaccess (root)
```apache
<Files ".env">
    Order allow,deny
    Deny from all
</Files>
```

---

## ✅ TESTING NHANH

### Test CSRF:
```bash
# Thử submit form không có csrf_token
curl -X POST http://localhost/los/login.php \
  -d "username=admin&password=test"
# Kết quả: Phải bị reject
```

### Test File Upload:
```bash
# Tạo file shell.php
echo '<?php system($_GET["cmd"]); ?>' > shell.php

# Thử upload
# Kết quả: Phải bị reject với "invalid_type"

# Upload file PDF hợp lệ
# Kết quả: Thành công, tên file random
```

### Test IDOR:
```bash
# Đăng nhập user A, access application của user B
# URL: application_detail.php?id=999
# Kết quả: 403 Forbidden nếu không có quyền
```

### Test .env Protection:
```bash
# Thử truy cập
curl http://localhost/los/.env
# Kết quả: 403 Forbidden
```

---

## 📝 CHECKLIST SAU KHI FIX

- [ ] Tất cả forms có CSRF token
- [ ] File upload chỉ chấp nhận PDF, JPG, PNG, DOC, DOCX
- [ ] Không thể upload file .php
- [ ] Không thể xem application của người khác (trừ Admin)
- [ ] File .env không truy cập được từ browser
- [ ] Test trên development trước khi deploy production
- [ ] Backup database và code trước khi deploy

---

## 🚀 DEPLOYMENT

```bash
# 1. Backup
mysqldump -u vnbc_los -p vnbc_los > backup_$(date +%Y%m%d).sql
tar -czf code_backup_$(date +%Y%m%d).tar.gz /path/to/los/

# 2. Deploy
git pull origin main
cp .env.example .env
nano .env  # Nhập DB credentials

# 3. Set permissions
chmod 600 .env
chmod 755 uploads/
chmod 644 uploads/.htaccess

# 4. Test
# Chạy các test ở trên

# 5. Monitor
tail -f /var/log/php/error.log
```

---

**Thời gian ước tính:** 2 giờ
**Risk level sau khi fix:** Giảm từ CRITICAL → MEDIUM
**Next steps:** Xem SECURITY_AUDIT_REPORT.md để fix các lỗ hổng HIGH và MEDIUM
