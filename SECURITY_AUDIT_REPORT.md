# BÁO CÁO KIỂM TOÁN BẢO MẬT HỆ THỐNG LOS
## Loan Origination System - U&Bank

**Ngày kiểm toán:** 29/10/2025
**Phiên bản hệ thống:** Production
**Công nghệ:** PHP 8.2, MySQL/MariaDB 10.4.27, Tailwind CSS 3
**Người thực hiện:** Claude Code Security Audit

---

## TÓM TẮT ĐIỀU HÀNH (EXECUTIVE SUMMARY)

Hệ thống Loan Origination System (LOS) của U&Bank đã được kiểm toán toàn diện về mặt bảo mật. Hệ thống được xây dựng bằng PHP thuần (không sử dụng framework) với MySQL database, quản lý quy trình phê duyệt tín dụng từ khởi tạo đến phê duyệt cuối cùng.

### Kết quả tổng quan:
- **Lỗ hổng Nghiêm trọng (Critical):** 4
- **Lỗ hổng Cao (High):** 6
- **Lỗ hổng Trung bình (Medium):** 5
- **Lỗ hổng Thấp (Low):** 3
- **Thực hành tốt được phát hiện:** 7

### Đánh giá chung:
Hệ thống có **một số điểm mạnh về bảo mật** như sử dụng prepared statements để chống SQL Injection, mã hóa mật khẩu bằng bcrypt, và có cơ chế phân quyền dựa trên vai trò. Tuy nhiên, hệ thống **tồn tại nhiều lỗ hổng nghiêm trọng** cần được khắc phục ngay lập tức trước khi đưa vào môi trường production thực tế, đặc biệt là các vấn đề liên quan đến CSRF, file upload, và quản lý phiên.

---

## 🔴 CÁC LỖ HỔNG NGHIÊM TRỌNG (CRITICAL)

### 1. **Thiếu CSRF Protection trên toàn bộ hệ thống**

**Mức độ:** 🔴 CRITICAL
**CVSS Score:** 8.1 (High)
**CWE:** CWE-352 (Cross-Site Request Forgery)

**Mô tả:**
Tất cả các form trong hệ thống (login, tạo hồ sơ, phê duyệt, upload file, quản lý user) đều không có CSRF token để xác thực request.

**File bị ảnh hưởng:**
- `login.php` (dòng 88)
- `create_application.php` (dòng 67)
- `process_action.php` (toàn bộ file)
- `application_detail.php` (dòng 50)
- `admin/manage_users.php` (dòng 100)
- Tất cả các file admin khác

**Tác động:**
- Kẻ tấn công có thể lừa người dùng đã đăng nhập thực hiện các hành động không mong muốn như:
  - Phê duyệt khoản vay trái phép
  - Tạo hồ sơ tín dụng giả mạo
  - Thay đổi thông tin người dùng
  - Upload file độc hại
  - Xóa dữ liệu quan trọng

**Ví dụ khai thác:**
```html
<!-- Attacker tạo trang web độc hại -->
<form action="https://los.ubank.vn/process_action.php" method="POST">
    <input type="hidden" name="application_id" value="123">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="comment" value="Approved by attacker">
</form>
<script>document.forms[0].submit();</script>
```

**Khuyến nghị khắc phục:**

**File:** `config/csrf.php` (TẠO MỚI)
```php
<?php
// File: config/csrf.php
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('CSRF token validation failed. Possible CSRF attack detected.');
    }
}
?>
```

**Sử dụng trong form:**
```php
<?php require_once 'config/csrf.php'; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <!-- Các trường khác -->
</form>
```

**Xác thực khi xử lý POST:**
```php
// Thêm vào đầu mỗi file xử lý POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
}
```

---

### 2. **File Upload Vulnerability - Không kiểm tra loại file**

**Mức độ:** 🔴 CRITICAL
**CVSS Score:** 9.8 (Critical)
**CWE:** CWE-434 (Unrestricted Upload of File with Dangerous Type)

**Mô tả:**
Hệ thống cho phép upload file mà không kiểm tra loại file, kích thước, hoặc nội dung. Chỉ sanitize tên file nhưng không validate MIME type.

**File:** `process_action.php` (dòng 68-96)

**Code hiện tại:**
```php
if ($action == 'upload_document') {
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $document_name = trim($_POST['document_name']);
        $upload_dir = 'uploads/';
        $file_name = time() . '_' . basename(preg_replace("/[^a-zA-Z0-9.\-_]/", "_", $_FILES['document_file']['name']));
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target_file)) {
            // Save to database...
        }
    }
}
```

**Tác động:**
- Upload shell PHP để chiếm quyền điều khiển server
- Upload file .htaccess để thay đổi cấu hình server
- Upload file HTML chứa JavaScript độc hại (Stored XSS)
- Upload file .zip chứa malware
- Lấp đầy ổ đĩa server (DoS)

**Ví dụ khai thác:**
```php
// File shell.php được upload lên uploads/
<?php system($_GET['cmd']); ?>
// Truy cập: https://los.ubank.vn/uploads/1234567890_shell.php?cmd=whoami
```

**Khuyến nghị khắc phục:**
```php
// File: process_action.php (thay thế section upload)
if ($action == 'upload_document') {
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $document_name = trim($_POST['document_name']);

        // 1. Kiểm tra kích thước (max 10MB)
        $max_size = 10 * 1024 * 1024;
        if ($_FILES['document_file']['size'] > $max_size) {
            header("location: application_detail.php?id=" . $application_id . "&error=file_too_large");
            exit;
        }

        // 2. Whitelist MIME types
        $allowed_types = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['document_file']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            header("location: application_detail.php?id=" . $application_id . "&error=invalid_file_type");
            exit;
        }

        // 3. Kiểm tra extension
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
        $file_extension = strtolower(pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            header("location: application_detail.php?id=" . $application_id . "&error=invalid_extension");
            exit;
        }

        // 4. Tạo tên file ngẫu nhiên, không sử dụng tên gốc
        $new_filename = bin2hex(random_bytes(16)) . '.' . $file_extension;
        $upload_dir = 'uploads/';
        $target_file = $upload_dir . $new_filename;

        // 5. Di chuyển file
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target_file)) {
            // 6. Lưu metadata vào database
            $sql = "INSERT INTO application_documents (application_id, document_name, file_path, uploaded_by_id, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                $file_size = $_FILES['document_file']['size'];
                mysqli_stmt_bind_param($stmt, "isssis", $application_id, $document_name, $new_filename, $user_id, $file_size, $mime_type);
                mysqli_stmt_execute($stmt);
            }
        }
    }
}
```

**Bảo vệ thư mục uploads:**

**File:** `uploads/.htaccess` (TẠO MỚI)
```apache
# Chặn thực thi PHP trong thư mục uploads
php_flag engine off

# Chỉ cho phép download file
<FilesMatch "\.(pdf|jpg|jpeg|png|doc|docx|xls|xlsx)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Chặn truy cập trực tiếp
Options -Indexes
```

---

### 3. **Insecure Direct Object Reference (IDOR) - Thiếu kiểm tra quyền truy cập**

**Mức độ:** 🔴 CRITICAL
**CVSS Score:** 8.5 (High)
**CWE:** CWE-639 (Authorization Bypass Through User-Controlled Key)

**Mô tả:**
File `application_detail.php` cho phép truy cập bất kỳ hồ sơ nào chỉ với tham số `id`, không kiểm tra xem user có quyền xem hồ sơ đó không.

**File:** `application_detail.php` (dòng 12-19)

**Code hiện tại:**
```php
$application_id = $_GET['id'] ?? null;
if (!$application_id) {
    header("location: index.php");
    exit;
}

$app = get_application_details($link, $application_id);
if (!$app) die("Không tìm thấy hồ sơ.");
```

**Tác động:**
- Người dùng có thể xem, sửa, phê duyệt hồ sơ tín dụng của người khác
- Nhân viên chi nhánh có thể truy cập hồ sơ của chi nhánh khác
- Rò rỉ thông tin khách hàng nhạy cảm (CCCD, số điện thoại, thông tin tài chính)
- Vi phạm GDPR/PDPA nếu áp dụng

**Ví dụ khai thác:**
```
1. User A được assign hồ sơ ID 1
2. User A thay đổi URL: application_detail.php?id=2
3. User A có thể xem hồ sơ ID 2 mặc dù không được assign
4. User A có thể duyệt qua tất cả hồ sơ từ id=1 đến id=1000
```

**Khuyến nghị khắc phục:**
```php
// File: application_detail.php (thay thế dòng 12-34)
$application_id = $_GET['id'] ?? null;
if (!$application_id) {
    header("location: index.php");
    exit;
}

$app = get_application_details($link, $application_id);
if (!$app) die("Không tìm thấy hồ sơ.");

// KIỂM TRA QUYỀN TRUY CẬP
$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];
$user_branch = $_SESSION['branch'];

// Admin có thể xem tất cả
if ($user_role !== 'Admin') {
    // Kiểm tra user có được assign hồ sơ này không
    $has_access = false;

    // 1. User hiện tại được assign
    if ($app['assigned_to_id'] == $user_id) {
        $has_access = true;
    }

    // 2. User là người tạo hồ sơ
    if ($app['created_by_id'] == $user_id) {
        $has_access = true;
    }

    // 3. Kiểm tra trong lịch sử xem user có liên quan không
    $sql = "SELECT COUNT(*) as cnt FROM application_history WHERE application_id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $application_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        if ($row['cnt'] > 0) {
            $has_access = true;
        }
    }

    // 4. Kiểm tra branch nếu cùng chi nhánh (tùy chọn)
    // Uncomment nếu muốn user cùng chi nhánh có thể xem
    // $creator = get_user_by_id($link, $app['created_by_id']);
    // if ($creator['branch'] == $user_branch) {
    //     $has_access = true;
    // }

    if (!$has_access) {
        die("Bạn không có quyền truy cập hồ sơ này. (Error: 403 Forbidden)");
    }
}

// Tiếp tục xử lý...
$customer = get_customer_by_id($link, $app['customer_id']);
$is_editable = ($user_role == 'CVQHKH' && ($app['stage'] == 'Khởi tạo hồ sơ tín dụng' || $app['stage'] == 'Yêu cầu bổ sung') && ($app['assigned_to_id'] == $user_id || $app['created_by_id'] == $user_id));
```

---

### 4. **Database Credentials Hardcoded - Thông tin nhạy cảm lộ rõ**

**Mức độ:** 🔴 CRITICAL
**CVSS Score:** 9.1 (Critical)
**CWE:** CWE-798 (Use of Hard-coded Credentials)

**Mô tả:**
Thông tin đăng nhập database được lưu trực tiếp trong code với mật khẩu rất mạnh nhưng không được bảo vệ.

**File:** `config/db.php` (dòng 6-9)

**Code hiện tại:**
```php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'vnbc_los');
define('DB_PASSWORD', '4LyTKPdAY3ek6pZD3BEd');  // Mật khẩu production!
define('DB_NAME', 'vnbc_los');
```

**Tác động:**
- Nếu attacker có quyền đọc source code (qua LFI, backup files, Git leak, etc.), họ có toàn quyền truy cập database
- Có thể đọc/sửa/xóa tất cả dữ liệu khách hàng
- Đánh cắp password hash của tất cả users
- Chèn dữ liệu giả mạo vào hệ thống

**Khuyến nghị khắc phục:**

**1. Tạo file .env (không commit vào Git):**
```bash
# File: .env
DB_SERVER=localhost
DB_USERNAME=vnbc_los
DB_PASSWORD=4LyTKPdAY3ek6pZD3BEd
DB_NAME=vnbc_los
```

**2. Thêm vào .gitignore:**
```
# File: .gitignore
.env
config/db.php
*.log
uploads/*
!uploads/.htaccess
```

**3. Sửa config/db.php:**
```php
<?php
// File: config/db.php
// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    define('DB_SERVER', $env['DB_SERVER']);
    define('DB_USERNAME', $env['DB_USERNAME']);
    define('DB_PASSWORD', $env['DB_PASSWORD']);
    define('DB_NAME', $env['DB_NAME']);
} else {
    die('Configuration file not found. Please create .env file.');
}

// Kết nối với error handling tốt hơn
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($link === false){
    // Không hiển thị chi tiết lỗi trong production
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Hệ thống đang bảo trì. Vui lòng thử lại sau.");
}

mysqli_set_charset($link, "utf8mb4");  // utf8mb4 tốt hơn utf8
?>
```

**4. Bảo vệ file .env trên server:**
```apache
# File: .htaccess (root directory)
<Files ".env">
    Order allow,deny
    Deny from all
</Files>
```

---

## 🟠 CÁC LỖ HỔNG MỨC CAO (HIGH)

### 5. **Session Fixation - Không tạo lại Session ID sau khi đăng nhập**

**Mức độ:** 🟠 HIGH
**CVSS Score:** 7.5 (High)
**CWE:** CWE-384 (Session Fixation)

**Mô tả:**
Sau khi đăng nhập thành công, hệ thống không gọi `session_regenerate_id()` để tạo session ID mới.

**File:** `login.php` (dòng 39-47)

**Tác động:**
- Kẻ tấn công có thể cố định session ID của nạn nhân trước khi đăng nhập
- Sau khi nạn nhân đăng nhập, attacker sử dụng session ID đó để truy cập

**Khuyến nghị khắc phục:**
```php
// File: login.php (sau dòng 39)
if(password_verify($password, $hashed_password)){
    // QUAN TRỌNG: Tạo session ID mới
    session_regenerate_id(true);

    $_SESSION["loggedin"] = true;
    $_SESSION["id"] = $id;
    $_SESSION["username"] = $username;
    $_SESSION["full_name"] = $full_name;
    $_SESSION["role"] = $role;
    $_SESSION["branch"] = $branch;

    // Thêm thông tin bảo mật
    $_SESSION["user_ip"] = $_SERVER['REMOTE_ADDR'];
    $_SESSION["user_agent"] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION["login_time"] = time();

    header("location: index.php");
}
```

---

### 6. **Không có Session Timeout - Session tồn tại vô thời hạn**

**Mức độ:** 🟠 HIGH
**CVSS Score:** 6.5 (Medium)
**CWE:** CWE-613 (Insufficient Session Expiration)

**Mô tả:**
Session không có cơ chế timeout, người dùng có thể đăng nhập một lần và session tồn tại mãi mãi (cho đến khi đóng browser hoặc logout thủ công).

**Khuyến nghị khắc phục:**

**File:** `config/session.php` (TẠO MỚI)
```php
<?php
// File: config/session.php
// Cấu hình session timeout

// Session timeout sau 30 phút không hoạt động
define('SESSION_TIMEOUT', 1800); // 30 phút = 1800 giây

// Session timeout tuyệt đối sau 8 giờ (1 ca làm việc)
define('SESSION_ABSOLUTE_TIMEOUT', 28800); // 8 giờ = 28800 giây

function check_session_timeout() {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        return;
    }

    $current_time = time();

    // Kiểm tra timeout không hoạt động
    if (isset($_SESSION['last_activity']) && ($current_time - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("location: login.php?timeout=1");
        exit;
    }

    // Kiểm tra timeout tuyệt đối
    if (isset($_SESSION['login_time']) && ($current_time - $_SESSION['login_time'] > SESSION_ABSOLUTE_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("location: login.php?timeout=absolute");
        exit;
    }

    // Cập nhật thời gian hoạt động cuối
    $_SESSION['last_activity'] = $current_time;

    // Tùy chọn: Kiểm tra IP và User-Agent để phát hiện session hijacking
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        session_unset();
        session_destroy();
        header("location: login.php?error=session_hijack");
        exit;
    }
}
?>
```

**Thêm vào đầu mỗi file protected:**
```php
<?php
session_start();
require_once "config/session.php";
check_session_timeout();

// Phần còn lại của code...
```

---

### 7. **Path Traversal Risk - Xóa file không an toàn**

**Mức độ:** 🟠 HIGH
**CVSS Score:** 7.5 (High)
**CWE:** CWE-22 (Path Traversal)

**Mô tả:**
Khi xóa file, hệ thống sử dụng đường dẫn từ database mà không kiểm tra xem file có nằm trong thư mục uploads hay không.

**File:** `process_action.php` (dòng 42-44)

**Code hiện tại:**
```php
if (file_exists('uploads/' . $doc['file_path'])) {
    unlink('uploads/' . $doc['file_path']);
}
```

**Tác động:**
- Nếu attacker có thể chèn `../../config/db.php` vào `file_path`, họ có thể xóa file cấu hình
- Xóa file hệ thống quan trọng

**Khuyến nghị khắc phục:**
```php
// File: process_action.php (thay thế dòng 36-46)
if ($item_type === 'document') {
    $sql_get_file = "SELECT file_path FROM application_documents WHERE id = ? AND application_id = ?";
    if($stmt_get_file = mysqli_prepare($link, $sql_get_file)) {
        mysqli_stmt_bind_param($stmt_get_file, "ii", $item_id, $application_id);
        mysqli_stmt_execute($stmt_get_file);
        $result = mysqli_stmt_get_result($stmt_get_file);
        if($doc = mysqli_fetch_assoc($result)) {
            // Chỉ lấy basename để tránh path traversal
            $safe_filename = basename($doc['file_path']);
            $full_path = realpath('uploads/' . $safe_filename);
            $upload_dir = realpath('uploads/');

            // Kiểm tra file có nằm trong thư mục uploads không
            if ($full_path && $upload_dir && strpos($full_path, $upload_dir) === 0) {
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
            } else {
                error_log("Attempted path traversal: " . $doc['file_path']);
            }
        }
    }
}
```

---

### 8. **No Rate Limiting on Login - Không giới hạn số lần đăng nhập sai**

**Mức độ:** 🟠 HIGH
**CVSS Score:** 7.3 (High)
**CWE:** CWE-307 (Improper Restriction of Excessive Authentication Attempts)

**Mô tả:**
Không có cơ chế giới hạn số lần đăng nhập sai, cho phép brute-force attack.

**File:** `login.php`

**Tác động:**
- Brute force password của tài khoản admin
- DDoS bằng cách spam login requests
- Tự động dò tìm username hợp lệ

**Khuyến nghị khắc phục:**

**File:** `config/rate_limit.php` (TẠO MỚI)
```php
<?php
// File: config/rate_limit.php
function check_login_attempts($username, $ip) {
    global $link;

    // Tạo bảng nếu chưa có
    $sql_create = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50),
        ip_address VARCHAR(45),
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_ip (ip_address),
        INDEX idx_time (attempt_time)
    ) ENGINE=InnoDB";
    mysqli_query($link, $sql_create);

    $max_attempts = 5;
    $lockout_time = 900; // 15 phút

    // Đếm số lần thất bại trong 15 phút qua
    $sql = "SELECT COUNT(*) as attempts FROM login_attempts
            WHERE (username = ? OR ip_address = ?)
            AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssi", $username, $ip, $lockout_time);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        if ($row['attempts'] >= $max_attempts) {
            // Tính thời gian còn lại phải đợi
            $sql_time = "SELECT TIMESTAMPDIFF(SECOND, MAX(attempt_time), DATE_ADD(MAX(attempt_time), INTERVAL ? SECOND)) as wait_time
                         FROM login_attempts WHERE username = ? OR ip_address = ?";
            if ($stmt_time = mysqli_prepare($link, $sql_time)) {
                mysqli_stmt_bind_param($stmt_time, "iss", $lockout_time, $username, $ip);
                mysqli_stmt_execute($stmt_time);
                $result_time = mysqli_stmt_get_result($stmt_time);
                $row_time = mysqli_fetch_assoc($result_time);
                $wait_minutes = ceil($row_time['wait_time'] / 60);
            }

            return [
                'allowed' => false,
                'message' => "Tài khoản tạm khóa do đăng nhập sai quá nhiều. Vui lòng thử lại sau {$wait_minutes} phút."
            ];
        }
    }

    return ['allowed' => true];
}

function record_failed_attempt($username, $ip) {
    global $link;
    $sql = "INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $ip);
        mysqli_stmt_execute($stmt);
    }
}

function clear_login_attempts($username, $ip) {
    global $link;
    $sql = "DELETE FROM login_attempts WHERE username = ? OR ip_address = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $ip);
        mysqli_stmt_execute($stmt);
    }
}

// Cleanup old records (chạy định kỳ)
function cleanup_old_attempts() {
    global $link;
    $sql = "DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 1 DAY)";
    mysqli_query($link, $sql);
}
?>
```

**Sử dụng trong login.php:**
```php
<?php
// File: login.php (thêm vào đầu phần xử lý POST)
require_once "config/rate_limit.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Kiểm tra rate limit
    $rate_check = check_login_attempts($username, $ip_address);
    if (!$rate_check['allowed']) {
        $login_err = $rate_check['message'];
    } else {
        // Xử lý đăng nhập bình thường...
        if(password_verify($password, $hashed_password)){
            // Đăng nhập thành công - xóa attempts
            clear_login_attempts($username, $ip_address);

            session_regenerate_id(true);
            $_SESSION["loggedin"] = true;
            // ...
        } else{
            // Đăng nhập thất bại - ghi nhận
            record_failed_attempt($username, $ip_address);
            $login_err = "Tên đăng nhập hoặc mật khẩu không hợp lệ.";
        }
    }
}
?>
```

---

### 9. **Weak Demo Credentials Displayed - Lộ mật khẩu demo trên trang đăng nhập**

**Mức độ:** 🟠 HIGH (nếu là production)
**CVSS Score:** 6.5 (Medium)
**CWE:** CWE-200 (Information Exposure)

**Mô tả:**
Trang đăng nhập hiển thị tất cả username và mật khẩu demo. Nếu đây là hệ thống production, đây là lỗ hổng nghiêm trọng.

**File:** `login.php` (dòng 108-118)

**Khuyến nghị:**
- **Production:** Xóa hoàn toàn phần demo credentials
- **Development/UAT:** Giữ lại nhưng thêm kiểm tra môi trường

```php
<?php
// Chỉ hiển thị demo credentials trong môi trường development
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
?>
    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-300 rounded-lg text-xs">
        <h4 class="font-bold mb-2 text-yellow-800 text-sm">⚠️ DEVELOPMENT MODE - Demo Accounts</h4>
        <!-- Demo credentials here -->
    </div>
<?php } ?>
```

---

### 10. **Missing Security Headers**

**Mức độ:** 🟠 HIGH
**CVSS Score:** 6.1 (Medium)
**CWE:** CWE-693 (Protection Mechanism Failure)

**Mô tả:**
Không có HTTP Security Headers để bảo vệ khỏi các tấn công phổ biến.

**Khuyến nghị khắc phục:**

**File:** `includes/header.php` (thêm vào đầu file, trước HTML)
```php
<?php
// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Content Security Policy
$csp = "default-src 'self'; ";
$csp .= "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; ";
$csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ";
$csp .= "font-src 'self' https://fonts.gstatic.com; ";
$csp .= "img-src 'self' data: https://placehold.co; ";
$csp .= "frame-ancestors 'none';";
header("Content-Security-Policy: " . $csp);

// HTTPS enforcement (nếu có SSL)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}
?>
```

---

## 🟡 CÁC LỖ HỔNG MỨC TRUNG BÌNH (MEDIUM)

### 11. **Username Enumeration**

**Mức độ:** 🟡 MEDIUM
**CVSS Score:** 5.3 (Medium)
**CWE:** CWE-204 (Observable Response Discrepancy)

**Mô tả:**
Hệ thống trả về thông báo lỗi giống nhau cho cả username và password sai, tốt. Tuy nhiên vẫn có thể enumerate username qua timing attack.

**File:** `login.php` (dòng 49, 53)

**Khuyến nghị:**
```php
// Thêm random delay để chống timing attack
if (!password_verify($password, $hashed_password)) {
    usleep(rand(100000, 300000)); // Random 0.1-0.3 giây
    $login_err = "Tên đăng nhập hoặc mật khẩu không hợp lệ.";
}
```

---

### 12. **No Input Length Validation**

**Mức độ:** 🟡 MEDIUM
**CVSS Score:** 5.0 (Medium)
**CWE:** CWE-1284 (Improper Validation of Specified Quantity in Input)

**Mô tả:**
Một số trường không validate độ dài input, có thể gây DoS hoặc buffer overflow.

**Files:** `create_application.php`, `admin/manage_users.php`

**Khuyến nghị:**
```php
// Thêm maxlength vào HTML
<input type="text" name="username" maxlength="50" required>
<textarea name="purpose" maxlength="1000" required></textarea>

// Validate server-side
if (strlen($username) > 50) {
    $errors[] = "Tên đăng nhập không được vượt quá 50 ký tự.";
}

if (strlen($purpose) > 1000) {
    $errors[] = "Mục đích vay không được vượt quá 1000 ký tự.";
}
```

---

### 13. **Error Information Disclosure**

**Mức độ:** 🟡 MEDIUM
**CVSS Score:** 4.3 (Medium)
**CWE:** CWE-209 (Information Exposure Through Error Message)

**Mô tả:**
Một số file hiển thị lỗi chi tiết có thể lộ thông tin hệ thống.

**File:** `login.php` (dòng 56), `config/db.php` (dòng 16)

**Khuyến nghị:**
```php
// Production error handling
if($link === false){
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Hệ thống đang bảo trì. Vui lòng liên hệ IT Support.");
}

// php.ini settings cho production:
// display_errors = Off
// log_errors = On
// error_log = /var/log/php/error.log
```

---

### 14. **SQL Injection trong table name (Low risk vì đã filter)**

**Mức độ:** 🟡 MEDIUM
**CVSS Score:** 4.0 (Medium)
**CWE:** CWE-89 (SQL Injection)

**Mô tả:**
Dù đã có whitelist, việc concatenate table name vẫn tiềm ẩn rủi ro.

**File:** `process_action.php` (dòng 49)

**Code hiện tại:**
```php
$table_map = [
    'collateral' => 'application_collaterals',
    'repayment' => 'application_repayment_sources',
    'document' => 'application_documents'
];
$table_name = $table_map[$item_type];
$sql = "DELETE FROM $table_name WHERE id = ? AND application_id = ?";
```

**Đánh giá:** Code này AN TOÀN vì đã dùng whitelist. Tuy nhiên nên thêm kiểm tra phòng ngừa:

```php
if (!array_key_exists($item_type, $table_map)) {
    error_log("Invalid item_type attempted: " . $item_type);
    header("location: application_detail.php?id=" . $application_id . "&error=invalid_action");
    exit;
}
$table_name = $table_map[$item_type];
```

---

### 15. **Directory Listing Enabled**

**Mức độ:** 🟡 MEDIUM
**CVSS Score:** 5.3 (Medium)
**CWE:** CWE-548 (Directory Listing)

**Mô tả:**
Thư mục uploads/ có thể cho phép liệt kê danh sách file nếu không có file index.

**Khuyến nghị:**

**File:** `uploads/index.php` (TẠO MỚI)
```php
<?php
// Prevent directory listing
header("HTTP/1.0 403 Forbidden");
die("Access Denied");
?>
```

**Hoặc thêm vào .htaccess:**
```apache
Options -Indexes
```

---

## ⚪ CÁC VẤN ĐỀ MỨC THẤP (LOW)

### 16. **No HTTPS Enforcement**

**Mức độ:** ⚪ LOW (Informational)
**Mô tả:** Không có code redirect HTTP sang HTTPS.

**Khuyến nghị:**
```php
// File: config/https.php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    $redirect_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: " . $redirect_url);
    exit;
}
```

---

### 17. **Predictable Application ID**

**Mức độ:** ⚪ LOW
**Mô tả:** Mã hồ sơ `APP.2024.XXXX` dùng rand() có thể đoán được.

**File:** `create_application.php` (dòng 24)

**Khuyến nghị:**
```php
// Sử dụng random_int thay vì rand
$hstd_code = "APP." . date("Y") . "." . random_int(100000, 999999);

// Hoặc dùng UUID
$hstd_code = "APP." . date("Y") . "." . bin2hex(random_bytes(4));
```

---

### 18. **No Password Complexity Requirements**

**Mức độ:** ⚪ LOW
**Mô tả:** Không enforce độ mạnh mật khẩu khi tạo/đổi user.

**File:** `admin/manage_users.php`

**Khuyến nghị:**
```php
// Validate password strength
if (!empty($password)) {
    if (strlen($password) < 12) {
        $errors[] = "Mật khẩu phải có ít nhất 12 ký tự.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 1 chữ hoa.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 1 chữ thường.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 1 số.";
    }
    if (!preg_match('/[@$!%*?&#]/', $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 1 ký tự đặc biệt.";
    }
}
```

---

## ✅ THỰC HÀNH TỐT ĐÃ PHÁT HIỆN (GOOD PRACTICES)

1. **✅ Sử dụng Prepared Statements** - Toàn bộ queries đều dùng `mysqli_prepare()` và bind parameters, chống SQL Injection hiệu quả.

2. **✅ Password Hashing với Bcrypt** - Sử dụng `password_hash()` với `PASSWORD_DEFAULT` (bcrypt), không lưu plaintext password.

3. **✅ Output Escaping** - Hầu hết output đều dùng `htmlspecialchars()` để chống XSS.

4. **✅ Session-based Authentication** - Authentication dựa trên PHP session, không dùng cookies không an toàn.

5. **✅ Role-based Access Control (RBAC)** - Có phân quyền rõ ràng theo vai trò (CVQHKH, CVTĐ, CPD, GDK, Admin).

6. **✅ UTF-8 Encoding** - Database và connection đều dùng utf8mb4, hỗ trợ đầy đủ Unicode.

7. **✅ Approval Workflow Logic** - Quy trình phê duyệt có kiểm tra hạn mức, routing đúng người duyệt.

---

## 📊 THỐNG KÊ LỖ HỔNG

| Mức độ | Số lượng | % |
|--------|----------|---|
| 🔴 Critical | 4 | 22% |
| 🟠 High | 6 | 33% |
| 🟡 Medium | 5 | 28% |
| ⚪ Low | 3 | 17% |
| **Tổng** | **18** | **100%** |

---

## 🎯 KHUYẾN NGHỊ ƯU TIÊN KHẮC PHỤC

### Độ ưu tiên 1 - KHẨN CẤP (phải fix ngay):
1. ✅ Thêm CSRF Protection toàn bộ hệ thống
2. ✅ Fix File Upload Validation
3. ✅ Fix IDOR vulnerability trong application_detail.php
4. ✅ Di chuyển database credentials ra .env file

### Độ ưu tiên 2 - CAO (fix trong tuần này):
5. ✅ Implement Session Regeneration và Session Timeout
6. ✅ Thêm Rate Limiting cho login
7. ✅ Fix Path Traversal trong file deletion
8. ✅ Thêm Security Headers
9. ✅ Xóa/ẩn demo credentials

### Độ ưu tiên 3 - TRUNG BÌNH (fix trong tháng này):
10. ✅ Thêm Input Length Validation
11. ✅ Cải thiện Error Handling
12. ✅ Fix Directory Listing
13. ✅ Thêm timing attack protection

### Độ ưu tiên 4 - THẤP (fix khi có thời gian):
14. ✅ Enforce HTTPS
15. ✅ Sử dụng random_int thay rand
16. ✅ Thêm password complexity requirements

---

## 📝 COMPLIANCE & STANDARDS

### OWASP Top 10 2021 Coverage:

| OWASP Risk | Tìm thấy? | Lỗ hổng liên quan |
|------------|-----------|-------------------|
| A01 Broken Access Control | ✅ Có | #3 IDOR, #7 Path Traversal |
| A02 Cryptographic Failures | ⚠️ Một phần | #4 Hardcoded credentials |
| A03 Injection | ✅ Không | Đã chống bằng prepared statements |
| A04 Insecure Design | ✅ Có | #1 No CSRF, #6 No session timeout |
| A05 Security Misconfiguration | ✅ Có | #10 Missing headers, #15 Directory listing |
| A06 Vulnerable Components | ℹ️ N/A | Chưa check dependencies |
| A07 Identification & Auth Failures | ✅ Có | #5 Session fixation, #8 No rate limit |
| A08 Software & Data Integrity | ⚠️ Một phần | #2 File upload |
| A09 Logging & Monitoring Failures | ⚠️ Một phần | Thiếu audit logging |
| A10 Server-Side Request Forgery | ✅ Không | Không có chức năng liên quan |

---

## 🔧 CÔNG CỤ KIỂM TRA BẢO MẬT ĐỀ XUẤT

### Automated Security Scanners:
1. **OWASP ZAP** - Web application security scanner
2. **Burp Suite Community** - Manual penetration testing
3. **Nikto** - Web server scanner
4. **SQLMap** - SQL injection testing (đã an toàn)
5. **PHPStan** - PHP static analysis tool

### Dependency Checking:
```bash
# Kiểm tra PHP version và extensions
composer require --dev roave/security-advisories:dev-latest
```

### Manual Testing Checklist:
- [ ] Test CSRF trên tất cả forms
- [ ] Test file upload với shell.php, .htaccess
- [ ] Test IDOR bằng cách thay đổi id parameters
- [ ] Test SQL injection (should be safe)
- [ ] Test XSS trong tất cả input fields
- [ ] Test brute force login
- [ ] Test session management
- [ ] Test directory traversal
- [ ] Test security headers với securityheaders.com
- [ ] Test SSL/TLS configuration với ssllabs.com

---

## 📚 TÀI LIỆU THAM KHẢO

1. **OWASP Top 10:** https://owasp.org/www-project-top-ten/
2. **PHP Security Cheat Sheet:** https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html
3. **Session Management:** https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html
4. **File Upload:** https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
5. **CSRF Prevention:** https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html

---

## 🔒 KẾT LUẬN

Hệ thống LOS có **nền tảng bảo mật cơ bản tốt** với việc sử dụng prepared statements và password hashing. Tuy nhiên, **cần khắc phục NGAY LẬP TỨC** các lỗ hổng nghiêm trọng về CSRF, File Upload, và IDOR trước khi deploy production.

**Khuyến nghị chính:**
1. Không deploy hệ thống này lên production cho đến khi fix xong 4 lỗ hổng CRITICAL
2. Thực hiện penetration testing bởi chuyên gia bảo mật
3. Thiết lập WAF (Web Application Firewall)
4. Implement logging và monitoring
5. Training developer về secure coding practices

**Timeline đề xuất:**
- **Tuần 1-2:** Fix 4 lỗ hổng CRITICAL + 5 lỗ hổng HIGH
- **Tuần 3-4:** Fix 5 lỗ hổng MEDIUM + penetration testing
- **Tuần 5:** Re-test và deployment

---

**Prepared by:** Claude Code Security Audit
**Date:** 29/10/2025
**Version:** 1.0
**Classification:** CONFIDENTIAL - Internal Use Only
