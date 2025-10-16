<?php
// File: admin/includes/header.php
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle ?? 'Admin - Hệ thống LOS'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f0f4f8; }</style>
</head>
<body class="text-sm">
<div id="app-container" class="min-h-screen">
    <div id="main-app" class="h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow-sm z-10">
            <div class="px-4 h-16 flex justify-between items-center border-b">
                <div class="flex items-center space-x-3">
                     <img src="https://placehold.co/150x40/004a99/FFFFFF?text=U%26Bank" alt="U&Bank Logo" class="h-8">
                     <h1 class="text-lg font-bold text-gray-700 hidden sm:block">Khu vực Quản trị</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                         <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                         <div class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
                    </div>
                     <a href="../logout.php" title="Đăng xuất" class="p-2 rounded-full hover:bg-gray-100">
                       <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                     </a>
                </div>
            </div>
        </header>
        <div class="flex flex-1 overflow-hidden">
             <aside class="w-64 bg-white p-4 flex-shrink-0 overflow-y-auto border-r">
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 font-medium">
                        Bảng điều khiển
                    </a>
                    <a href="manage_users.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100">
                        Quản lý Người dùng
                    </a>
                    <a href="manage_products.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100">
                        Quản lý Sản phẩm
                    </a>
                    <a href="manage_collaterals.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100">
                        Quản lý Loại TSBĐ
                    </a>
                     <hr class="my-4">
                    <a href="../index.php" class="flex items-center px-3 py-2 text-blue-600 rounded-md hover:bg-gray-100">
                        &larr; Quay lại trang chính
                    </a>
                </nav>
            </aside>
            <main class="flex-1 overflow-y-auto">

