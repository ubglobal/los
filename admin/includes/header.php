<?php
// File: admin/includes/header.php - SECURE VERSION

// Set security headers
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

// Strict-Transport-Security (if using HTTPS)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}
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
        <header class="bg-gradient-to-r from-blue-600 to-blue-700 shadow-lg z-10">
            <div class="px-4 h-16 flex justify-between items-center">
                <div class="flex items-center space-x-3">
                     <img src="https://placehold.co/150x40/FFFFFF/004a99?text=U%26Bank" alt="U&Bank Logo" class="h-8">
                     <h1 class="text-lg font-bold text-white hidden sm:block">Khu vực Quản trị</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                         <div class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></div>
                         <div class="text-xs text-blue-100"><?php echo htmlspecialchars($_SESSION['role'] ?? 'N/A'); ?></div>
                    </div>
                     <a href="../logout.php" title="Đăng xuất" class="p-2 rounded-full hover:bg-blue-800 transition">
                       <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                     </a>
                </div>
            </div>
        </header>
        <div class="flex flex-1 overflow-hidden">
             <aside class="w-64 bg-white p-4 flex-shrink-0 overflow-y-auto border-r shadow-sm">
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-blue-50 font-medium transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Bảng điều khiển
                    </a>
                    <a href="manage_users.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-blue-50 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Người dùng
                    </a>
                    <a href="manage_customers.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-blue-50 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Khách hàng
                    </a>
                    <a href="manage_products.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-blue-50 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        Sản phẩm
                    </a>
                    <a href="manage_collaterals.php" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-blue-50 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Loại TSBĐ
                    </a>
                     <hr class="my-4">
                    <a href="../index.php" class="flex items-center px-3 py-2 text-blue-600 rounded-md hover:bg-blue-50 font-medium transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Trang chính
                    </a>
                </nav>
            </aside>
            <main class="flex-1 overflow-y-auto">
