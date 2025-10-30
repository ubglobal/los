<?php
/**
 * Comprehensive Database Schema Audit
 * Scans all tables and compares with code queries
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/db.php";
require_once "includes/functions.php";

echo "<!DOCTYPE html><html><head><title>Database Schema Audit</title>";
echo "<style>
body{font-family:Arial;max-width:1200px;margin:20px auto;padding:20px;}
.success{background:#d4edda;padding:10px;margin:10px 0;border-radius:5px;}
.error{background:#f8d7da;padding:10px;margin:10px 0;border-radius:5px;}
.warning{background:#fff3cd;padding:10px;margin:10px 0;border-radius:5px;}
table{width:100%;border-collapse:collapse;margin:10px 0;}
th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:12px;}
th{background:#004a99;color:white;}
.code{background:#f5f5f5;padding:2px 5px;font-family:monospace;font-size:11px;}
</style></head><body>";

echo "<h1>🔍 Database Schema Audit</h1>";
echo "<p>Checking all tables and comparing with code queries...</p>";

// Get all tables
$result = mysqli_query($link, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_array($result)) {
    $tables[] = $row[0];
}

echo "<div class='success'>Found " . count($tables) . " tables: " . implode(', ', $tables) . "</div>";

$all_issues = [];

// Define expected queries from code
$expected_queries = [
    'users' => [
        'SELECT' => ['id', 'username', 'email', 'password_hash', 'full_name', 'role', 'branch', 'approval_limit', 'is_active', 'created_at', 'updated_at'],
        'INSERT' => ['username', 'email', 'password_hash', 'full_name', 'role', 'branch', 'approval_limit'],
        'source' => 'includes/functions.php, admin/manage_users.php, install.php'
    ],
    'customers' => [
        'SELECT' => ['id', 'customer_code', 'full_name', 'customer_type', 'id_number', 'company_tax_code', 'phone', 'email', 'address', 'branch', 'created_at'],
        'INSERT' => ['customer_code', 'full_name', 'id_number', 'customer_type', 'phone', 'email', 'address', 'branch'],
        'source' => 'includes/functions.php, admin/manage_customers.php'
    ],
    'products' => [
        'SELECT' => ['id', 'product_code', 'product_name', 'description', 'max_term_months', 'interest_rate', 'is_active', 'created_at'],
        'INSERT' => ['product_code', 'product_name', 'description', 'max_term_months', 'interest_rate', 'is_active'],
        'source' => 'includes/functions.php, admin/manage_products.php'
    ],
    'collateral_types' => [
        'SELECT' => ['id', 'type_name', 'description', 'is_active', 'created_at'],
        'INSERT' => ['type_name', 'description', 'is_active'],
        'source' => 'includes/functions.php, admin/manage_collaterals.php'
    ]
];

// Check each critical table
foreach ($expected_queries as $table => $expectations) {
    echo "<hr>";
    echo "<h2>Table: <span class='code'>$table</span></h2>";

    if (!in_array($table, $tables)) {
        echo "<div class='error'>❌ Table does NOT exist in database!</div>";
        $all_issues[] = "Table '$table' missing";
        continue;
    }

    // Get actual columns
    $result = mysqli_query($link, "DESCRIBE `$table`");
    $actual_columns = [];
    echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $actual_columns[] = $row['Field'];
        echo "<tr>";
        echo "<td class='code'>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td class='code'>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td class='code'>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check SELECT queries
    echo "<h3>SELECT Query Check</h3>";
    echo "<p><strong>Expected columns in SELECT:</strong> " . implode(', ', $expectations['SELECT']) . "</p>";
    echo "<p><strong>Source:</strong> {$expectations['source']}</p>";

    $missing_select = array_diff($expectations['SELECT'], $actual_columns);
    $extra_columns = array_diff($actual_columns, $expectations['SELECT']);

    if (!empty($missing_select)) {
        echo "<div class='error'>";
        echo "❌ <strong>Missing columns in database:</strong> " . implode(', ', $missing_select);
        echo "</div>";
        foreach ($missing_select as $col) {
            $all_issues[] = "Table '$table': Missing column '$col' (used in SELECT)";
        }
    } else {
        echo "<div class='success'>✅ All SELECT columns exist in database</div>";
    }

    // Check INSERT queries
    echo "<h3>INSERT Query Check</h3>";
    echo "<p><strong>Expected columns in INSERT:</strong> " . implode(', ', $expectations['INSERT']) . "</p>";

    $missing_insert = array_diff($expectations['INSERT'], $actual_columns);

    if (!empty($missing_insert)) {
        echo "<div class='error'>";
        echo "❌ <strong>Missing columns for INSERT:</strong> " . implode(', ', $missing_insert);
        echo "</div>";
        foreach ($missing_insert as $col) {
            $all_issues[] = "Table '$table': Missing column '$col' (used in INSERT)";
        }
    } else {
        echo "<div class='success'>✅ All INSERT columns exist in database</div>";
    }

    // Count records
    $count_result = mysqli_query($link, "SELECT COUNT(*) as cnt FROM `$table`");
    $count_row = mysqli_fetch_assoc($count_result);
    $record_count = $count_row['cnt'];

    if ($record_count == 0) {
        echo "<div class='warning'>⚠️ Table is empty (0 records)</div>";
    } else {
        echo "<div class='success'>✅ Table has $record_count records</div>";
    }
}

// Summary
echo "<hr>";
echo "<h2>📊 Audit Summary</h2>";

if (empty($all_issues)) {
    echo "<div class='success'>";
    echo "<h3>✅ No Issues Found!</h3>";
    echo "<p>All expected columns exist in the database.</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Found " . count($all_issues) . " Issues:</h3>";
    echo "<ol>";
    foreach ($all_issues as $issue) {
        echo "<li>" . htmlspecialchars($issue) . "</li>";
    }
    echo "</ol>";
    echo "</div>";

    echo "<h3>🔧 Recommended Actions:</h3>";
    echo "<ol>";
    echo "<li>Review database.sql and add missing columns</li>";
    echo "<li>Update install.php to use correct column names</li>";
    echo "<li>Create/run migration script to fix existing installations</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<h3>Quick Links:</h3>";
echo "<ul>";
echo "<li><a href='admin/debug_admin.php'>Admin Debug</a></li>";
echo "<li><a href='admin/manage_users.php'>Manage Users</a></li>";
echo "<li><a href='admin/manage_customers.php'>Manage Customers</a></li>";
echo "<li><a href='admin/manage_products.php'>Manage Products</a></li>";
echo "</ul>";

echo "</body></html>";
?>
