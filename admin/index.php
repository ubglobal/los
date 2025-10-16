<?php
// File: admin/index.php
session_start();
// Admin access only
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'Admin') {
    header("location: ../login.php");
    exit;
}

$pageTitle = "Bảng điều khiển Admin";
include 'includes/header.php';
?>

<main class="p-6">
    <h1 class="text-2xl font-bold mb-6">Bảng điều khiển Quản trị</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <a href="manage_users.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h2 class="text-xl font-semibold text-gray-800">Quản lý Người dùng</h2>
            <p class="mt-2 text-gray-600">Thêm, sửa, và phân quyền cho các tài khoản người dùng trong hệ thống.</p>
        </a>

        <a href="manage_customers.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h2 class="text-xl font-semibold text-gray-800">Quản lý Khách hàng</h2>
            <p class="mt-2 text-gray-600">Tạo và quản lý thông tin khách hàng cá nhân và doanh nghiệp.</p>
        </a>

        <a href="manage_products.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h2 class="text-xl font-semibold text-gray-800">Quản lý Sản phẩm</h2>
            <p class="mt-2 text-gray-600">Định nghĩa các sản phẩm tín dụng đang được cung cấp.</p>
        </a>
        
        <a href="manage_collaterals.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h2 class="text-xl font-semibold text-gray-800">Quản lý Loại TSBĐ</h2>
            <p class="mt-2 text-gray-600">Quản lý danh mục các loại tài sản bảo đảm được chấp nhận.</p>
        </a>

    </div>
</main>

<?php include 'includes/footer.php'; ?>

