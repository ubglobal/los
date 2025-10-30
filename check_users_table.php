<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/db.php";

echo "<!DOCTYPE html><html><head><title>Check Users Table</title></head><body>";
echo "<h1>Users Table Structure</h1>";

// Check table structure
$result = mysqli_query($link, "DESCRIBE users");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background:#004a99;color:white;'>";
echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
echo "</tr>";

$password_column = null;

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
    echo "</tr>";

    // Identify password column
    $field = strtolower($row['Field']);
    if (strpos($field, 'password') !== false || strpos($field, 'pass') !== false) {
        $password_column = $row['Field'];
    }
}

echo "</table>";

echo "<h2>Analysis:</h2>";
if ($password_column) {
    echo "<p style='color:green;font-size:18px;'><strong>✓ Password column found: <code style='background:#ffff00;padding:5px;'>" . htmlspecialchars($password_column) . "</code></strong></p>";
    echo "<p>The login code is looking for <code>password_hash</code> but the actual column is <code>" . htmlspecialchars($password_column) . "</code></p>";
} else {
    echo "<p style='color:red;'><strong>❌ No password column found!</strong></p>";
}

echo "<h2>Sample User Data (first user):</h2>";
$result = mysqli_query($link, "SELECT * FROM users LIMIT 1");
if ($row = mysqli_fetch_assoc($result)) {
    echo "<table border='1' cellpadding='10'>";
    foreach ($row as $key => $value) {
        echo "<tr>";
        echo "<th style='text-align:right;background:#f0f0f0;'>" . htmlspecialchars($key) . ":</th>";

        // Hide password value but show its format
        if (strpos(strtolower($key), 'password') !== false || strpos(strtolower($key), 'pass') !== false) {
            echo "<td><code>" . substr($value, 0, 7) . "..." . substr($value, -5) . "</code> (length: " . strlen($value) . ")</td>";
        } else {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>No users found in database!</p>";
}

echo "</body></html>";
?>
