<?php
// File: login.php (Updated with new password)
session_start();
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}
require_once "config/db.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Vui lòng nhập tên đăng nhập.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Vui lòng nhập mật khẩu.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT id, username, password_hash, full_name, role, branch FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $full_name, $role, $branch);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["full_name"] = $full_name;
                            $_SESSION["role"] = $role;
                            $_SESSION["branch"] = $branch;
                                                        
                            header("location: index.php");
                        } else{
                            $login_err = "Tên đăng nhập hoặc mật khẩu không hợp lệ.";
                        }
                    }
                } else{
                    $login_err = "Tên đăng nhập hoặc mật khẩu không hợp lệ.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Hệ thống LOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body>
    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="p-8 bg-white rounded-lg shadow-xl w-full max-w-md">
             <div class="flex justify-center mb-4">
                <img src="https://placehold.co/150x40/004a99/FFFFFF?text=U%26Bank" alt="U&Bank Logo" class="h-10">
             </div>
            <h2 class="text-2xl font-bold mb-2 text-gray-800 text-center">Hệ thống LOS</h2>
            <p class="text-gray-500 mb-6 text-center">Vui lòng đăng nhập để bắt đầu</p>
            
            <?php 
            if(!empty($login_err)){
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . $login_err . '</div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="space-y-4">
                    <input type="text" name="username" placeholder="Tên đăng nhập" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="relative mt-4">
                    <input type="password" name="password" id="password-input" placeholder="Mật khẩu" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg id="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 1.274-4.057 5.064-7 9.542-7 .847 0 1.67.127 2.454.364m-3.033 7.143a3 3 0 11-4.243-4.243 3 3 0 014.243 4.243z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <button type="submit" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition duration-300">Đăng nhập</button>
            </form>

            <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg text-xs">
                <h4 class="font-bold mb-2 text-gray-800 text-sm">Tài khoản Demo</h4>
                <p class="mb-3 text-gray-600">Sử dụng các tài khoản dưới đây để đăng nhập. Mật khẩu cho tất cả là: <code class="bg-gray-200 text-red-600 font-semibold px-1 py-0.5 rounded">ub@12345678</code></p>
                <div class="space-y-2">
                    <p><strong>Admin:</strong> <code class="bg-gray-200 px-1 py-0.5 rounded select-all cursor-pointer" title="Click để chọn">admin</code></p>
                    <p><strong>CVQHKH:</strong> <code class="bg-gray-200 px-1 py-0.5 rounded select-all cursor-pointer" title="Click để chọn">qhkh.an.nguyen</code></p>
                    <p><strong>CVTĐ:</strong> <code class="bg-gray-200 px-1 py-0.5 rounded select-all cursor-pointer" title="Click để chọn">thamdinh.lan.vu</code></p>
                    <p><strong>CPD (Hạn mức <= 5 tỷ):</strong> <code class="bg-gray-200 px-1 py-0.5 rounded select-all cursor-pointer" title="Click để chọn">pheduyet.hung.tran</code></p>
                    <p><strong>GDK (Hạn mức > 5 tỷ):</strong> <code class="bg-gray-200 px-1 py-0.5 rounded select-all cursor-pointer" title="Click để chọn">gd.khoi.nguyen</code></p>
                </div>
            </div>

        </div>
    </div>
    <script>
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password-input');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeSlashIcon = document.getElementById('eye-slash-icon');

        togglePassword.addEventListener('click', function (e) {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            eyeIcon.classList.toggle('hidden');
            eyeSlashIcon.classList.toggle('hidden');
        });
    </script>
</body>
</html>

