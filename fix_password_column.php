<?php
/**
 * Migration Script: Fix password column name
 *
 * This script renames the 'password' column to 'password_hash' in the users table
 * to match the column name used throughout the PHP codebase.
 *
 * Run this ONCE after updating to the latest code.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/db.php";

echo "<!DOCTYPE html><html><head><title>Fix Password Column</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px;}";
echo "</style></head><body>";

echo "<h1>🔧 Database Migration: Fix Password Column</h1>";

// Step 1: Check current column name
echo "<h2>Step 1: Checking current table structure...</h2>";

$result = mysqli_query($link, "DESCRIBE users");
$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
}

echo "<div class='info'>";
echo "<strong>Current columns in users table:</strong><br>";
echo implode(', ', $columns);
echo "</div>";

// Check which password column exists
$has_password = in_array('password', $columns);
$has_password_hash = in_array('password_hash', $columns);

echo "<h2>Step 2: Analyzing password column...</h2>";

if ($has_password_hash && !$has_password) {
    echo "<div class='success'>";
    echo "✅ <strong>Column 'password_hash' already exists!</strong><br>";
    echo "No migration needed. Your database is up to date.";
    echo "</div>";
    exit;
}

if (!$has_password && !$has_password_hash) {
    echo "<div class='error'>";
    echo "❌ <strong>ERROR: No password column found!</strong><br>";
    echo "Neither 'password' nor 'password_hash' column exists in the users table.<br>";
    echo "Please run a fresh installation.";
    echo "</div>";
    exit;
}

if ($has_password && $has_password_hash) {
    echo "<div class='error'>";
    echo "❌ <strong>ERROR: Both columns exist!</strong><br>";
    echo "Both 'password' and 'password_hash' columns exist. This is unexpected.<br>";
    echo "Please contact support.";
    echo "</div>";
    exit;
}

// If we reach here, we have 'password' but not 'password_hash'
echo "<div class='info'>";
echo "ℹ️ Column 'password' found. Need to rename to 'password_hash'.";
echo "</div>";

echo "<h2>Step 3: Renaming column...</h2>";

// Rename the column
$sql = "ALTER TABLE `users` CHANGE `password` `password_hash` VARCHAR(255) NOT NULL";

if (mysqli_query($link, $sql)) {
    echo "<div class='success'>";
    echo "✅ <strong>SUCCESS!</strong><br>";
    echo "Column 'password' has been renamed to 'password_hash'.<br>";
    echo "Your database is now compatible with the latest code.";
    echo "</div>";

    // Verify the change
    echo "<h2>Step 4: Verifying changes...</h2>";
    $result = mysqli_query($link, "DESCRIBE users");
    $columns_after = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns_after[] = $row['Field'];
    }

    if (in_array('password_hash', $columns_after) && !in_array('password', $columns_after)) {
        echo "<div class='success'>";
        echo "✅ <strong>Verification passed!</strong><br>";
        echo "Column 'password_hash' now exists and 'password' no longer exists.";
        echo "</div>";

        echo "<h2>✅ Migration Complete!</h2>";
        echo "<div class='success'>";
        echo "<strong>You can now:</strong><br>";
        echo "1. <a href='login.php'>Go to Login Page</a><br>";
        echo "2. <a href='test_includes.php'>Run Tests Again</a><br>";
        echo "3. <a href='login_simple.php'>Try Simple Login</a>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "❌ <strong>Verification failed!</strong><br>";
        echo "The column rename may not have worked correctly.";
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
