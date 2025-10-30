<?php
/**
 * Pre-Installation Database Schema Validator
 *
 * Validates database.sql file before running installer to catch
 * any schema/data mismatches that would cause installation errors.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Database Schema Validator</title>";
echo "<style>
body{font-family:Arial;max-width:1000px;margin:30px auto;padding:20px;background:#f5f5f5;}
h1{color:#004a99;}
h2{color:#333;border-bottom:2px solid #004a99;padding-bottom:10px;margin-top:30px;}
.success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:12px;margin:10px 0;border-radius:5px;}
.error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:12px;margin:10px 0;border-radius:5px;}
.warning{background:#fff3cd;border:1px solid #ffeaa7;color:#856404;padding:12px;margin:10px 0;border-radius:5px;}
.info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460;padding:12px;margin:10px 0;border-radius:5px;}
table{width:100%;border-collapse:collapse;margin:10px 0;background:white;}
th,td{border:1px solid #ddd;padding:8px;text-align:left;}
th{background:#004a99;color:white;}
code{background:#f5f5f5;padding:2px 5px;font-family:monospace;color:#c7254e;}
</style></head><body>";

echo "<h1>🔍 Database Schema Validator</h1>";
echo "<p>This tool validates database.sql before installation to prevent errors.</p>";

$sql_file = __DIR__ . '/database.sql';

if (!file_exists($sql_file)) {
    echo "<div class='error'>❌ database.sql not found at: " . htmlspecialchars($sql_file) . "</div>";
    exit;
}

echo "<div class='success'>✅ Found database.sql</div>";

// Read SQL file
$sql_content = file_get_contents($sql_file);

// Extract CREATE TABLE statements
preg_match_all('/CREATE TABLE `([^`]+)`\s*\((.*?)\)/s', $sql_content, $create_matches, PREG_SET_ORDER);

$tables_schema = [];
foreach ($create_matches as $match) {
    $table_name = $match[1];
    $columns_def = $match[2];

    // Extract column names
    preg_match_all('/`([^`]+)`\s+([^\s,]+)/', $columns_def, $col_matches, PREG_SET_ORDER);

    $columns = [];
    foreach ($col_matches as $col_match) {
        // Skip constraints like PRIMARY KEY, UNIQUE KEY, KEY, FOREIGN KEY
        if (!in_array($col_match[1], ['id', 'username', 'customer_code', 'product_code', 'type_name', 'doc_name']) &&
            strpos($col_match[1], '_') === false) {
            continue;
        }
        if (!in_array(strtoupper($col_match[2]), ['PRIMARY', 'UNIQUE', 'KEY', 'FOREIGN', 'CONSTRAINT'])) {
            $columns[] = $col_match[1];
        }
    }

    $tables_schema[$table_name] = $columns;
}

echo "<h2>Schema Analysis</h2>";
echo "<table>";
echo "<tr><th>Table</th><th>Columns Found</th></tr>";
foreach ($tables_schema as $table => $columns) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($table) . "</strong></td>";
    echo "<td><code>" . implode(', ', array_map('htmlspecialchars', $columns)) . "</code></td>";
    echo "</tr>";
}
echo "</table>";

// Extract INSERT statements
preg_match_all('/INSERT INTO `([^`]+)`\s*\(([^)]+)\)\s*VALUES/i', $sql_content, $insert_matches, PREG_SET_ORDER);

$issues = [];

echo "<h2>INSERT Statement Validation</h2>";

foreach ($insert_matches as $insert_match) {
    $table_name = $insert_match[1];
    $insert_columns_str = $insert_match[2];

    // Extract column names from INSERT
    preg_match_all('/`([^`]+)`/', $insert_columns_str, $insert_col_matches);
    $insert_columns = $insert_col_matches[1];

    echo "<div class='info'>";
    echo "<strong>Table: <code>$table_name</code></strong><br>";
    echo "INSERT columns: <code>" . implode(', ', $insert_columns) . "</code>";
    echo "</div>";

    // Check if table exists in schema
    if (!isset($tables_schema[$table_name])) {
        echo "<div class='error'>❌ Table <code>$table_name</code> not found in CREATE TABLE statements!</div>";
        $issues[] = "Table '$table_name' has INSERT but no CREATE TABLE";
        continue;
    }

    // Check if all INSERT columns exist in table
    $schema_columns = $tables_schema[$table_name];
    $missing_columns = array_diff($insert_columns, $schema_columns);

    if (!empty($missing_columns)) {
        echo "<div class='error'>";
        echo "❌ <strong>Column mismatch!</strong><br>";
        echo "INSERT uses columns not in CREATE TABLE: <code>" . implode(', ', $missing_columns) . "</code><br>";
        echo "Available columns: <code>" . implode(', ', $schema_columns) . "</code>";
        echo "</div>";

        foreach ($missing_columns as $col) {
            $issues[] = "Table '$table_name': INSERT uses non-existent column '$col'";
        }
    } else {
        echo "<div class='success'>✅ All INSERT columns exist in table schema</div>";
    }
}

// Summary
echo "<h2>📊 Validation Summary</h2>";

if (empty($issues)) {
    echo "<div class='success'>";
    echo "<h3>✅ Validation PASSED!</h3>";
    echo "<p>database.sql is valid and ready for installation.</p>";
    echo "<p><strong>Next step:</strong> <a href='install.php'>Run Installer</a></p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Validation FAILED!</h3>";
    echo "<p>Found " . count($issues) . " issue(s) that will cause installation errors:</p>";
    echo "<ol>";
    foreach ($issues as $issue) {
        echo "<li>" . htmlspecialchars($issue) . "</li>";
    }
    echo "</ol>";
    echo "<p><strong>Action required:</strong> Fix database.sql before running installer.</p>";
    echo "</div>";
}

echo "<h3>Additional Checks:</h3>";
echo "<ul>";
echo "<li><a href='audit_database.php'>Full Database Audit</a> (requires installed database)</li>";
echo "<li><a href='test_includes.php'>Test Include Files</a></li>";
echo "</ul>";

echo "</body></html>";
?>
