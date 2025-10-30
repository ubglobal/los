<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bug Fixes Migration - Phase 3.1</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-4 text-blue-600">🔧 Bug Fixes Migration - Phase 3.1</h1>
        <p class="mb-6 text-gray-600">This script will apply bug fixes from Phase 3.1 audit:</p>

        <div class="bg-blue-50 border border-blue-300 rounded p-4 mb-6">
            <h3 class="font-bold mb-2">📋 Fixes Included:</h3>
            <ul class="list-disc list-inside space-y-1 text-sm">
                <li><strong>BUG-006:</strong> Create application_code_sequence table for unique code generation</li>
                <li><strong>SECURITY-001:</strong> Verify .htaccess protection in uploads directory</li>
            </ul>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_migration'])) {
            require_once __DIR__ . '/config/db.php';

            echo '<div class="bg-blue-50 border border-blue-300 rounded p-4 mb-4">';
            echo '<h2 class="font-bold text-lg mb-2">🚀 Executing Migration...</h2>';

            $success = 0;
            $errors = 0;

            // 1. Create application_code_sequence table
            echo "<div class='mb-2'><strong>Creating application_code_sequence table...</strong> ";
            $seq_table_sql = "CREATE TABLE IF NOT EXISTS `application_code_sequence` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `year` int(11) NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_year` (`year`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
              COMMENT='Sequence generator for unique application codes'";

            if (mysqli_query($link, $seq_table_sql)) {
                echo "<span class='text-green-600'>✅ OK</span>";
                $success++;
            } else {
                echo "<span class='text-red-600'>❌ FAILED: " . mysqli_error($link) . "</span>";
                $errors++;
            }
            echo "</div>";

            // 2. Add unique constraint to hstd_code (if not exists)
            echo "<div class='mb-2'><strong>Adding unique constraint to hstd_code...</strong> ";

            // Check if constraint already exists
            $check_constraint = "SHOW INDEX FROM credit_applications WHERE Key_name = 'uk_hstd_code'";
            $result = mysqli_query($link, $check_constraint);

            if (mysqli_num_rows($result) > 0) {
                echo "<span class='text-yellow-600'>⚠️ Already exists</span>";
                $success++;
            } else {
                $add_constraint_sql = "ALTER TABLE credit_applications ADD UNIQUE KEY uk_hstd_code (hstd_code)";
                if (mysqli_query($link, $add_constraint_sql)) {
                    echo "<span class='text-green-600'>✅ OK</span>";
                    $success++;
                } else {
                    // Check if error is because of duplicate values
                    if (strpos(mysqli_error($link), 'Duplicate entry') !== false) {
                        echo "<span class='text-red-600'>❌ FAILED: Duplicate hstd_code values exist. Please fix duplicates first.</span>";
                    } else {
                        echo "<span class='text-red-600'>❌ FAILED: " . mysqli_error($link) . "</span>";
                    }
                    $errors++;
                }
            }
            echo "</div>";

            // 3. Verify .htaccess in uploads directory
            echo "<div class='mb-2'><strong>Verifying uploads/.htaccess...</strong> ";
            $htaccess_path = __DIR__ . '/uploads/.htaccess';
            if (file_exists($htaccess_path)) {
                $htaccess_content = file_get_contents($htaccess_path);
                if (strpos($htaccess_content, 'Deny from all') !== false) {
                    echo "<span class='text-green-600'>✅ Protected</span>";
                    $success++;
                } else {
                    echo "<span class='text-yellow-600'>⚠️ Not protected. Manual update needed.</span>";
                    echo "<div class='text-xs text-gray-600 mt-1'>The .htaccess file exists but doesn't deny all access. Please update it manually.</div>";
                    $success++;
                }
            } else {
                echo "<span class='text-yellow-600'>⚠️ File not found</span>";
                echo "<div class='text-xs text-gray-600 mt-1'>Please create uploads/.htaccess with 'Deny from all'</div>";
                $success++;
            }
            echo "</div>";

            echo '</div>';

            // Verification
            echo '<div class="bg-gray-50 border border-gray-300 rounded p-4 mb-4">';
            echo '<h2 class="font-bold text-lg mb-2">✅ Verification</h2>';

            // Check table exists
            $check_table = mysqli_query($link, "SHOW TABLES LIKE 'application_code_sequence'");
            if ($check_table && mysqli_num_rows($check_table) > 0) {
                $count_result = mysqli_query($link, "SELECT COUNT(*) as cnt FROM application_code_sequence");
                $count = mysqli_fetch_assoc($count_result)['cnt'] ?? 0;
                echo "<div class='text-green-600'>✅ application_code_sequence table exists (rows: $count)</div>";
            } else {
                echo "<div class='text-red-600'>❌ application_code_sequence table NOT FOUND</div>";
            }

            // Check unique constraint
            $check_uk = mysqli_query($link, "SHOW INDEX FROM credit_applications WHERE Key_name = 'uk_hstd_code'");
            if ($check_uk && mysqli_num_rows($check_uk) > 0) {
                echo "<div class='text-green-600'>✅ Unique constraint on hstd_code exists</div>";
            } else {
                echo "<div class='text-yellow-600'>⚠️ Unique constraint on hstd_code NOT found</div>";
            }

            // Check .htaccess
            if (file_exists($htaccess_path)) {
                echo "<div class='text-green-600'>✅ uploads/.htaccess file exists</div>";
            } else {
                echo "<div class='text-yellow-600'>⚠️ uploads/.htaccess file not found</div>";
            }

            echo '</div>';

            mysqli_close($link);

            if ($errors === 0) {
                echo '<div class="mt-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">';
                echo '<strong>🎉 Migration completed successfully!</strong><br>';
                echo "Fixed: Application code generation now uses sequence table (prevents collisions).<br>";
                echo "Security: Document downloads now require access control checks.";
                echo '</div>';

                echo '<div class="mt-4">';
                echo '<a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Go to Dashboard</a>';
                echo ' <button onclick="window.location.reload()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded ml-2">Run Again</button>';
                echo '</div>';
            } else {
                echo '<div class="mt-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">';
                echo '<strong>⚠️ Migration completed with some warnings.</strong><br>';
                echo "Please review the errors above and fix manually if needed.";
                echo '</div>';
            }
        } else {
            // Show migration form
            ?>
            <div class="bg-yellow-50 border border-yellow-300 rounded p-4 mb-6">
                <h3 class="font-bold mb-2">⚠️ What will this do?</h3>
                <ol class="list-decimal list-inside space-y-2 text-sm">
                    <li>Create <code class="bg-gray-200 px-1 rounded">application_code_sequence</code> table for unique application code generation</li>
                    <li>Add unique constraint to <code class="bg-gray-200 px-1 rounded">credit_applications.hstd_code</code> column (prevents duplicates at database level)</li>
                    <li>Verify <code class="bg-gray-200 px-1 rounded">uploads/.htaccess</code> file protects against direct file access</li>
                </ol>
            </div>

            <div class="bg-blue-50 border border-blue-300 rounded p-4 mb-6">
                <h3 class="font-bold mb-2">📝 Code Changes Already Applied:</h3>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    <li>✅ BUG-007: Fixed column name mismatches (type_name)</li>
                    <li>✅ BUG-008: Implemented add_collateral and add_repayment functions</li>
                    <li>✅ BUG-006: Updated create_application.php to use sequence table</li>
                    <li>✅ SECURITY-001: Created download_document.php with access control</li>
                    <li>✅ Updated application_detail.php to use secure downloads</li>
                </ul>
                <p class="mt-2 text-xs text-gray-600"><strong>Note:</strong> These code changes are already in your files. This migration only updates the database.</p>
            </div>

            <form method="POST" onsubmit="return confirm('Are you sure you want to execute the migration?');">
                <button type="submit" name="execute_migration" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg text-lg">
                    🚀 Execute Migration Now
                </button>
            </form>

            <div class="mt-6 text-sm text-gray-600">
                <p><strong>Note:</strong> This migration is safe to run multiple times. It uses <code>CREATE TABLE IF NOT EXISTS</code> and checks for existing constraints.</p>
                <p class="mt-2"><strong>Important:</strong> After migration, you can delete this file (migrate_bug_fixes.php) for security.</p>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>
