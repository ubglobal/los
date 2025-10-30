<?php
// Test each include file individually
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test Includes</title></head><body>";
echo "<h1>Testing Include Files</h1>";

$files_to_test = [
    'config/session.php',
    'config/db.php',
    'config/csrf.php',
    'config/rate_limit.php',
    'includes/functions.php',
    'includes/workflow_engine.php',
    'includes/facility_functions.php',
    'includes/disbursement_functions.php',
    'includes/permission_functions.php',
    'includes/header.php'
];

foreach ($files_to_test as $file) {
    echo "<p>Testing: <strong>$file</strong>... ";

    if (!file_exists($file)) {
        echo "<span style='color:red;'>❌ FILE NOT FOUND</span></p>";
        continue;
    }

    try {
        // Test if file has syntax errors by including it
        $before = get_defined_functions()['user'];
        require_once $file;
        $after = get_defined_functions()['user'];

        $new_functions = array_diff($after, $before);
        $func_count = count($new_functions);

        echo "<span style='color:green;'>✓ OK</span>";
        if ($func_count > 0) {
            echo " (defined $func_count functions)";
        }
        echo "</p>";

    } catch (Throwable $e) {
        echo "<span style='color:red;'>❌ ERROR</span></p>";
        echo "<pre style='background:#ffeeee; padding:10px; border:1px solid red;'>";
        echo htmlspecialchars($e->getMessage()) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all files show ✓ OK, the issue is likely in the logic, not the includes.</p>";
echo "<p><a href='index_simple.php'>Try Simple Index</a> | <a href='login.php'>Back to Login</a></p>";

echo "</body></html>";
?>
