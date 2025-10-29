<?php
// File: includes/security_init.php
// Security initialization for all protected pages
// Include this at the top of every protected page

// Initialize secure session
require_once __DIR__ . "/../config/session.php";
init_secure_session();

// Check authentication
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: " . (__DIR__ . "/../" ? "../" : "") . "login.php");
    exit;
}

// Check session timeout
check_session_timeout();

// Include CSRF protection
require_once __DIR__ . "/../config/csrf.php";
?>
