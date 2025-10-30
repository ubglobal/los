<?php
// Simple index for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Simple Test</title></head><body>";
echo "<h1>Testing Index</h1>";

try {
    echo "<p>1. Starting session...</p>";
    require_once "config/session.php";
    init_secure_session();
    echo "<p>✓ Session OK</p>";

    echo "<p>2. Connecting to database...</p>";
    require_once "config/db.php";
    echo "<p>✓ Database OK</p>";

    echo "<p>3. Checking login status...</p>";
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        echo "<p>❌ Not logged in</p>";
        echo "<p><a href='login.php'>Go to Login</a></p>";
    } else {
        echo "<p>✓ Logged in as: " . htmlspecialchars($_SESSION['username']) . "</p>";
        echo "<p>Role: " . htmlspecialchars($_SESSION['role']) . "</p>";
        echo "<p>Branch: " . htmlspecialchars($_SESSION['branch'] ?? 'N/A') . "</p>";

        if ($_SESSION['role'] === 'Admin') {
            echo "<p>This is an Admin user - should redirect to admin/index.php</p>";
            echo "<p><a href='admin/index_simple.php'>Go to Simple Admin Dashboard</a></p>";
        } else {
            echo "<p>This is a regular user - should show dashboard</p>";
        }

        echo "<p><a href='logout.php'>Logout</a></p>";
    }

} catch (Throwable $e) {
    echo "<p style='color:red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
