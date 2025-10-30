<?php
/**
 * Migration Script: Add approval_limit column to users table
 *
 * This script adds the 'approval_limit' column to the users table
 * for CPD/GDK approval limit tracking.
 *
 * Run this ONCE after updating to the latest code.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/db.php";

echo "<!DOCTYPE html><html><head><title>Add Approval Limit Column</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px;}";
echo "</style></head><body>";

echo "<h1>🔧 Database Migration: Add approval_limit Column</h1>";

// Step 1: Check current table structure
echo "<h2>Step 1: Checking current table structure...</h2>";

$result = mysqli_query($link, "DESCRIBE users");
$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[$row['Field']] = $row;
}

echo "<div class='info'>";
echo "<strong>Current columns in users table:</strong><br>";
echo implode(', ', array_keys($columns));
echo "</div>";

// Check if approval_limit already exists
if (isset($columns['approval_limit'])) {
    echo "<div class='success'>";
    echo "✅ <strong>Column 'approval_limit' already exists!</strong><br>";
    echo "Type: " . $columns['approval_limit']['Type'] . "<br>";
    echo "Null: " . $columns['approval_limit']['Null'] . "<br>";
    echo "Default: " . ($columns['approval_limit']['Default'] ?? 'NULL') . "<br>";
    echo "<br>No migration needed. Your database is up to date.";
    echo "</div>";

    echo "<h2>✅ All Done!</h2>";
    echo "<div class='success'>";
    echo "<strong>You can now:</strong><br>";
    echo "1. <a href='admin/debug_admin.php'>Run Admin Debug Again</a><br>";
    echo "2. <a href='admin/manage_users.php'>Go to Manage Users</a><br>";
    echo "3. <a href='admin/index.php'>Go to Admin Dashboard</a>";
    echo "</div>";
    exit;
}

// If we reach here, we need to add the column
echo "<div class='info'>";
echo "ℹ️ Column 'approval_limit' not found. Need to add it.";
echo "</div>";

echo "<h2>Step 2: Adding approval_limit column...</h2>";

// Add the column
$sql = "ALTER TABLE `users` ADD COLUMN `approval_limit` DECIMAL(15,2) DEFAULT NULL COMMENT 'Hạn mức phê duyệt (VND) - Dành cho CPD/GDK' AFTER `branch`";

if (mysqli_query($link, $sql)) {
    echo "<div class='success'>";
    echo "✅ <strong>SUCCESS!</strong><br>";
    echo "Column 'approval_limit' has been added to the users table.<br>";
    echo "Type: DECIMAL(15,2)<br>";
    echo "Default: NULL<br>";
    echo "Position: After 'branch' column";
    echo "</div>";

    // Verify the change
    echo "<h2>Step 3: Verifying changes...</h2>";
    $result = mysqli_query($link, "DESCRIBE users");
    $columns_after = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns_after[$row['Field']] = $row;
    }

    if (isset($columns_after['approval_limit'])) {
        echo "<div class='success'>";
        echo "✅ <strong>Verification passed!</strong><br>";
        echo "Column 'approval_limit' now exists in the users table.<br>";
        echo "<br><strong>Column details:</strong><br>";
        echo "Type: " . $columns_after['approval_limit']['Type'] . "<br>";
        echo "Null: " . $columns_after['approval_limit']['Null'] . "<br>";
        echo "Default: " . ($columns_after['approval_limit']['Default'] ?? 'NULL');
        echo "</div>";

        echo "<h2>✅ Migration Complete!</h2>";
        echo "<div class='success'>";
        echo "<strong>You can now:</strong><br>";
        echo "1. <a href='admin/debug_admin.php'>Run Admin Debug Again</a><br>";
        echo "2. <a href='admin/manage_users.php'>Go to Manage Users</a><br>";
        echo "3. <a href='admin/index.php'>Go to Admin Dashboard</a>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "❌ <strong>Verification failed!</strong><br>";
        echo "The column addition may not have worked correctly.";
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "❌ <strong>Migration FAILED!</strong><br>";
    echo "Error: " . htmlspecialchars(mysqli_error($link)) . "<br>";
    echo "SQL: " . htmlspecialchars($sql);
    echo "</div>";
}

echo "</body></html>";
?>
