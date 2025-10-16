<?php
// File: includes/header.php
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle ?? 'Hệ thống LOS - U&Bank'; ?></title>
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
                     <h1 class="text-lg font-bold text-gray-700 hidden sm:block">Giải pháp Khởi tạo và Quản lý Tín dụng (LOS)</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                         <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                         <div class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['role'] . ' - ' . $_SESSION['branch']); ?></div>
                    </div>
                     <a href="logout.php" title="Đăng xuất" class="p-2 rounded-full hover:bg-gray-100">
                       <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                     </a>
                </div>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden">
            <!-- Left Main Menu -->
            <aside class="w-64 bg-white p-4 flex-shrink-0 overflow-y-auto border-r">
                <nav class="space-y-2">
                    <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nghiệp vụ</h3>
                    <a href="index.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 font-medium">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Hộp công việc
                    </a>
                    <?php if ($_SESSION['role'] == 'CVQHKH'): ?>
                    <a href="create_application.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 font-medium">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        Tạo Hồ sơ mới
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] == 'Admin'): ?>
                    <h3 class="px-3 pt-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Quản trị</h3>
                     <a href="admin/index.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100">
                         <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6A2.25 2.25 0 0 1 12.75 8.25v1.5a2.25 2.25 0 0 1-4.5 0v-1.5A2.25 2.25 0 0 1 10.5 6Zm0 9.75h.008v.008h-.008V15.75Zm.75-4.5a.75.75 0 0 0-1.5 0v.75a.75.75 0 0 0 1.5 0v-.75Z" /></svg>
                         Bảng điều khiển
                    </a>
                    <a href="admin/manage_users.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100">
                         <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-2.253a9.527 9.527 0 0 0-1.742-1.845A9.337 9.337 0 0 0 12 18.25a9.337 9.337 0 0 0-6.004-2.253A9.527 9.527 0 0 0 4.258 17.25a9.337 9.337 0 0 0 4.121 2.253A9.38 9.38 0 0 0 12 19.128ZM12 12.25a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z" /></svg>
                         Quản lý Người dùng
                    </a>
                    <?php endif; ?>
                </nav>
            </aside>

