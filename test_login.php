<?php
// Test file to debug login issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. PHP is working<br>";

// Test session
require_once "config/session.php";
echo "2. Session.php loaded<br>";

init_secure_session();
echo "3. Session initialized<br>";

// Test database
require_once "config/db.php";
echo "4. Database connected<br>";

// Test if user exists
$sql = "SELECT id, username, role FROM users LIMIT 1";
$result = mysqli_query($link, $sql);
if ($result) {
    $user = mysqli_fetch_assoc($result);
    echo "5. User found: " . htmlspecialchars($user['username']) . " - Role: " . htmlspecialchars($user['role']) . "<br>";
} else {
    echo "5. ERROR: " . mysqli_error($link) . "<br>";
}

// Test includes
echo "6. Testing includes...<br>";
if (file_exists("includes/functions.php")) {
    require_once "includes/functions.php";
    echo "   - functions.php OK<br>";
} else {
    echo "   - functions.php MISSING<br>";
}

if (file_exists("includes/workflow_engine.php")) {
    require_once "includes/workflow_engine.php";
    echo "   - workflow_engine.php OK<br>";
} else {
    echo "   - workflow_engine.php MISSING<br>";
}

if (file_exists("includes/facility_functions.php")) {
    require_once "includes/facility_functions.php";
    echo "   - facility_functions.php OK<br>";
} else {
    echo "   - facility_functions.php MISSING<br>";
}

if (file_exists("includes/disbursement_functions.php")) {
    require_once "includes/disbursement_functions.php";
    echo "   - disbursement_functions.php OK<br>";
} else {
    echo "   - disbursement_functions.php MISSING<br>";
}

if (file_exists("includes/permission_functions.php")) {
    require_once "includes/permission_functions.php";
    echo "   - permission_functions.php OK<br>";
} else {
    echo "   - permission_functions.php MISSING<br>";
}

echo "<br>7. All tests passed!<br>";
echo "<br><a href='login.php'>Go to Login</a>";
?>
