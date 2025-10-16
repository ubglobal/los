<?php
// File: config/db.php
// Cấu hình kết nối cơ sở dữ liệu

// Vui lòng thay đổi các thông tin dưới đây cho phù hợp với môi trường của bạn
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'vnbc_los');
define('DB_PASSWORD', '4LyTKPdAY3ek6pZD3BEd');
define('DB_NAME', 'vnbc_los');

// Cố gắng kết nối đến CSDL MySQL
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kiểm tra kết nối
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Thiết lập charset UTF-8
mysqli_set_charset($link, "utf8");

?>
