<?php
/**
 * Migration Script: Fix customers table structure
 *
 * This script updates the customers table to match the code requirements:
 * - Renames 'phone' to 'phone_number'
 * - Adds 'dob' column for date of birth
 * - Adds 'company_tax_code' for companies
 * - Adds 'company_representative' for companies
 * - Updates customer_type enum values
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/db.php";

echo "<!DOCTYPE html><html><head><title>Fix Customers Table</title>";
echo "<style>body{font-family:Arial;max-width:900px;margin:50px auto;padding:20px;}";
echo ".success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".warning{background:#fff3cd;border:1px solid #ffeaa7;color:#856404;padding:15px;margin:10px 0;border-radius:5px;}";
echo "</style></head><body>";

echo "<h1>🔧 Database Migration: Fix Customers Table</h1>";

// Step 1: Check if table exists
echo "<h2>Step 1: Checking if customers table exists...</h2>";
$result = mysqli_query($link, "SHOW TABLES LIKE 'customers'");
if (mysqli_num_rows($result) == 0) {
    echo "<div class='error'>❌ Table 'customers' does not exist! Please run install.php first.</div>";
    exit;
}
echo "<div class='success'>✓ Table 'customers' exists</div>";

// Step 2: Get current structure
echo "<h2>Step 2: Analyzing current table structure...</h2>";
$result = mysqli_query($link, "DESCRIBE customers");
$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[$row['Field']] = $row;
}

echo "<div class='info'><strong>Current columns:</strong> " . implode(', ', array_keys($columns)) . "</div>";

$migrations_needed = [];

// Check each required column
$required_changes = [
    'phone_number' => ['check' => !isset($columns['phone_number']) && isset($columns['phone']),
                      'action' => "CHANGE `phone` `phone_number` VARCHAR(20) DEFAULT NULL",
                      'description' => "Rename 'phone' to 'phone_number'"],
    'dob' => ['check' => !isset($columns['dob']),
             'action' => "ADD COLUMN `dob` DATE DEFAULT NULL COMMENT 'Date of birth for individuals' AFTER `id_number`",
             'description' => "Add 'dob' (date of birth) column"],
    'company_tax_code' => ['check' => !isset($columns['company_tax_code']),
                          'action' => "ADD COLUMN `company_tax_code` VARCHAR(50) DEFAULT NULL COMMENT 'Tax code for companies' AFTER `dob`",
                          'description' => "Add 'company_tax_code' column"],
    'company_representative' => ['check' => !isset($columns['company_representative']),
                                'action' => "ADD COLUMN `company_representative` VARCHAR(100) DEFAULT NULL COMMENT 'Representative for companies' AFTER `company_tax_code`",
                                'description' => "Add 'company_representative' column"]
];

foreach ($required_changes as $col => $info) {
    if ($info['check']) {
        $migrations_needed[] = $info;
        echo "<div class='warning'>⚠️ {$info['description']}</div>";
    } else {
        echo "<div class='success'>✓ Column '$col' already correct</div>";
    }
}

// Check customer_type enum
if (isset($columns['customer_type'])) {
    $type_info = $columns['customer_type']['Type'];
    if (strpos($type_info, 'CÁ NHÂN') === false) {
        $migrations_needed[] = [
            'action' => "MODIFY COLUMN `customer_type` ENUM('CÁ NHÂN','DOANH NGHIỆP') NOT NULL DEFAULT 'CÁ NHÂN'",
            'description' => "Update customer_type enum values"
        ];
        echo "<div class='warning'>⚠️ Need to update customer_type enum values</div>";
    } else {
        echo "<div class='success'>✓ customer_type enum already correct</div>";
    }
}

if (empty($migrations_needed)) {
    echo "<h2>✅ No Migrations Needed!</h2>";
    echo "<div class='success'>Your customers table is already up to date.</div>";
    echo "<p><a href='admin/manage_customers.php'>Go to Manage Customers</a></p>";
    exit;
}

// Step 3: Apply migrations
echo "<h2>Step 3: Applying migrations...</h2>";

$success_count = 0;
$error_count = 0;

foreach ($migrations_needed as $migration) {
    echo "<p><strong>Applying:</strong> {$migration['description']}...</p>";
    $sql = "ALTER TABLE `customers` {$migration['action']}";

    if (mysqli_query($link, $sql)) {
        echo "<div class='success'>✓ Success</div>";
        $success_count++;
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars(mysqli_error($link)) . "</div>";
        $error_count++;
    }
}

// Step 4: Verify
echo "<h2>Step 4: Verifying changes...</h2>";
$result = mysqli_query($link, "DESCRIBE customers");
$new_columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $new_columns[] = $row['Field'];
}

$required_cols = ['phone_number', 'dob', 'company_tax_code', 'company_representative'];
$missing = array_diff($required_cols, $new_columns);

if (empty($missing)) {
    echo "<div class='success'>";
    echo "<h3>✅ Migration Complete!</h3>";
    echo "<p>Applied $success_count changes successfully.</p>";
    echo "<p><strong>New columns:</strong> " . implode(', ', $new_columns) . "</p>";
    echo "</div>";

    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li><a href='admin/manage_customers.php'>Go to Manage Customers</a></li>";
    echo "<li><a href='admin/debug_admin.php'>Run Admin Debug</a></li>";
    echo "<li><a href='admin/index.php'>Go to Admin Dashboard</a></li>";
    echo "</ul>";
} else {
    echo "<div class='error'>";
    echo "❌ Some columns are still missing: " . implode(', ', $missing);
    echo "</div>";
}

if ($error_count > 0) {
    echo "<div class='warning'>⚠️ $error_count migrations failed. Please check errors above.</div>";
}

echo "</body></html>";
?>
