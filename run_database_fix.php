<?php
/**
 * Database Fix Executor - Phase 2
 * Executes database_fix_phase2.sql to create missing tables
 */

require_once __DIR__ . '/config/db.php';

echo "==============================================\n";
echo "DATABASE FIX - Phase 2 Missing Tables\n";
echo "==============================================\n\n";

// Read SQL file
$sql_file = __DIR__ . '/database_fix_phase2.sql';
if (!file_exists($sql_file)) {
    die("ERROR: SQL file not found: $sql_file\n");
}

$sql = file_get_contents($sql_file);

// Split by semicolon (simple approach for this use case)
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        // Filter out comments and empty statements
        $stmt = trim($stmt);
        return !empty($stmt) &&
               strpos($stmt, '--') !== 0 &&
               strpos($stmt, 'USE ') !== 0 &&
               strpos($stmt, '/*') !== 0;
    }
);

echo "Found " . count($statements) . " SQL statements to execute.\n\n";

$success_count = 0;
$error_count = 0;

// Execute each statement
foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;

    // Extract table name for display (if CREATE TABLE)
    $table_name = '';
    if (preg_match('/CREATE TABLE.*?`?(\w+)`?\s*\(/i', $statement, $matches)) {
        $table_name = $matches[1];
        echo "Creating table: $table_name ... ";
    } elseif (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches)) {
        $table_name = $matches[1];
        echo "Inserting data into: $table_name ... ";
    } elseif (preg_match('/SELECT.*FROM.*information_schema/i', $statement)) {
        echo "Verifying tables ... ";
    } elseif (preg_match('/SELECT.*Status/i', $statement)) {
        echo "Final check ... ";
    } else {
        echo "Executing statement " . ($index + 1) . " ... ";
    }

    $result = mysqli_query($link, $statement);

    if ($result) {
        echo "✅ OK\n";
        $success_count++;

        // If it's a SELECT, show results
        if (is_object($result) && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "  → " . implode(' | ', $row) . "\n";
            }
        }
    } else {
        echo "❌ FAILED\n";
        echo "  Error: " . mysqli_error($link) . "\n";
        $error_count++;
    }
}

echo "\n==============================================\n";
echo "SUMMARY:\n";
echo "  ✅ Success: $success_count\n";
echo "  ❌ Errors: $error_count\n";
echo "==============================================\n\n";

// Verify tables exist
echo "VERIFICATION - Checking all required tables:\n";
$required_tables = [
    'application_history',
    'customer_credit_ratings',
    'customer_related_parties',
    'application_repayment_sources'
];

foreach ($required_tables as $table) {
    $check_sql = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($link, $check_sql);
    if ($result && mysqli_num_rows($result) > 0) {
        // Count rows
        $count_sql = "SELECT COUNT(*) as cnt FROM $table";
        $count_result = mysqli_query($link, $count_sql);
        $count_row = mysqli_fetch_assoc($count_result);
        echo "  ✅ $table (rows: {$count_row['cnt']})\n";
    } else {
        echo "  ❌ $table (NOT FOUND)\n";
    }
}

mysqli_close($link);

echo "\n✅ Database fix completed!\n";
?>
