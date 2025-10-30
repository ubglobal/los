<?php
/**
 * LOS v3.0 - Automated System Tester
 *
 * This tool performs automated tests on various system components
 * Run this after each audit phase to verify fixes
 *
 * Usage: Access via browser http://your-domain/system_tester.php
 * Or CLI: php system_tester.php
 */

// Prevent direct access in production
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
if (!in_array($_SERVER['REMOTE_ADDR'] ?? 'cli', array_merge($allowed_ips, ['cli']))) {
    die('Access denied. This tool is for development only.');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Load configuration
require_once 'config/db.php';

// Test results storage
$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'skipped' => 0,
    'tests' => []
];

/**
 * Add test result
 */
function addTest($category, $name, $status, $message = '', $details = '') {
    global $results;

    $results['total']++;
    $results[$status]++;

    $results['tests'][] = [
        'category' => $category,
        'name' => $name,
        'status' => $status, // 'passed', 'failed', 'skipped'
        'message' => $message,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Test database connection
 */
function testDatabaseConnection() {
    global $conn;

    if ($conn && $conn->ping()) {
        addTest('Core', 'Database Connection', 'passed', 'Connected to: ' . DB_NAME);
        return true;
    } else {
        addTest('Core', 'Database Connection', 'failed', 'Cannot connect to database');
        return false;
    }
}

/**
 * Test database schema
 */
function testDatabaseSchema() {
    global $conn;

    $required_tables = [
        'users', 'customers', 'products', 'collateral_types', 'document_definitions',
        'credit_applications', 'application_collaterals', 'application_documents',
        'facilities', 'disbursements', 'disbursement_conditions'
    ];

    $missing_tables = [];

    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            $missing_tables[] = $table;
        }
    }

    if (empty($missing_tables)) {
        addTest('Database', 'Schema Tables', 'passed', 'All ' . count($required_tables) . ' required tables exist');
        return true;
    } else {
        addTest('Database', 'Schema Tables', 'failed', 'Missing tables: ' . implode(', ', $missing_tables));
        return false;
    }
}

/**
 * Test table columns against expected schema
 */
function testTableColumns($table, $expected_columns) {
    global $conn;

    $result = $conn->query("DESCRIBE `$table`");
    if (!$result) {
        addTest('Database', "$table columns", 'failed', 'Cannot describe table');
        return false;
    }

    $actual_columns = [];
    while ($row = $result->fetch_assoc()) {
        $actual_columns[] = $row['Field'];
    }

    $missing = array_diff($expected_columns, $actual_columns);
    $extra = array_diff($actual_columns, $expected_columns);

    if (empty($missing) && empty($extra)) {
        addTest('Database', "$table columns", 'passed', count($actual_columns) . ' columns match');
        return true;
    } else {
        $msg = '';
        if (!empty($missing)) $msg .= 'Missing: ' . implode(', ', $missing) . '. ';
        if (!empty($extra)) $msg .= 'Extra: ' . implode(', ', $extra);
        addTest('Database', "$table columns", 'failed', trim($msg));
        return false;
    }
}

/**
 * Test critical table schemas
 */
function testCriticalSchemas() {
    // Users table
    testTableColumns('users', [
        'id', 'username', 'email', 'password_hash', 'full_name', 'role',
        'branch', 'is_active', 'approval_limit', 'created_at', 'updated_at'
    ]);

    // Customers table
    testTableColumns('customers', [
        'id', 'customer_code', 'customer_type', 'full_name', 'id_number', 'dob',
        'company_tax_code', 'company_representative', 'address', 'phone_number',
        'email', 'branch', 'is_active', 'created_at', 'updated_at'
    ]);

    // Products table
    testTableColumns('products', [
        'id', 'name', 'description', 'is_active', 'created_at', 'updated_at'
    ]);

    // Credit applications table
    testTableColumns('credit_applications', [
        'id', 'hstd_code', 'customer_id', 'product_id', 'amount', 'term_months',
        'purpose', 'status', 'stage', 'created_by_id', 'assigned_to_id',
        'reviewed_by_id', 'approved_by_id', 'rejection_reason', 'created_at',
        'updated_at', 'sla_target_date', 'sla_status', 'legal_completed',
        'legal_completed_date', 'legal_completed_by_id'
    ]);
}

/**
 * Test foreign key constraints
 */
function testForeignKeys() {
    global $conn;

    $constraints = [
        'credit_applications' => ['customer_id' => 'customers', 'product_id' => 'products'],
        'facilities' => ['application_id' => 'credit_applications'],
        'disbursements' => ['application_id' => 'credit_applications', 'facility_id' => 'facilities']
    ];

    foreach ($constraints as $table => $fks) {
        foreach ($fks as $fk_column => $ref_table) {
            // Check if FK constraint exists
            $sql = "SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = '" . DB_NAME . "'
                    AND TABLE_NAME = '$table'
                    AND COLUMN_NAME = '$fk_column'
                    AND REFERENCED_TABLE_NAME = '$ref_table'";

            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                addTest('Database', "FK: $table.$fk_column → $ref_table", 'passed', 'Constraint exists');
            } else {
                addTest('Database', "FK: $table.$fk_column → $ref_table", 'failed', 'Constraint missing or invalid');
            }
        }
    }
}

/**
 * Test demo data exists
 */
function testDemoData() {
    global $conn;

    $tables_to_check = [
        'users' => 10,
        'customers' => 50,
        'products' => 4,
        'credit_applications' => 110,
        'facilities' => 40,
        'disbursements' => 50
    ];

    foreach ($tables_to_check as $table => $expected_min) {
        $result = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
        if ($result) {
            $row = $result->fetch_assoc();
            $count = $row['cnt'];

            if ($count >= $expected_min) {
                addTest('Data', "$table records", 'passed', "$count records (expected min: $expected_min)");
            } else {
                addTest('Data', "$table records", 'failed', "Only $count records (expected min: $expected_min)");
            }
        } else {
            addTest('Data', "$table records", 'failed', 'Cannot count records');
        }
    }
}

/**
 * Test critical files exist
 */
function testCriticalFiles() {
    $critical_files = [
        'config/db.php' => 'Database configuration',
        'config/session.php' => 'Session configuration',
        'includes/functions.php' => 'Core functions',
        'includes/header.php' => 'Main header',
        'login.php' => 'Login page',
        'index.php' => 'Dashboard',
        'create_application.php' => 'Create application',
        'application_detail.php' => 'Application detail',
        'admin/manage_users.php' => 'User management',
        'admin/manage_customers.php' => 'Customer management'
    ];

    foreach ($critical_files as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            addTest('Files', $description, 'passed', $file . ' exists');
        } else {
            addTest('Files', $description, 'failed', $file . ' NOT FOUND');
        }
    }
}

/**
 * Test PHP configuration
 */
function testPHPConfiguration() {
    // Check PHP version
    $php_version = phpversion();
    if (version_compare($php_version, '7.4.0', '>=')) {
        addTest('PHP', 'PHP Version', 'passed', 'PHP ' . $php_version);
    } else {
        addTest('PHP', 'PHP Version', 'failed', 'PHP ' . $php_version . ' (requires 7.4+)');
    }

    // Check required extensions
    $required_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'mbstring', 'session'];
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            addTest('PHP', "Extension: $ext", 'passed', 'Loaded');
        } else {
            addTest('PHP', "Extension: $ext", 'failed', 'NOT loaded');
        }
    }

    // Check file upload settings
    $upload_max = ini_get('upload_max_filesize');
    $post_max = ini_get('post_max_size');
    addTest('PHP', 'File upload limits', 'passed', "upload_max_filesize: $upload_max, post_max_size: $post_max");
}

/**
 * Test user roles and permissions
 */
function testUserRoles() {
    global $conn;

    $expected_roles = ['CVQHKH', 'CVTĐ', 'CPD', 'GDK', 'Kiểm soát', 'Thủ quỹ', 'Admin'];

    $result = $conn->query("SELECT DISTINCT role FROM users WHERE is_active = 1");
    $actual_roles = [];
    while ($row = $result->fetch_assoc()) {
        $actual_roles[] = $row['role'];
    }

    $missing_roles = array_diff($expected_roles, $actual_roles);

    if (empty($missing_roles)) {
        addTest('Users', 'User roles', 'passed', 'All ' . count($expected_roles) . ' roles have users');
    } else {
        addTest('Users', 'User roles', 'failed', 'Missing roles: ' . implode(', ', $missing_roles));
    }
}

/**
 * Test application statuses
 */
function testApplicationStatuses() {
    global $conn;

    $expected_statuses = ['Bản nháp', 'Đang xử lý', 'Đã phê duyệt', 'Từ chối', 'Yêu cầu bổ sung', 'Đã hủy'];

    $result = $conn->query("SELECT DISTINCT status FROM credit_applications");
    $actual_statuses = [];
    while ($row = $result->fetch_assoc()) {
        $actual_statuses[] = $row['status'];
    }

    $missing_statuses = array_diff($expected_statuses, $actual_statuses);

    if (empty($missing_statuses)) {
        addTest('Applications', 'Application statuses', 'passed', 'All statuses have data');
    } else {
        addTest('Applications', 'Application statuses', 'failed', 'Missing: ' . implode(', ', $missing_statuses));
    }
}

/**
 * Display results in HTML
 */
function displayHTMLResults() {
    global $results;

    $pass_rate = $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 1) : 0;

    echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>LOS v3.0 - System Test Results</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .stat-card { padding: 20px; border-radius: 6px; text-align: center; }
        .stat-card.total { background: #e3f2fd; border: 2px solid #2196f3; }
        .stat-card.passed { background: #e8f5e9; border: 2px solid #4caf50; }
        .stat-card.failed { background: #ffebee; border: 2px solid #f44336; }
        .stat-card.skipped { background: #fff3e0; border: 2px solid #ff9800; }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; color: #666; }
        .stat-card .number { font-size: 36px; font-weight: bold; }
        .progress-bar { background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden; margin: 20px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #4caf50, #8bc34a); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; transition: width 0.3s; }
        .test-results { margin-top: 30px; }
        .category { margin-bottom: 30px; }
        .category h3 { background: #f5f5f5; padding: 10px 15px; border-left: 4px solid #2196f3; margin: 0 0 10px 0; }
        .test-item { padding: 12px 15px; margin: 5px 0; border-left: 4px solid #ccc; background: #fafafa; border-radius: 4px; }
        .test-item.passed { border-left-color: #4caf50; background: #f1f8f4; }
        .test-item.failed { border-left-color: #f44336; background: #fff5f5; }
        .test-item.skipped { border-left-color: #ff9800; background: #fff8f0; }
        .test-name { font-weight: 600; color: #333; }
        .test-message { color: #666; font-size: 14px; margin-top: 5px; }
        .test-details { color: #999; font-size: 12px; margin-top: 5px; font-family: monospace; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-left: 10px; }
        .status-badge.passed { background: #4caf50; color: white; }
        .status-badge.failed { background: #f44336; color: white; }
        .status-badge.skipped { background: #ff9800; color: white; }
        .timestamp { color: #999; font-size: 12px; float: right; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 LOS v3.0 - System Test Results</h1>

        <div class='summary'>
            <div class='stat-card total'>
                <h3>Total Tests</h3>
                <div class='number'>{$results['total']}</div>
            </div>
            <div class='stat-card passed'>
                <h3>Passed</h3>
                <div class='number'>{$results['passed']}</div>
            </div>
            <div class='stat-card failed'>
                <h3>Failed</h3>
                <div class='number'>{$results['failed']}</div>
            </div>
            <div class='stat-card skipped'>
                <h3>Skipped</h3>
                <div class='number'>{$results['skipped']}</div>
            </div>
        </div>

        <div class='progress-bar'>
            <div class='progress-fill' style='width: {$pass_rate}%'>
                {$pass_rate}% Pass Rate
            </div>
        </div>

        <div class='test-results'>";

    // Group tests by category
    $categories = [];
    foreach ($results['tests'] as $test) {
        $categories[$test['category']][] = $test;
    }

    foreach ($categories as $category => $tests) {
        echo "<div class='category'>
                <h3>{$category} (" . count($tests) . " tests)</h3>";

        foreach ($tests as $test) {
            echo "<div class='test-item {$test['status']}'>
                    <div class='test-name'>
                        {$test['name']}
                        <span class='status-badge {$test['status']}'>{$test['status']}</span>
                        <span class='timestamp'>{$test['timestamp']}</span>
                    </div>";

            if ($test['message']) {
                echo "<div class='test-message'>{$test['message']}</div>";
            }

            if ($test['details']) {
                echo "<div class='test-details'>{$test['details']}</div>";
            }

            echo "</div>";
        }

        echo "</div>";
    }

    echo "</div>
    </div>
</body>
</html>";
}

// ============================================================================
// RUN TESTS
// ============================================================================

echo "\n";
echo "=" . str_repeat("=", 78) . "\n";
echo "  LOS v3.0 - AUTOMATED SYSTEM TESTER\n";
echo "=" . str_repeat("=", 78) . "\n\n";

// Phase 1: Core Tests
echo "Running Phase 1: Core Tests...\n";
testDatabaseConnection();
testPHPConfiguration();
testCriticalFiles();

// Phase 2: Database Tests
echo "Running Phase 2: Database Tests...\n";
testDatabaseSchema();
testCriticalSchemas();
testForeignKeys();

// Phase 3: Data Tests
echo "Running Phase 3: Data Tests...\n";
testDemoData();
testUserRoles();
testApplicationStatuses();

// Display results
if (php_sapi_name() === 'cli') {
    // CLI output
    echo "\n";
    echo str_repeat("=", 80) . "\n";
    echo "TEST RESULTS\n";
    echo str_repeat("=", 80) . "\n\n";

    echo "Total:   {$results['total']}\n";
    echo "Passed:  {$results['passed']}\n";
    echo "Failed:  {$results['failed']}\n";
    echo "Skipped: {$results['skipped']}\n\n";

    if ($results['failed'] > 0) {
        echo "FAILED TESTS:\n";
        foreach ($results['tests'] as $test) {
            if ($test['status'] === 'failed') {
                echo "  - [{$test['category']}] {$test['name']}: {$test['message']}\n";
            }
        }
    }

    echo "\nFor detailed HTML report, access this script via browser.\n\n";
} else {
    // Browser output
    displayHTMLResults();
}
