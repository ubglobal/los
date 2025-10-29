<?php
// File: admin/includes/admin_init.php
// Security initialization for all admin pages
// Include this at the top of every admin page

// Initialize secure session
require_once __DIR__ . "/../../config/session.php";
init_secure_session();

// Check authentication
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Check admin role
if ($_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    die("Access Denied. Admin privileges required.");
}

// Check session timeout
check_session_timeout();

// Include CSRF protection
require_once __DIR__ . "/../../config/csrf.php";

// Include database connection
require_once __DIR__ . "/../../config/db.php";

// Include functions
require_once __DIR__ . "/../../includes/functions.php";
?>
