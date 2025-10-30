<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Database Migration - Phase 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-4 text-blue-600">🔧 Database Migration - Phase 2</h1>
        <p class="mb-6 text-gray-600">This script will add missing tables to fix BUG-002 (application_history, customer_credit_ratings, customer_related_parties, application_repayment_sources)</p>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_migration'])) {
            require_once __DIR__ . '/config/db.php';

            echo '<div class="bg-blue-50 border border-blue-300 rounded p-4 mb-4">';
            echo '<h2 class="font-bold text-lg mb-2">🚀 Executing Migration...</h2>';

            $migrations = [
                [
                    'name' => 'application_history',
                    'sql' => "CREATE TABLE IF NOT EXISTS `application_history` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `application_id` int(11) NOT NULL,
                        `user_id` int(11) NOT NULL,
                        `action` varchar(100) NOT NULL COMMENT 'Action type: Khởi tạo, Hoàn tất pháp lý, etc.',
                        `comment` text DEFAULT NULL COMMENT 'Detailed comment about the action',
                        `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
                        PRIMARY KEY (`id`),
                        KEY `idx_application_id` (`application_id`),
                        KEY `idx_user_id` (`user_id`),
                        KEY `idx_timestamp` (`timestamp`),
                        CONSTRAINT `fk_app_history_application` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_app_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                ],
                [
                    'name' => 'customer_credit_ratings',
                    'sql' => "CREATE TABLE IF NOT EXISTS `customer_credit_ratings` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `customer_id` int(11) NOT NULL,
                        `rating_date` date NOT NULL,
                        `credit_score` int(11) DEFAULT NULL,
                        `rating_grade` varchar(10) DEFAULT NULL,
                        `rating_agency` varchar(100) DEFAULT NULL,
                        `assessment_notes` text DEFAULT NULL,
                        `assessed_by_id` int(11) DEFAULT NULL,
                        `validity_period_months` int(11) DEFAULT 12,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        PRIMARY KEY (`id`),
                        KEY `idx_customer_rating` (`customer_id`,`rating_date`),
                        KEY `idx_rating_date` (`rating_date`),
                        CONSTRAINT `fk_credit_rating_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_credit_rating_assessor` FOREIGN KEY (`assessed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                ],
                [
                    'name' => 'customer_related_parties',
                    'sql' => "CREATE TABLE IF NOT EXISTS `customer_related_parties` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `customer_id` int(11) NOT NULL,
                        `related_customer_id` int(11) NOT NULL,
                        `relationship_type` varchar(50) NOT NULL,
                        `relationship_details` text DEFAULT NULL,
                        `ownership_percentage` decimal(5,2) DEFAULT NULL,
                        `effective_date` date DEFAULT NULL,
                        `end_date` date DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `uk_customer_relationship` (`customer_id`,`related_customer_id`,`relationship_type`),
                        KEY `idx_customer_id` (`customer_id`),
                        KEY `idx_related_customer_id` (`related_customer_id`),
                        KEY `idx_relationship_type` (`relationship_type`),
                        CONSTRAINT `fk_related_party_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_related_party_related` FOREIGN KEY (`related_customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                ],
                [
                    'name' => 'application_repayment_sources',
                    'sql' => "CREATE TABLE IF NOT EXISTS `application_repayment_sources` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `application_id` int(11) NOT NULL,
                        `source_type` varchar(50) NOT NULL,
                        `source_description` text NOT NULL,
                        `estimated_monthly_amount` decimal(15,2) DEFAULT NULL,
                        `percentage_of_total` int(11) DEFAULT NULL,
                        `verification_status` varchar(50) DEFAULT 'Chưa xác minh',
                        `verified_by_id` int(11) DEFAULT NULL,
                        `verification_notes` text DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                        PRIMARY KEY (`id`),
                        KEY `idx_application_id` (`application_id`),
                        KEY `idx_source_type` (`source_type`),
                        CONSTRAINT `fk_repay_source_application` FOREIGN KEY (`application_id`) REFERENCES `credit_applications` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_repay_source_verifier` FOREIGN KEY (`verified_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                ]
            ];

            $success = 0;
            $errors = 0;

            foreach ($migrations as $migration) {
                echo "<div class='mb-2'>";
                echo "<strong>Creating table: {$migration['name']}</strong> ... ";

                if (mysqli_query($link, $migration['sql'])) {
                    echo "<span class='text-green-600'>✅ OK</span>";
                    $success++;
                } else {
                    echo "<span class='text-red-600'>❌ FAILED: " . mysqli_error($link) . "</span>";
                    $errors++;
                }
                echo "</div>";
            }

            echo '</div>';

            // Seed some initial data
            echo '<div class="bg-green-50 border border-green-300 rounded p-4 mb-4">';
            echo '<h2 class="font-bold text-lg mb-2">📊 Adding Initial Data...</h2>';

            // Add retroactive history for existing applications
            $history_sql = "INSERT IGNORE INTO application_history (application_id, user_id, action, comment)
                           SELECT id, created_by_id, 'Khởi tạo', 'Hồ sơ được tạo mới (dữ liệu demo - retroactive)'
                           FROM credit_applications
                           LIMIT 10";

            if (mysqli_query($link, $history_sql)) {
                $affected = mysqli_affected_rows($link);
                echo "<div>✅ Added $affected history records for existing applications</div>";
            } else {
                echo "<div>⚠️ Could not add history: " . mysqli_error($link) . "</div>";
            }

            echo '</div>';

            // Verification
            echo '<div class="bg-gray-50 border border-gray-300 rounded p-4">';
            echo '<h2 class="font-bold text-lg mb-2">✅ Verification</h2>';

            foreach ($migrations as $migration) {
                $table = $migration['name'];
                $check = mysqli_query($link, "SHOW TABLES LIKE '$table'");
                $count_result = mysqli_query($link, "SELECT COUNT(*) as cnt FROM $table");
                $count = mysqli_fetch_assoc($count_result)['cnt'] ?? 0;

                if ($check && mysqli_num_rows($check) > 0) {
                    echo "<div class='text-green-600'>✅ Table <strong>$table</strong> exists (rows: $count)</div>";
                } else {
                    echo "<div class='text-red-600'>❌ Table <strong>$table</strong> NOT FOUND</div>";
                }
            }

            echo '</div>';

            mysqli_close($link);

            if ($errors === 0) {
                echo '<div class="mt-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">';
                echo '<strong>🎉 Migration completed successfully!</strong><br>';
                echo "All $success tables created. You can now use the system without BUG-002 errors.";
                echo '</div>';

                echo '<div class="mt-4">';
                echo '<a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Go to Dashboard</a>';
                echo '</div>';
            }
        } else {
            // Show migration form
            ?>
            <div class="bg-yellow-50 border border-yellow-300 rounded p-4 mb-6">
                <h3 class="font-bold mb-2">⚠️ What will this do?</h3>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    <li>Create <code class="bg-gray-200 px-1 rounded">application_history</code> table - tracks all changes to applications</li>
                    <li>Create <code class="bg-gray-200 px-1 rounded">customer_credit_ratings</code> table - stores customer credit scores</li>
                    <li>Create <code class="bg-gray-200 px-1 rounded">customer_related_parties</code> table - tracks customer relationships</li>
                    <li>Create <code class="bg-gray-200 px-1 rounded">application_repayment_sources</code> table - tracks repayment sources</li>
                    <li>Add retroactive history records for existing applications</li>
                </ul>
            </div>

            <div class="bg-blue-50 border border-blue-300 rounded p-4 mb-6">
                <h3 class="font-bold mb-2">📋 Tables to be created:</h3>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>✅ application_history</div>
                    <div>✅ customer_credit_ratings</div>
                    <div>✅ customer_related_parties</div>
                    <div>✅ application_repayment_sources</div>
                </div>
            </div>

            <form method="POST" onsubmit="return confirm('Are you sure you want to execute the migration?');">
                <button type="submit" name="execute_migration" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg text-lg">
                    🚀 Execute Migration Now
                </button>
            </form>

            <div class="mt-6 text-sm text-gray-600">
                <p><strong>Note:</strong> This migration is safe to run multiple times. It uses <code>CREATE TABLE IF NOT EXISTS</code> so existing tables won't be affected.</p>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>
