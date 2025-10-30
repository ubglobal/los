<?php
// Debug admin pages
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Debug Admin Pages</title>";
echo "<style>body{font-family:Arial;max-width:1000px;margin:20px auto;padding:20px;}";
echo ".success{background:#d4edda;padding:10px;margin:10px 0;border-radius:5px;}";
echo ".error{background:#f8d7da;padding:10px;margin:10px 0;border-radius:5px;}";
echo "table{width:100%;border-collapse:collapse;}";
echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
echo "th{background:#004a99;color:white;}";
echo "</style></head><body>";

echo "<h1>🔍 Debug Admin Pages</h1>";

try {
    require_once "../config/session.php";
    init_secure_session();
    echo "<div class='success'>✓ Session initialized</div>";

    require_once "../config/db.php";
    echo "<div class='success'>✓ Database connected</div>";

    require_once "../config/csrf.php";
    echo "<div class='success'>✓ CSRF loaded</div>";

    require_once "../includes/functions.php";
    echo "<div class='success'>✓ Functions loaded</div>";

    // Check if logged in as admin
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        echo "<div class='error'>❌ Not logged in</div>";
        echo "<p><a href='../login.php'>Login</a></p>";
        exit;
    }

    if ($_SESSION['role'] !== 'Admin') {
        echo "<div class='error'>❌ Not admin. Your role: " . htmlspecialchars($_SESSION['role']) . "</div>";
        exit;
    }

    echo "<div class='success'>✓ Logged in as Admin: " . htmlspecialchars($_SESSION['full_name']) . "</div>";

    // Test get_all_users function
    echo "<h2>Test: get_all_users()</h2>";
    $all_users = get_all_users($link);
    if (empty($all_users)) {
        echo "<div class='error'>⚠️ No users found in database</div>";
    } else {
        echo "<div class='success'>✓ Found " . count($all_users) . " users</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Branch</th></tr>";
        foreach ($all_users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['branch']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Test database tables
    echo "<h2>Database Tables</h2>";
    $tables = ['users', 'customers', 'products', 'collateral_types'];
    foreach ($tables as $table) {
        $result = mysqli_query($link, "SELECT COUNT(*) as count FROM `$table`");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $count = $row['count'];
            if ($count == 0) {
                echo "<div class='error'>⚠️ Table '$table': 0 records (empty)</div>";
            } else {
                echo "<div class='success'>✓ Table '$table': $count records</div>";
            }
        } else {
            echo "<div class='error'>❌ Table '$table': Error - " . htmlspecialchars(mysqli_error($link)) . "</div>";
        }
    }

    // Check if manage_users.php can be loaded
    echo "<h2>Admin Pages Check</h2>";
    $admin_pages = [
        'manage_users.php' => 'Quản lý Users',
        'manage_customers.php' => 'Quản lý Customers',
        'manage_products.php' => 'Quản lý Products',
        'manage_collaterals.php' => 'Quản lý Collaterals'
    ];

    foreach ($admin_pages as $file => $title) {
        if (file_exists($file)) {
            echo "<div class='success'>✓ <a href='$file'>$title</a> - File exists</div>";
        } else {
            echo "<div class='error'>❌ $title - File NOT found</div>";
        }
    }

    echo "<h2>Next Steps</h2>";
    echo "<ul>";
    echo "<li><a href='manage_users.php'>Go to Manage Users</a></li>";
    echo "<li><a href='index.php'>Go to Admin Dashboard</a></li>";
    echo "<li><a href='../index.php'>Go to Main Dashboard</a></li>";
    echo "</ul>";

} catch (Throwable $e) {
    echo "<div class='error'>";
    echo "<h2>FATAL ERROR</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "\n\n";
    echo htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>
