<?php
// Simple admin index for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Simple Admin Test</title></head><body>";
echo "<h1>Testing Admin Index</h1>";

try {
    echo "<p>1. Starting session...</p>";
    require_once "../config/session.php";
    init_secure_session();
    echo "<p>✓ Session OK</p>";

    echo "<p>2. Checking admin access...</p>";
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        echo "<p>❌ Not logged in - <a href='../login.php'>Login</a></p>";
        exit;
    }

    if ($_SESSION['role'] !== 'Admin') {
        echo "<p>❌ Not an admin user (Role: " . htmlspecialchars($_SESSION['role']) . ")</p>";
        echo "<p><a href='../index_simple.php'>Back to Dashboard</a></p>";
        exit;
    }

    echo "<p>✓ Admin access granted</p>";
    echo "<p>Welcome, " . htmlspecialchars($_SESSION['full_name']) . "!</p>";

    echo "<h2>Admin Dashboard</h2>";
    echo "<p>This is the simplified admin dashboard for testing.</p>";
    echo "<ul>";
    echo "<li><a href='manage_users.php'>Manage Users</a></li>";
    echo "<li><a href='manage_customers.php'>Manage Customers</a></li>";
    echo "<li><a href='manage_products.php'>Manage Products</a></li>";
    echo "</ul>";

    echo "<p><a href='../logout.php'>Logout</a></p>";

} catch (Throwable $e) {
    echo "<p style='color:red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
