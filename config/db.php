<?php
// File: config/db.php
// Cấu hình kết nối cơ sở dữ liệu - SECURE VERSION

// Load environment variables from .env file
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $env = parse_ini_file($env_file);
    if ($env === false) {
        error_log("Failed to parse .env file");
        die("Configuration error. Please contact system administrator.");
    }

    define('DB_SERVER', $env['DB_SERVER'] ?? 'localhost');
    define('DB_USERNAME', $env['DB_USERNAME'] ?? '');
    define('DB_PASSWORD', $env['DB_PASSWORD'] ?? '');
    define('DB_NAME', $env['DB_NAME'] ?? '');
    define('ENVIRONMENT', $env['ENVIRONMENT'] ?? 'production');
} else {
    error_log(".env file not found at: " . $env_file);
    die('Configuration file not found. Please create .env file from .env.example');
}

// Validate required configuration
if (empty(DB_USERNAME) || empty(DB_PASSWORD) || empty(DB_NAME)) {
    error_log("Database configuration incomplete");
    die("Configuration error. Please check .env file.");
}

// Attempt to connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    // Log error but don't expose details to user
    error_log("Database connection failed: " . mysqli_connect_error());

    if (ENVIRONMENT === 'development') {
        die("ERROR: Could not connect to database. " . mysqli_connect_error());
    } else {
        die("Hệ thống đang bảo trì. Vui lòng thử lại sau hoặc liên hệ IT Support.");
    }
}

// Set charset to UTF-8mb4 (better than utf8)
mysqli_set_charset($link, "utf8mb4");

// Set SQL mode for better security and compatibility
mysqli_query($link, "SET SESSION sql_mode='STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

?>
