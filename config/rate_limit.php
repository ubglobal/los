<?php
// File: config/rate_limit.php
// Rate Limiting for Login Attempts

/**
 * Check if user/IP has exceeded login attempt limit
 * @param mysqli $link Database connection
 * @param string $username Username attempting to login
 * @param string $ip IP address
 * @return array ['allowed' => bool, 'message' => string]
 */
function check_login_attempts($link, $username, $ip) {
    // Create table if not exists
    $sql_create = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50),
        ip_address VARCHAR(45),
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_ip (ip_address),
        INDEX idx_time (attempt_time)
    ) ENGINE=InnoDB";
    mysqli_query($link, $sql_create);

    $max_attempts = 5;
    $lockout_time = 900; // 15 minutes

    // Count failed attempts in last 15 minutes
    $sql = "SELECT COUNT(*) as attempts FROM login_attempts
            WHERE (username = ? OR ip_address = ?)
            AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssi", $username, $ip, $lockout_time);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        if ($row['attempts'] >= $max_attempts) {
            // Calculate remaining wait time
            $sql_time = "SELECT TIMESTAMPDIFF(SECOND, MAX(attempt_time), DATE_ADD(MAX(attempt_time), INTERVAL ? SECOND)) as wait_time
                         FROM login_attempts WHERE username = ? OR ip_address = ?";
            $wait_minutes = 15;

            if ($stmt_time = mysqli_prepare($link, $sql_time)) {
                mysqli_stmt_bind_param($stmt_time, "iss", $lockout_time, $username, $ip);
                mysqli_stmt_execute($stmt_time);
                $result_time = mysqli_stmt_get_result($stmt_time);
                $row_time = mysqli_fetch_assoc($result_time);
                if ($row_time['wait_time'] > 0) {
                    $wait_minutes = ceil($row_time['wait_time'] / 60);
                }
            }

            return [
                'allowed' => false,
                'message' => "Tài khoản tạm khóa do đăng nhập sai quá nhiều lần. Vui lòng thử lại sau {$wait_minutes} phút."
            ];
        }
    }

    return ['allowed' => true];
}

/**
 * Record failed login attempt
 * @param mysqli $link Database connection
 * @param string $username Username that failed
 * @param string $ip IP address
 */
function record_failed_attempt($link, $username, $ip) {
    $sql = "INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/**
 * Clear login attempts for successful login
 * @param mysqli $link Database connection
 * @param string $username Username that succeeded
 * @param string $ip IP address
 */
function clear_login_attempts($link, $username, $ip) {
    $sql = "DELETE FROM login_attempts WHERE username = ? OR ip_address = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/**
 * Cleanup old login attempts (run periodically)
 * @param mysqli $link Database connection
 */
function cleanup_old_attempts($link) {
    $sql = "DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 1 DAY)";
    mysqli_query($link, $sql);
}
?>
