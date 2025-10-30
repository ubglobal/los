<?php
/**
 * COMPREHENSIVE DATABASE MIGRATION SCRIPT
 *
 * Fixes all database schema issues to match current code:
 * 1. users table: Adds approval_limit column
 * 2. customers table: Adds dob, company_tax_code, company_representative, renames phone to phone_number
 * 3. products table: Simplifies structure to match manage_products.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/db.php";

echo "<!DOCTYPE html><html><head><title>Comprehensive Database Migration</title>";
echo "<style>
body{font-family:Arial;max-width:1000px;margin:30px auto;padding:20px;background:#f5f5f5;}
h1{color:#004a99;}
h2{color:#333;border-bottom:2px solid #004a99;padding-bottom:10px;margin-top:30px;}
.success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:12px;margin:10px 0;border-radius:5px;}
.error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:12px;margin:10px 0;border-radius:5px;}
.info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460;padding:12px;margin:10px 0;border-radius:5px;}
.warning{background:#fff3cd;border:1px solid #ffeaa7;color:#856404;padding:12px;margin:10px 0;border-radius:5px;}
table{width:100%;border-collapse:collapse;margin:10px 0;background:white;}
th,td{border:1px solid #ddd;padding:10px;text-align:left;}
th{background:#004a99;color:white;}
.step{background:white;padding:15px;margin:15px 0;border-radius:5px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
</style></head><body>";

echo "<h1>🔧 Comprehensive Database Migration</h1>";
echo "<p>This script will synchronize your database with the current code.</p>";

$all_migrations = [];
$total_success = 0;
$total_failed = 0;

// ============================================================================
// TABLE 1: USERS
// ============================================================================
echo "<div class='step'>";
echo "<h2>Table 1: users</h2>";

$result = mysqli_query($link, "DESCRIBE users");
$users_columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users_columns[] = $row['Field'];
}

echo "<div class='info'>Current columns: " . implode(', ', $users_columns) . "</div>";

if (!in_array('approval_limit', $users_columns)) {
    echo "<p>⚠️ Missing: approval_limit</p>";
    $sql = "ALTER TABLE `users` ADD COLUMN `approval_limit` DECIMAL(15,2) DEFAULT NULL COMMENT 'Hạn mức phê duyệt (VND) - Dành cho CPD/GDK' AFTER `branch`";

    if (mysqli_query($link, $sql)) {
        echo "<div class='success'>✅ Added approval_limit column</div>";
        $total_success++;
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars(mysqli_error($link)) . "</div>";
        $total_failed++;
    }
} else {
    echo "<div class='success'>✅ approval_limit already exists</div>";
}
echo "</div>";

// ============================================================================
// TABLE 2: CUSTOMERS
// ============================================================================
echo "<div class='step'>";
echo "<h2>Table 2: customers</h2>";

$result = mysqli_query($link, "DESCRIBE customers");
$customers_columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $customers_columns[] = $row['Field'];
}

echo "<div class='info'>Current columns: " . implode(', ', $customers_columns) . "</div>";

// Migration 1: Rename phone to phone_number
if (in_array('phone', $customers_columns) && !in_array('phone_number', $customers_columns)) {
    echo "<p>⚠️ Need to rename: phone → phone_number</p>";
    $sql = "ALTER TABLE `customers` CHANGE `phone` `phone_number` VARCHAR(20) DEFAULT NULL";

    if (mysqli_query($link, $sql)) {
        echo "<div class='success'>✅ Renamed phone to phone_number</div>";
        $total_success++;
        $customers_columns = array_diff($customers_columns, ['phone']);
        $customers_columns[] = 'phone_number';
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars(mysqli_error($link)) . "</div>";
        $total_failed++;
    }
} elseif (in_array('phone_number', $customers_columns)) {
    echo "<div class='success'>✅ phone_number already correct</div>";
}

// Migration 2: Add dob
if (!in_array('dob', $customers_columns)) {
    echo "<p>⚠️ Missing: dob (date of birth)</p>";
    $sql = "ALTER TABLE `customers` ADD COLUMN `dob` DATE DEFAULT NULL COMMENT 'Date of birth for individuals' AFTER `id_number`";

    if (mysqli_query($link, $sql)) {
        echo "<div class='success'>✅ Added dob column</div>";
        $total_success++;
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars(mysqli_error($link)) . "</div>";
        $total_failed++;
    }
} else {
    echo "<div class='success'>✅ dob already exists</div>";
}

// Migration 3: Add company_tax_code
if (!in_array('company_tax_code', $customers_columns)) {
    echo "<p>⚠️ Missing: company_tax_code</p>";
    $sql = "ALTER TABLE `customers` ADD COLUMN `company_tax_code` VARCHAR(50) DEFAULT NULL COMMENT 'Tax code for companies' AFTER `dob`";

    if (mysqli_query($link, $sql)) {
        echo "<div class='success'>✅ Added company_tax_code column</div>";
        $total_success++;
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars(mysqli_error($link)) . "</div>";
        $total_failed++;
    }
} else {
    echo "<div class='success'>✅ company_tax_code already exists</div>";
}

// Migration 4: Add company_representative
if (!in_array('company_representative', $customers_columns)) {
    echo "<p>⚠️ Missing: company_representative</p>";
    $sql = "ALTER TABLE `customers` ADD COLUMN `company_representative` VARCHAR(100) DEFAULT NULL COMMENT 'Representative for companies' AFTER `company_tax_code`";

    if (mysqli_query($link, $sql)) {
        echo "<div class='success'>✅ Added company_representative column</div>";
        $total_success++;
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars(mysqli_error($link)) . "</div>";
        $total_failed++;
    }
} else {
    echo "<div class='success'>✅ company_representative already exists</div>";
}

// Migration 5: Update customer_type enum
$result = mysqli_query($link, "SHOW COLUMNS FROM customers LIKE 'customer_type'");
$type_row = mysqli_fetch_assoc($result);
if ($type_row && strpos($type_row['Type'], 'CÁ NHÂN') === false) {
    echo "<p>⚠️ Need to update customer_type enum</p>";
    $sql = "ALTER TABLE `customers` MODIFY COLUMN `customer_type` ENUM('CÁ NHÂN','DOANH NGHIỆP') NOT NULL DEFAULT 'CÁ NHÂN'";

    if (mysqli_query($link, $sql)) {
        echo "<div class='success'>✅ Updated customer_type enum</div>";
        $total_success++;
    } else {
        echo "<div class='error'>❌ Failed: " . htmlspecialchars(mysqli_error($link)) . "</div>";
        $total_failed++;
    }
} else {
    echo "<div class='success'>✅ customer_type enum already correct</div>";
}
echo "</div>";

// ============================================================================
// TABLE 3: PRODUCTS
// ============================================================================
echo "<div class='step'>";
echo "<h2>Table 3: products</h2>";

$result = mysqli_query($link, "DESCRIBE products");
$products_columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products_columns[] = $row['Field'];
}

echo "<div class='info'>Current columns: " . implode(', ', $products_columns) . "</div>";

$needs_restructure = false;

// Check if we need to restructure (has old columns)
if (in_array('product_code', $products_columns) || in_array('product_name', $products_columns)) {
    $needs_restructure = true;
    echo "<div class='warning'>";
    echo "⚠️ <strong>Products table needs restructuring</strong><br>";
    echo "Current structure has: product_code, product_name, product_type, interest_rate_min, etc.<br>";
    echo "Code expects: id, name, description, is_active<br>";
    echo "<strong>Action required:</strong> Will recreate table with correct structure.<br>";
    echo "⚠️ <strong>WARNING: This will delete all existing product data!</strong>";
    echo "</div>";

    echo "<form method='POST' style='margin:20px 0;'>";
    echo "<input type='hidden' name='confirm_products_restructure' value='1'>";
    echo "<button type='submit' style='background:#dc3545;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;font-size:16px;'>";
    echo "⚠️ CONFIRM: Recreate products table (will delete data)";
    echo "</button>";
    echo "</form>";

    if (isset($_POST['confirm_products_restructure'])) {
        echo "<p><strong>Recreating products table...</strong></p>";

        // Drop and recreate
        $sqls = [
            "DROP TABLE IF EXISTS `products`",
            "CREATE TABLE `products` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL,
              `description` text DEFAULT NULL,
              `is_active` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        foreach ($sqls as $sql) {
            if (mysqli_query($link, $sql)) {
                echo "<div class='success'>✅ Executed successfully</div>";
                $total_success++;
            } else {
                echo "<div class='error'>❌ Failed: " . htmlspecialchars(mysqli_error($link)) . "</div>";
                $total_failed++;
            }
        }
    }
} elseif (!in_array('name', $products_columns) || !in_array('description', $products_columns)) {
    // Missing columns but structure is close
    echo "<div class='warning'>⚠️ Products table missing some columns</div>";

    if (!in_array('name', $products_columns)) {
        echo "<p>Need to add 'name' column</p>";
        $sql = "ALTER TABLE `products` ADD COLUMN `name` VARCHAR(100) NOT NULL AFTER `id`";
        if (mysqli_query($link, $sql)) {
            echo "<div class='success'>✅ Added name column</div>";
            $total_success++;
        } else {
            echo "<div class='error'>❌ Failed: " . htmlspecialchars(mysqli_error($link)) . "</div>";
            $total_failed++;
        }
    }

    if (!in_array('description', $products_columns)) {
        echo "<p>Need to add 'description' column</p>";
        $sql = "ALTER TABLE `products` ADD COLUMN `description` TEXT DEFAULT NULL AFTER `name`";
        if (mysqli_query($link, $sql)) {
            echo "<div class='success'>✅ Added description column</div>";
            $total_success++;
        } else {
            echo "<div class='error'>❌ Failed: " . htmlspecialchars(mysqli_error($link)) . "</div>";
            $total_failed++;
        }
    }
} else {
    echo "<div class='success'>✅ Products table structure is correct</div>";
}
echo "</div>";

// ============================================================================
// SUMMARY
// ============================================================================
echo "<div class='step'>";
echo "<h2>📊 Migration Summary</h2>";

echo "<table>";
echo "<tr><th>Status</th><th>Count</th></tr>";
echo "<tr><td>✅ Successful migrations</td><td><strong>$total_success</strong></td></tr>";
echo "<tr><td>❌ Failed migrations</td><td><strong>$total_failed</strong></td></tr>";
echo "</table>";

if ($total_failed == 0 && !$needs_restructure) {
    echo "<div class='success'>";
    echo "<h3>✅ All Migrations Complete!</h3>";
    echo "<p>Your database is now synchronized with the code.</p>";
    echo "</div>";
} elseif ($needs_restructure && !isset($_POST['confirm_products_restructure'])) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Action Required</h3>";
    echo "<p>Please confirm the products table restructure above to complete migration.</p>";
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Some migrations incomplete</h3>";
    echo "<p>Please review errors above and contact support if needed.</p>";
    echo "</div>";
}

echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='audit_database.php' style='color:#004a99;font-weight:bold;'>Run Database Audit</a> - Verify all changes</li>";
echo "<li><a href='admin/manage_users.php' style='color:#004a99;'>Test: Manage Users</a></li>";
echo "<li><a href='admin/manage_customers.php' style='color:#004a99;'>Test: Manage Customers</a></li>";
echo "<li><a href='admin/manage_products.php' style='color:#004a99;'>Test: Manage Products</a></li>";
echo "<li><a href='admin/manage_collaterals.php' style='color:#004a99;'>Test: Manage Collaterals</a></li>";
echo "</ul>";

echo "</div>";

echo "</body></html>";
?>
