<?php
// File: logout.php - SECURE VERSION
require_once "config/session.php";
init_secure_session();

// Log logout event
if (isset($_SESSION['username'])) {
    error_log("User logout: " . $_SESSION['username']);
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("location: login.php");
exit;
?>
