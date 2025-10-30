<?php
/**
 * LOS v3.0 Web-Based Installer
 *
 * This script provides a user-friendly web interface for installing
 * the Loan Origination System v3.0
 *
 * Features:
 * - System requirements check
 * - Database setup
 * - Environment configuration
 * - Initial admin user creation
 * - Security lockdown after installation
 *
 * @version 3.0
 * @author Claude AI
 * @date 2025-10-30
 */

// Security: Prevent access if already installed
if (file_exists('.env') && filesize('.env') > 50 && file_exists('.installed')) {
    die('⚠️ Application is already installed. Please delete .installed file to reinstall.');
}

// Start session for installation progress
session_start();

// Installation steps
$steps = [
    1 => 'Kiểm tra yêu cầu hệ thống',
    2 => 'Cấu hình cơ sở dữ liệu',
    3 => 'Thiết lập cấu hình',
    4 => 'Tạo tài khoản quản trị',
    5 => 'Hoàn tất cài đặt'
];

$current_step = $_GET['step'] ?? 1;
$current_step = (int)$current_step;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'check_requirements') {
        // Move to step 2
        header('Location: install.php?step=2');
        exit;
    } elseif ($action == 'test_db_connection') {
        // Test database connection
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        $db_name = $_POST['db_name'] ?? 'vnbc_los';

        $conn = @mysqli_connect($db_host, $db_user, $db_pass);

        if ($conn) {
            // Store credentials in session
            $_SESSION['db_host'] = $db_host;
            $_SESSION['db_user'] = $db_user;
            $_SESSION['db_pass'] = $db_pass;
            $_SESSION['db_name'] = $db_name;

            // Create database if not exists
            $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            mysqli_query($conn, $sql);

            mysqli_close($conn);

            // Move to step 3
            header('Location: install.php?step=3');
            exit;
        } else {
            $error = "Không thể kết nối database: " . mysqli_connect_error();
        }
    } elseif ($action == 'setup_config') {
        // Setup configuration
        $app_url = $_POST['app_url'] ?? '';
        $environment = $_POST['environment'] ?? 'production';

        $_SESSION['app_url'] = $app_url;
        $_SESSION['environment'] = $environment;

        // Move to step 4
        header('Location: install.php?step=4');
        exit;
    } elseif ($action == 'create_admin') {
        // Create admin user and finalize installation
        $admin_name = $_POST['admin_name'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        $admin_confirm = $_POST['admin_confirm'] ?? '';

        if ($admin_password !== $admin_confirm) {
            $error = "Mật khẩu xác nhận không khớp.";
        } elseif (strlen($admin_password) < 8) {
            $error = "Mật khẩu phải có ít nhất 8 ký tự.";
        } else {
            // Run full installation
            $result = run_installation($_SESSION, $admin_name, $admin_email, $admin_password);

            if ($result['success']) {
                // Move to step 5
                header('Location: install.php?step=5');
                exit;
            } else {
                $error = $result['error'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt LOS v3.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step-active { background-color: #3B82F6; color: white; }
        .step-completed { background-color: #10B981; color: white; }
        .step-pending { background-color: #E5E7EB; color: #6B7280; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-4xl w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-blue-600 mb-2">LOS v3.0</h1>
                <p class="text-gray-600">Loan Origination System - Hệ thống quản lý cho vay</p>
            </div>

            <!-- Progress Steps -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex justify-between items-center">
                    <?php foreach ($steps as $num => $title): ?>
                        <div class="flex-1 text-center">
                            <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center mb-2 font-bold
                                <?php
                                    if ($num < $current_step) echo 'step-completed';
                                    elseif ($num == $current_step) echo 'step-active';
                                    else echo 'step-pending';
                                ?>">
                                <?php echo $num; ?>
                            </div>
                            <p class="text-xs text-gray-600"><?php echo $title; ?></p>
                        </div>
                        <?php if ($num < count($steps)): ?>
                            <div class="flex-shrink-0 w-16 h-1 bg-gray-300"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <strong>Lỗi:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($current_step == 1): ?>
                    <!-- Step 1: Requirements Check -->
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Kiểm tra yêu cầu hệ thống</h2>

                    <?php
                    $requirements = check_requirements();
                    $all_pass = true;
                    foreach ($requirements as $req) {
                        if (!$req['status']) $all_pass = false;
                    }
                    ?>

                    <div class="space-y-4 mb-6">
                        <?php foreach ($requirements as $req): ?>
                            <div class="flex items-center justify-between p-4 border rounded-lg <?php echo $req['status'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?>">
                                <div>
                                    <h3 class="font-semibold text-gray-800"><?php echo $req['name']; ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo $req['description']; ?></p>
                                    <?php if (isset($req['current'])): ?>
                                        <p class="text-xs text-gray-500 mt-1">Hiện tại: <?php echo $req['current']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($req['status']): ?>
                                        <span class="text-green-600 text-2xl">✓</span>
                                    <?php else: ?>
                                        <span class="text-red-600 text-2xl">✗</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($all_pass): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="check_requirements">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">
                                Tiếp tục →
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-yellow-800 font-semibold">⚠️ Một số yêu cầu chưa đáp ứng</p>
                            <p class="text-sm text-yellow-700 mt-2">Vui lòng cài đặt/cấu hình các thành phần bị thiếu trước khi tiếp tục.</p>
                        </div>
                    <?php endif; ?>

                <?php elseif ($current_step == 2): ?>
                    <!-- Step 2: Database Configuration -->
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Cấu hình cơ sở dữ liệu</h2>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="test_db_connection">

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Database Host</label>
                            <input type="text" name="db_host" value="localhost" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-sm text-gray-500 mt-1">Thường là "localhost" hoặc "127.0.0.1"</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Database Username</label>
                            <input type="text" name="db_user" value="root" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Database Password</label>
                            <input type="password" name="db_pass" value=""
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-sm text-gray-500 mt-1">Để trống nếu không có password</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Database Name</label>
                            <input type="text" name="db_name" value="vnbc_los" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-sm text-gray-500 mt-1">Database sẽ được tạo tự động nếu chưa tồn tại</p>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">
                            Kiểm tra kết nối →
                        </button>
                    </form>

                <?php elseif ($current_step == 3): ?>
                    <!-- Step 3: Application Configuration -->
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Thiết lập cấu hình</h2>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="setup_config">

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Application URL</label>
                            <input type="url" name="app_url" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?>" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-sm text-gray-500 mt-1">URL đầy đủ để truy cập ứng dụng (không có dấu "/" ở cuối)</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Environment</label>
                            <select name="environment" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="production">Production (Sản xuất)</option>
                                <option value="development">Development (Phát triển)</option>
                                <option value="staging">Staging (Kiểm thử)</option>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">Chọn "Production" cho môi trường chính thức</p>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">
                            Tiếp tục →
                        </button>
                    </form>

                <?php elseif ($current_step == 4): ?>
                    <!-- Step 4: Create Admin User -->
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Tạo tài khoản quản trị</h2>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="create_admin">

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Họ và tên</label>
                            <input type="text" name="admin_name" value="Administrator" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Email</label>
                            <input type="email" name="admin_email" value="admin@vnbc.vn" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Mật khẩu</label>
                            <input type="password" name="admin_password" required minlength="8"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-sm text-gray-500 mt-1">Tối thiểu 8 ký tự</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Xác nhận mật khẩu</label>
                            <input type="password" name="admin_confirm" required minlength="8"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-yellow-800 font-semibold">⚠️ Lưu ý quan trọng</p>
                            <p class="text-sm text-yellow-700 mt-2">Đây sẽ là bước cuối cùng. Hệ thống sẽ:</p>
                            <ul class="list-disc list-inside text-sm text-yellow-700 mt-2 space-y-1">
                                <li>Tạo cơ sở dữ liệu và các bảng</li>
                                <li>Tạo file cấu hình .env</li>
                                <li>Tạo tài khoản quản trị</li>
                                <li>Khóa trình cài đặt</li>
                            </ul>
                        </div>

                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg">
                            🚀 Bắt đầu cài đặt
                        </button>
                    </form>

                <?php elseif ($current_step == 5): ?>
                    <!-- Step 5: Installation Complete -->
                    <div class="text-center">
                        <div class="mb-6">
                            <span class="text-green-600 text-6xl">✓</span>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-800 mb-4">Cài đặt hoàn tất!</h2>
                        <p class="text-gray-600 mb-8">LOS v3.0 đã được cài đặt thành công trên hệ thống của bạn.</p>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6 text-left">
                            <h3 class="font-semibold text-blue-800 mb-3">Thông tin đăng nhập:</h3>
                            <div class="space-y-2 text-sm text-blue-700">
                                <p><strong>URL:</strong> <a href="login.php" class="underline">login.php</a></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'admin@vnbc.vn'); ?></p>
                                <p><strong>Mật khẩu:</strong> (mật khẩu bạn vừa tạo)</p>
                            </div>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6 text-left">
                            <h3 class="font-semibold text-yellow-800 mb-3">⚠️ Bảo mật quan trọng:</h3>
                            <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                                <li>Đã tạo file <code>.installed</code> để khóa trình cài đặt</li>
                                <li>Nên xóa hoặc đổi tên file <code>install.php</code></li>
                                <li>Kiểm tra quyền truy cập thư mục <code>/uploads/</code></li>
                                <li>Đảm bảo file <code>.env</code> không public</li>
                            </ul>
                        </div>

                        <a href="login.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg">
                            Đến trang đăng nhập
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 text-gray-500 text-sm">
                <p>LOS v3.0 © 2025 | Developed with Claude AI</p>
            </div>
        </div>
    </div>
</body>
</html>

<?php
/**
 * Check system requirements
 */
function check_requirements() {
    $requirements = [];

    // PHP Version
    $php_version = phpversion();
    $requirements[] = [
        'name' => 'PHP Version',
        'description' => 'PHP 7.4 trở lên (khuyến nghị 8.0+)',
        'status' => version_compare($php_version, '7.4.0', '>='),
        'current' => $php_version
    ];

    // MySQL Extension
    $requirements[] = [
        'name' => 'MySQLi Extension',
        'description' => 'Extension PHP MySQLi để kết nối database',
        'status' => extension_loaded('mysqli')
    ];

    // Session Support
    $requirements[] = [
        'name' => 'Session Support',
        'description' => 'PHP Session để quản lý đăng nhập',
        'status' => function_exists('session_start')
    ];

    // JSON Support
    $requirements[] = [
        'name' => 'JSON Extension',
        'description' => 'Extension JSON cho xử lý dữ liệu',
        'status' => function_exists('json_encode')
    ];

    // mbstring Extension
    $requirements[] = [
        'name' => 'MBString Extension',
        'description' => 'Extension mbstring cho xử lý Unicode/UTF-8',
        'status' => extension_loaded('mbstring')
    ];

    // File Permissions
    $writable_dirs = ['uploads', 'migrations'];
    $all_writable = true;
    foreach ($writable_dirs as $dir) {
        if (!is_writable($dir)) {
            $all_writable = false;
            break;
        }
    }
    $requirements[] = [
        'name' => 'File Permissions',
        'description' => 'Thư mục uploads/ và migrations/ cần quyền ghi',
        'status' => $all_writable
    ];

    // .env writable
    $env_writable = (!file_exists('.env') || is_writable('.env')) && is_writable('.');
    $requirements[] = [
        'name' => '.env File Writable',
        'description' => 'Thư mục gốc cần quyền ghi để tạo file .env',
        'status' => $env_writable
    ];

    return $requirements;
}

/**
 * Run full installation process
 */
function run_installation($config, $admin_name, $admin_email, $admin_password) {
    try {
        // 1. Connect to database
        $conn = mysqli_connect(
            $config['db_host'],
            $config['db_user'],
            $config['db_pass'],
            $config['db_name']
        );

        if (!$conn) {
            return ['success' => false, 'error' => 'Không thể kết nối database: ' . mysqli_connect_error()];
        }

        mysqli_set_charset($conn, 'utf8mb4');

        // 2. Run complete database schema (database.sql)
        $sql_file = file_get_contents('database.sql');
        if ($sql_file === false) {
            return ['success' => false, 'error' => 'Không thể đọc file database.sql'];
        }

        // Execute complete schema
        if (!mysqli_multi_query($conn, $sql_file)) {
            return ['success' => false, 'error' => 'Lỗi khi tạo database schema: ' . mysqli_error($conn)];
        }

        // Wait for all queries to finish
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));

        // 4. Create admin user
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, full_name, role, is_active, created_at)
                VALUES ('admin', ?, ?, ?, 'Admin', 1, NOW())
                ON DUPLICATE KEY UPDATE email = ?, full_name = ?, password = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss",
            $admin_email, $hashed_password, $admin_name,
            $admin_email, $admin_name, $hashed_password
        );
        mysqli_stmt_execute($stmt);

        // 5. Create .env file
        $env_content = "# LOS v3.0 Configuration\n";
        $env_content .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $env_content .= "DB_HOST=" . $config['db_host'] . "\n";
        $env_content .= "DB_USER=" . $config['db_user'] . "\n";
        $env_content .= "DB_PASSWORD=" . $config['db_pass'] . "\n";
        $env_content .= "DB_NAME=" . $config['db_name'] . "\n\n";
        $env_content .= "APP_URL=" . $config['app_url'] . "\n";
        $env_content .= "ENVIRONMENT=" . $config['environment'] . "\n";

        file_put_contents('.env', $env_content);

        // 6. Create .installed lock file
        file_put_contents('.installed', date('Y-m-d H:i:s'));

        // Store admin email in session
        $_SESSION['admin_email'] = $admin_email;

        mysqli_close($conn);

        return ['success' => true];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
