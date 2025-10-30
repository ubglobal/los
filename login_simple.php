<?php
// Simple login without CSRF and rate limiting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/session.php";
init_secure_session();
require_once "config/db.php";

$login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    echo "<h3>POST received</h3>";
    echo "<pre>POST data: " . htmlspecialchars(print_r($_POST, true)) . "</pre>";

    $username = trim($_POST["username"] ?? '');
    $password = trim($_POST["password"] ?? '');

    echo "<p>Username: " . htmlspecialchars($username) . "</p>";
    echo "<p>Password length: " . strlen($password) . "</p>";

    if(empty($username) || empty($password)){
        $login_err = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        echo "<p>Preparing SQL query...</p>";

        $sql = "SELECT id, username, password_hash, full_name, role, branch FROM users WHERE username = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            echo "<p>✓ Statement prepared</p>";

            mysqli_stmt_bind_param($stmt, "s", $username);

            echo "<p>Executing query...</p>";
            if(mysqli_stmt_execute($stmt)){
                echo "<p>✓ Query executed</p>";

                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    echo "<p>✓ User found</p>";

                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $full_name, $role, $branch);

                    if(mysqli_stmt_fetch($stmt)){
                        echo "<p>✓ Data fetched</p>";
                        echo "<p>User ID: $id, Name: $full_name, Role: $role, Branch: $branch</p>";

                        if(password_verify($password, $hashed_password)){
                            echo "<p style='color:green;'>✓ Password correct!</p>";

                            // Set session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["full_name"] = $full_name;
                            $_SESSION["role"] = $role;
                            $_SESSION["branch"] = $branch;

                            echo "<p>Session set. Redirecting...</p>";
                            echo "<p><a href='index_simple.php'>Click here if not redirected</a></p>";

                            // Uncomment to enable redirect
                            // header("location: index_simple.php");
                            // exit;
                        } else {
                            $login_err = "Mật khẩu không đúng.";
                        }
                    }
                } else {
                    echo "<p style='color:red;'>❌ User not found</p>";
                    $login_err = "Tên đăng nhập không tồn tại.";
                }
            } else {
                echo "<p style='color:red;'>❌ Execute failed: " . htmlspecialchars(mysqli_stmt_error($stmt)) . "</p>";
                $login_err = "Lỗi hệ thống.";
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "<p style='color:red;'>❌ Prepare failed: " . htmlspecialchars(mysqli_error($link)) . "</p>";
            $login_err = "Lỗi hệ thống.";
        }
    }

    if(!empty($login_err)){
        echo "<p style='color:red; font-weight:bold;'>Login Error: " . htmlspecialchars($login_err) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { width: 100%; padding: 10px; background: #0066cc; color: white; border: none; cursor: pointer; }
        .error { background: #ffeeee; border: 1px solid red; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Simple Login Test (No CSRF, No Rate Limit)</h1>

    <?php if(!empty($login_err)): ?>
        <div class="error"><?php echo $login_err; ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <hr>
    <p><a href="test_login_process.php">Test Login Process</a></p>
    <p><a href="login.php">Back to Normal Login</a></p>
</body>
</html>
