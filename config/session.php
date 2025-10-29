<?php
// File: config/session.php
// Session Security and Timeout Management

// Session timeout after 30 minutes of inactivity
define('SESSION_TIMEOUT', 1800); // 30 minutes = 1800 seconds

// Absolute session timeout after 8 hours (1 working shift)
define('SESSION_ABSOLUTE_TIMEOUT', 28800); // 8 hours = 28800 seconds

/**
 * Check session timeout and security
 */
function check_session_timeout() {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        return;
    }

    $current_time = time();

    // Check inactivity timeout
    if (isset($_SESSION['last_activity']) && ($current_time - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("location: login.php?timeout=inactivity");
        exit;
    }

    // Check absolute timeout
    if (isset($_SESSION['login_time']) && ($current_time - $_SESSION['login_time'] > SESSION_ABSOLUTE_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("location: login.php?timeout=absolute");
        exit;
    }

    // Update last activity time
    $_SESSION['last_activity'] = $current_time;

    // Optional: Check IP address to detect session hijacking
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        error_log("Session hijacking detected. Session IP: " . $_SESSION['user_ip'] . ", Request IP: " . $_SERVER['REMOTE_ADDR']);
        session_unset();
        session_destroy();
        header("location: login.php?error=session_hijack");
        exit;
    }

    // Optional: Check User-Agent (less strict as it can change legitimately)
    // Uncomment if needed
    /*
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        error_log("User-Agent changed. Original: " . $_SESSION['user_agent'] . ", Current: " . $_SERVER['HTTP_USER_AGENT']);
        session_unset();
        session_destroy();
        header("location: login.php?error=session_invalid");
        exit;
    }
    */
}

/**
 * Initialize secure session
 */
function init_secure_session() {
    // Configure session security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
?>
