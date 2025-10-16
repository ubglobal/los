<?php
// File: generate_hash.php
// Purpose: A simple tool to generate a password hash for the current PHP environment.
// Usage: Run this file in your browser. It will output a hash for the password 'ub@12345678'.
// Copy the generated hash and update the 'password_hash' column in your 'users' table in phpMyAdmin.

$passwordToHash = 'ub@12345678';

// Generate the hash using PHP's standard password hashing function.
// This ensures compatibility with the password_verify() function used in login.php.
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Công cụ tạo mã hóa mật khẩu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hash-box {
            word-break: break-all;
            user-select: all;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="p-8 bg-white rounded-lg shadow-xl w-full max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Mã hóa mật khẩu cho môi trường của bạn</h1>
        <p class="text-gray-600 mb-2">Mã hóa này được tạo riêng cho phiên bản PHP của bạn.</p>
        <p class="text-gray-600 mb-4">Mật khẩu đang được mã hóa là: <code class="bg-gray-200 text-red-600 font-semibold px-1 py-0.5 rounded"><?php echo htmlspecialchars($passwordToHash); ?></code></p>
        
        <div class="mt-6">
            <label for="hash-output" class="block text-sm font-medium text-gray-700">Đây là chuỗi mã hóa của bạn:</label>
            <div id="hash-output" class="mt-1 p-4 bg-gray-50 border border-gray-300 rounded-md font-mono text-sm hash-box" title="Click để chọn tất cả">
                <?php echo htmlspecialchars($hashedPassword); ?>
            </div>
        </div>

        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="font-bold text-blue-800">Các bước tiếp theo:</h4>
            <ol class="list-decimal list-inside mt-2 text-blue-700 space-y-1">
                <li>Sao chép toàn bộ chuỗi mã hóa ở trên.</li>
                <li>Mở phpMyAdmin và truy cập bảng <strong>users</strong>.</li>
                <li>Chỉnh sửa (Edit) các dòng người dùng (ví dụ: `admin`, `qhkh.an.nguyen`...).</li>
                <li>Dán chuỗi mã hóa mới này vào cột <strong>password_hash</strong> cho từng người.</li>
                <li>Lưu lại thay đổi. Bây giờ bạn có thể đăng nhập bằng mật khẩu <code class="bg-gray-200 font-semibold px-1 py-0.5 rounded">ub@12345678</code>.</li>
            </ol>
        </div>
    </div>
    <script>
        document.getElementById('hash-output').addEventListener('click', function() {
            window.getSelection().selectAllChildren(this);
        });
    </script>
</body>
</html>
