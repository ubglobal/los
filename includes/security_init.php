<?php
// File: includes/security_init.php
// Security initialization for all protected pages
// Include this at the top of every protected page

// Initialize secure session
require_once __DIR__ . "/../config/session.php";
init_secure_session();

// Check authentication
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Detect if we're in a subdirectory (admin/ or includes/)
    $is_in_subdirectory = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
                           strpos($_SERVER['PHP_SELF'], '/includes/') !== false);
    $login_path = $is_in_subdirectory ? "../login.php" : "login.php";
    header("location: $login_path");
    exit;
}

// Check session timeout
check_session_timeout();

// Include CSRF protection
require_once __DIR__ . "/../config/csrf.php";
?>
