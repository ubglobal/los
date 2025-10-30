<?php
/**
 * COMPREHENSIVE CODE AUDIT
 * Scans all PHP files, extracts SQL queries, compares with database schema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes

echo "<!DOCTYPE html><html><head><title>Comprehensive Code Audit</title>";
echo "<style>
body{font-family:Arial;max-width:1400px;margin:20px auto;padding:20px;background:#f5f5f5;}
h1{color:#004a99;border-bottom:3px solid #004a99;padding-bottom:10px;}
h2{color:#333;background:#e9ecef;padding:10px;border-left:4px solid #004a99;margin-top:30px;}
h3{color:#555;}
.success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:10px;margin:10px 0;border-radius:5px;}
.error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:10px;margin:10px 0;border-radius:5px;}
.warning{background:#fff3cd;border:1px solid #ffeaa7;color:#856404;padding:10px;margin:10px 0;border-radius:5px;}
.info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460;padding:10px;margin:10px 0;border-radius:5px;}
table{width:100%;border-collapse:collapse;margin:15px 0;background:white;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
th,td{border:1px solid #ddd;padding:10px;text-align:left;font-size:13px;}
th{background:#004a99;color:white;position:sticky;top:0;}
tr:hover{background:#f8f9fa;}
code{background:#f5f5f5;padding:2px 6px;font-family:monospace;color:#c7254e;border-radius:3px;}
.file-path{color:#6c757d;font-size:11px;font-family:monospace;}
.column-name{background:#e7f3ff;padding:2px 5px;border-radius:3px;display:inline-block;margin:2px;}
</style></head><body>";

echo "<h1>🔍 Comprehensive Code & Database Audit</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>";

// ============================================================================
// PART 1: SCAN ALL PHP FILES
// ============================================================================
echo "<h2>Part 1: Scanning PHP Files</h2>";

$php_files = [];
$directories = ['.', 'admin', 'includes', 'config'];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '/*.php');
        foreach ($files as $file) {
            $php_files[] = $file;
        }
    }
}

echo "<div class='info'>Found " . count($php_files) . " PHP files to analyze</div>";

// ============================================================================
// PART 2: EXTRACT SQL QUERIES
// ============================================================================
echo "<h2>Part 2: Extracting SQL Queries</h2>";

$all_queries = [];
$tables_used = [];

foreach ($php_files as $file) {
    $content = file_get_contents($file);

    // Extract SELECT queries
    preg_match_all('/SELECT\s+(.+?)\s+FROM\s+`?(\w+)`?/is', $content, $select_matches, PREG_SET_ORDER);
    foreach ($select_matches as $match) {
        $table = $match[2];
        $columns_str = $match[1];

        // Extract column names
        if (trim($columns_str) === '*') {
            $columns = ['*'];
        } else {
            preg_match_all('/(?:^|,|\s)([a-zA-Z_][a-zA-Z0-9_]*)/i', $columns_str, $col_matches);
            $columns = array_unique(array_filter($col_matches[1], function($col) {
                return !in_array(strtoupper($col), ['SELECT', 'DISTINCT', 'AS', 'FROM', 'WHERE', 'ORDER', 'GROUP', 'BY', 'LIMIT']);
            }));
        }

        if (!isset($all_queries[$table])) {
            $all_queries[$table] = ['SELECT' => [], 'INSERT' => [], 'UPDATE' => [], 'files' => []];
        }
        $all_queries[$table]['SELECT'] = array_unique(array_merge($all_queries[$table]['SELECT'], $columns));
        $all_queries[$table]['files'][] = $file;
        $tables_used[$table] = true;
    }

    // Extract INSERT queries
    preg_match_all('/INSERT\s+INTO\s+`?(\w+)`?\s*\(([^)]+)\)/is', $content, $insert_matches, PREG_SET_ORDER);
    foreach ($insert_matches as $match) {
        $table = $match[1];
        $columns_str = $match[2];

        preg_match_all('/`([^`]+)`/', $columns_str, $col_matches);
        $columns = $col_matches[1];

        if (!isset($all_queries[$table])) {
            $all_queries[$table] = ['SELECT' => [], 'INSERT' => [], 'UPDATE' => [], 'files' => []];
        }
        $all_queries[$table]['INSERT'] = array_unique(array_merge($all_queries[$table]['INSERT'], $columns));
        if (!in_array($file, $all_queries[$table]['files'])) {
            $all_queries[$table]['files'][] = $file;
        }
        $tables_used[$table] = true;
    }

    // Extract UPDATE queries
    preg_match_all('/UPDATE\s+`?(\w+)`?\s+SET\s+(.+?)(?:WHERE|$)/is', $content, $update_matches, PREG_SET_ORDER);
    foreach ($update_matches as $match) {
        $table = $match[1];
        $set_clause = $match[2];

        preg_match_all('/`?([a-zA-Z_][a-zA-Z0-9_]*)`?\s*=/', $set_clause, $col_matches);
        $columns = $col_matches[1];

        if (!isset($all_queries[$table])) {
            $all_queries[$table] = ['SELECT' => [], 'INSERT' => [], 'UPDATE' => [], 'files' => []];
        }
        $all_queries[$table]['UPDATE'] = array_unique(array_merge($all_queries[$table]['UPDATE'], $columns));
        if (!in_array($file, $all_queries[$table]['files'])) {
            $all_queries[$table]['files'][] = $file;
        }
        $tables_used[$table] = true;
    }
}

echo "<div class='success'>Extracted queries from " . count($php_files) . " files</div>";
echo "<div class='info'>Found " . count($tables_used) . " unique tables used in code: <code>" . implode(', ', array_keys($tables_used)) . "</code></div>";

// ============================================================================
// PART 3: PARSE DATABASE SCHEMA
// ============================================================================
echo "<h2>Part 3: Parsing database.sql Schema</h2>";

$sql_content = file_get_contents('database.sql');

// Extract CREATE TABLE statements
preg_match_all('/CREATE TABLE `([^`]+)`\s*\((.*?)\n\) ENGINE/s', $sql_content, $create_matches, PREG_SET_ORDER);

$db_schema = [];
foreach ($create_matches as $match) {
    $table = $match[1];
    $definition = $match[2];

    // Extract columns (excluding KEY definitions)
    preg_match_all('/^\s*`([^`]+)`\s+([A-Z][^\s,]+)/m', $definition, $col_matches, PREG_SET_ORDER);

    $columns = [];
    foreach ($col_matches as $col_match) {
        $col_name = $col_match[1];
        $col_type = $col_match[2];

        // Skip constraint definitions
        if (!in_array($col_name, ['PRIMARY', 'UNIQUE', 'KEY', 'FOREIGN', 'CONSTRAINT', 'INDEX'])) {
            $columns[$col_name] = $col_type;
        }
    }

    $db_schema[$table] = $columns;
}

echo "<div class='success'>Parsed " . count($db_schema) . " tables from database.sql</div>";

// ============================================================================
// PART 4: COMPARE CODE vs SCHEMA
// ============================================================================
echo "<h2>Part 4: Code vs Schema Comparison</h2>";

$all_issues = [];
$perfect_tables = [];

foreach ($all_queries as $table => $queries) {
    echo "<h3>Table: <code>$table</code></h3>";

    if (!isset($db_schema[$table])) {
        echo "<div class='error'>❌ Table <code>$table</code> used in code but NOT defined in database.sql!</div>";
        $all_issues[] = "Table '$table' missing from database.sql";
        echo "<div class='file-path'>Used in: " . implode(', ', array_unique($queries['files'])) . "</div>";
        continue;
    }

    $schema_columns = array_keys($db_schema[$table]);
    $table_issues = 0;

    // Check SELECT columns
    if (!empty($queries['SELECT'])) {
        $select_cols = array_diff($queries['SELECT'], ['*']);
        if (!empty($select_cols)) {
            $missing = array_diff($select_cols, $schema_columns);
            if (!empty($missing)) {
                echo "<div class='error'>❌ <strong>SELECT missing columns:</strong> " . implode(', ', array_map(function($c) { return "<code>$c</code>"; }, $missing)) . "</div>";
                foreach ($missing as $col) {
                    $all_issues[] = "Table '$table': SELECT uses non-existent column '$col'";
                    $table_issues++;
                }
            } else {
                echo "<div class='success'>✅ SELECT columns valid</div>";
            }
        }
    }

    // Check INSERT columns
    if (!empty($queries['INSERT'])) {
        $missing = array_diff($queries['INSERT'], $schema_columns);
        if (!empty($missing)) {
            echo "<div class='error'>❌ <strong>INSERT missing columns:</strong> " . implode(', ', array_map(function($c) { return "<code>$c</code>"; }, $missing)) . "</div>";
            foreach ($missing as $col) {
                $all_issues[] = "Table '$table': INSERT uses non-existent column '$col'";
                $table_issues++;
            }
        } else {
            echo "<div class='success'>✅ INSERT columns valid</div>";
        }
    }

    // Check UPDATE columns
    if (!empty($queries['UPDATE'])) {
        $missing = array_diff($queries['UPDATE'], $schema_columns);
        if (!empty($missing)) {
            echo "<div class='error'>❌ <strong>UPDATE missing columns:</strong> " . implode(', ', array_map(function($c) { return "<code>$c</code>"; }, $missing)) . "</div>";
            foreach ($missing as $col) {
                $all_issues[] = "Table '$table': UPDATE uses non-existent column '$col'";
                $table_issues++;
            }
        } else {
            echo "<div class='success'>✅ UPDATE columns valid</div>";
        }
    }

    // Show schema
    echo "<div class='info'><strong>Database schema (" . count($schema_columns) . " columns):</strong><br>";
    foreach ($schema_columns as $col) {
        echo "<span class='column-name'>$col</span> ";
    }
    echo "</div>";

    echo "<div class='file-path'><strong>Used in files:</strong> " . implode(', ', array_unique($queries['files'])) . "</div>";

    if ($table_issues === 0) {
        $perfect_tables[] = $table;
    }
}

// Check for tables in schema but not used in code
$unused_tables = array_diff(array_keys($db_schema), array_keys($all_queries));
if (!empty($unused_tables)) {
    echo "<h3>Tables in database.sql but NOT used in code:</h3>";
    echo "<div class='warning'>⚠️ These tables exist in database but no code uses them:<br>";
    foreach ($unused_tables as $table) {
        echo "<code>$table</code> ";
    }
    echo "</div>";
}

// ============================================================================
// PART 5: FINAL SUMMARY
// ============================================================================
echo "<h2>📊 Final Audit Summary</h2>";

echo "<table>";
echo "<tr><th>Metric</th><th>Count</th></tr>";
echo "<tr><td>PHP files scanned</td><td><strong>" . count($php_files) . "</strong></td></tr>";
echo "<tr><td>Tables in database.sql</td><td><strong>" . count($db_schema) . "</strong></td></tr>";
echo "<tr><td>Tables used in code</td><td><strong>" . count($all_queries) . "</strong></td></tr>";
echo "<tr><td>Tables with perfect match</td><td><strong>" . count($perfect_tables) . "</strong></td></tr>";
echo "<tr><td>Tables with issues</td><td><strong>" . (count($all_queries) - count($perfect_tables)) . "</strong></td></tr>";
echo "<tr><td>Total issues found</td><td><strong>" . count($all_issues) . "</strong></td></tr>";
echo "</table>";

if (count($all_issues) === 0) {
    echo "<div class='success'>";
    echo "<h3>✅ AUDIT PASSED - NO ISSUES!</h3>";
    echo "<p>All code queries match database schema perfectly.</p>";
    echo "<p><strong>Perfect tables:</strong> " . implode(', ', $perfect_tables) . "</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ AUDIT FAILED - " . count($all_issues) . " Issues Found</h3>";
    echo "<ol>";
    foreach ($all_issues as $issue) {
        echo "<li>" . htmlspecialchars($issue) . "</li>";
    }
    echo "</ol>";
    echo "</div>";

    echo "<h3>🔧 Recommended Actions:</h3>";
    echo "<ol>";
    echo "<li>Review and fix all column mismatches in database.sql</li>";
    echo "<li>OR update PHP code to use correct column names</li>";
    echo "<li>Run this audit again after fixes</li>";
    echo "<li>Test fresh installation</li>";
    echo "</ol>";
}

echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='validate_schema.php'>Validate Schema (INSERT statements)</a></li>";
echo "<li><a href='install.php'>Run Fresh Installation</a></li>";
echo "</ul>";

echo "</body></html>";
?>
