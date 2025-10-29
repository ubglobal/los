<?php
// File: admin/index.php - SECURE VERSION
require_once "../config/session.php";
init_secure_session();

// Admin access only
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'Admin') {
    header("location: ../login.php");
    exit;
}

// Check session timeout
check_session_timeout();

$pageTitle = "Bảng điều khiển Admin";
include 'includes/header.php';
?>

<main class="p-6">
    <h1 class="text-2xl font-bold mb-6">Bảng điều khiển Quản trị</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <a href="manage_users.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center mb-2">
                <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <h2 class="text-xl font-semibold text-gray-800">Quản lý Người dùng</h2>
            </div>
            <p class="mt-2 text-gray-600">Thêm, sửa, và phân quyền cho các tài khoản người dùng trong hệ thống.</p>
        </a>

        <a href="manage_customers.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center mb-2">
                <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <h2 class="text-xl font-semibold text-gray-800">Quản lý Khách hàng</h2>
            </div>
            <p class="mt-2 text-gray-600">Tạo và quản lý thông tin khách hàng cá nhân và doanh nghiệp.</p>
        </a>

        <a href="manage_products.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center mb-2">
                <svg class="w-8 h-8 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <h2 class="text-xl font-semibold text-gray-800">Quản lý Sản phẩm</h2>
            </div>
            <p class="mt-2 text-gray-600">Định nghĩa các sản phẩm tín dụng đang được cung cấp.</p>
        </a>

        <a href="manage_collaterals.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center mb-2">
                <svg class="w-8 h-8 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <h2 class="text-xl font-semibold text-gray-800">Quản lý Loại TSBĐ</h2>
            </div>
            <p class="mt-2 text-gray-600">Quản lý danh mục các loại tài sản bảo đảm được chấp nhận.</p>
        </a>

    </div>

    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Lưu ý bảo mật</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• Phiên làm việc sẽ tự động đăng xuất sau 30 phút không hoạt động</li>
            <li>• Tất cả các thao tác đều được ghi log để kiểm toán</li>
            <li>• Chỉ Admin mới có quyền truy cập khu vực này</li>
        </ul>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
