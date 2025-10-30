<?php
// Test login process step by step
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test Login Process</title></head><body>";
echo "<h1>Testing Login Process Step by Step</h1>";

try {
    echo "<h2>Step 1: Load required files</h2>";
    require_once "config/session.php";
    echo "<p>✓ session.php loaded</p>";

    init_secure_session();
    echo "<p>✓ Session initialized</p>";

    require_once "config/db.php";
    echo "<p>✓ db.php loaded</p>";

    require_once "config/csrf.php";
    echo "<p>✓ csrf.php loaded</p>";

    require_once "config/rate_limit.php";
    echo "<p>✓ rate_limit.php loaded</p>";

    echo "<h2>Step 2: Check if login_attempts table exists</h2>";
    $check_table = "SHOW TABLES LIKE 'login_attempts'";
    $result = mysqli_query($link, $check_table);
    if (mysqli_num_rows($result) > 0) {
        echo "<p>✓ login_attempts table exists</p>";

        // Check table structure
        $structure = mysqli_query($link, "DESCRIBE login_attempts");
        echo "<p>Table structure:</p><pre>";
        while ($row = mysqli_fetch_assoc($structure)) {
            echo htmlspecialchars(print_r($row, true));
        }
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>❌ login_attempts table does NOT exist</p>";
    }

    echo "<h2>Step 3: Test check_login_attempts function</h2>";
    $test_username = "testuser";
    $test_ip = $_SERVER['REMOTE_ADDR'];

    echo "<p>Testing with username: $test_username, IP: $test_ip</p>";

    try {
        $rate_check = check_login_attempts($link, $test_username, $test_ip);
        echo "<p>✓ check_login_attempts executed successfully</p>";
        echo "<pre>Result: " . htmlspecialchars(print_r($rate_check, true)) . "</pre>";
    } catch (Throwable $e) {
        echo "<p style='color:red;'>❌ ERROR in check_login_attempts:</p>";
        echo "<pre style='background:#ffeeee;padding:10px;'>";
        echo htmlspecialchars($e->getMessage()) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    }

    echo "<h2>Step 4: Test verify_csrf_token function</h2>";
    try {
        $token = generate_csrf_token();
        echo "<p>✓ CSRF token generated: " . htmlspecialchars(substr($token, 0, 20)) . "...</p>";

        verify_csrf_token($token);
        echo "<p>✓ CSRF token verified successfully</p>";
    } catch (Throwable $e) {
        echo "<p style='color:red;'>❌ ERROR in CSRF verification:</p>";
        echo "<pre style='background:#ffeeee;padding:10px;'>";
        echo htmlspecialchars($e->getMessage()) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    }

    echo "<h2>Step 5: Test user query</h2>";
    $sql = "SELECT id, username, password_hash, full_name, role, branch FROM users WHERE username = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $sql);
    if ($stmt) {
        echo "<p>✓ SQL statement prepared successfully</p>";
        mysqli_stmt_close($stmt);
    } else {
        echo "<p style='color:red;'>❌ Failed to prepare statement: " . htmlspecialchars(mysqli_error($link)) . "</p>";
    }

    echo "<h2>Step 6: List all users</h2>";
    $result = mysqli_query($link, "SELECT id, username, role, branch FROM users");
    if ($result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Branch</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
            echo "<td>" . htmlspecialchars($row['branch']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ Error querying users: " . htmlspecialchars(mysqli_error($link)) . "</p>";
    }

} catch (Throwable $e) {
    echo "<h2 style='color:red;'>FATAL ERROR</h2>";
    echo "<pre style='background:#ffeeee;padding:10px;border:1px solid red;'>";
    echo htmlspecialchars($e->getMessage()) . "\n\n";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}

echo "<hr>";
echo "<p><a href='login.php'>Back to Login</a></p>";
echo "</body></html>";
?>
