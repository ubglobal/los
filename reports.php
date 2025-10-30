<?php
/**
 * File: reports.php - v3.0
 * Purpose: Reporting module with export to Excel
 * Author: Claude AI
 * Date: 2025-10-30
 */

require_once "config/session.php";
init_secure_session();
require_once "config/db.php";
require_once "config/csrf.php";
require_once "includes/functions.php";
require_once "includes/permission_functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

check_session_timeout();

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];

// Get filter parameters
$report_type = $_GET['type'] ?? 'applications';
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'all';
$export = $_GET['export'] ?? '';

// Handle export to CSV
if ($export == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=report_' . $report_type . '_' . date('Ymd') . '.csv');

    $output = fopen('php://output', 'w');

    switch($report_type) {
        case 'applications':
            // Headers
            fputcsv($output, ['Mã HS', 'Khách hàng', 'Sản phẩm', 'Số tiền', 'Trạng thái', 'Ngày tạo', 'SLA']);

            // Build query
            $sql = "SELECT ca.hstd_code, c.full_name, p.product_name, ca.amount, ca.status, ca.created_at, ca.sla_status
                    FROM credit_applications ca
                    JOIN customers c ON ca.customer_id = c.id
                    JOIN products p ON ca.product_id = p.id
                    WHERE DATE(ca.created_at) BETWEEN ? AND ?";

            if ($status_filter != 'all') {
                $sql .= " AND ca.status = '" . mysqli_real_escape_string($link, $status_filter) . "'";
            }

            $sql .= " ORDER BY ca.created_at DESC";

            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_assoc($result)) {
                    fputcsv($output, [
                        $row['hstd_code'],
                        $row['full_name'],
                        $row['product_name'],
                        $row['amount'],
                        $row['status'],
                        date('d/m/Y', strtotime($row['created_at'])),
                        $row['sla_status'] ?? 'N/A'
                    ]);
                }
            }
            break;

        case 'disbursements':
            fputcsv($output, ['Mã GN', 'Mã HS', 'Số tiền', 'Trạng thái', 'Ngày tạo', 'Ngày giải ngân']);

            $sql = "SELECT d.disbursement_code, ca.hstd_code, d.amount, d.status, d.created_date, d.disbursement_date
                    FROM disbursements d
                    JOIN credit_applications ca ON d.application_id = ca.id
                    WHERE DATE(d.created_date) BETWEEN ? AND ?";

            if ($status_filter != 'all') {
                $sql .= " AND d.status = '" . mysqli_real_escape_string($link, $status_filter) . "'";
            }

            $sql .= " ORDER BY d.created_date DESC";

            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_assoc($result)) {
                    fputcsv($output, [
                        $row['disbursement_code'],
                        $row['hstd_code'],
                        $row['amount'],
                        $row['status'],
                        date('d/m/Y', strtotime($row['created_date'])),
                        $row['disbursement_date'] ? date('d/m/Y', strtotime($row['disbursement_date'])) : 'N/A'
                    ]);
                }
            }
            break;

        case 'sla':
            fputcsv($output, ['Mã HS', 'Khách hàng', 'Trạng thái', 'SLA Status', 'Ngày tạo', 'Ngày cập nhật']);

            $sql = "SELECT ca.hstd_code, c.full_name, ca.status, ca.sla_status, ca.created_at, ca.updated_at
                    FROM credit_applications ca
                    JOIN customers c ON ca.customer_id = c.id
                    WHERE DATE(ca.created_at) BETWEEN ? AND ? AND ca.status = 'Đang xử lý'
                    ORDER BY FIELD(ca.sla_status, 'Overdue', 'Warning', 'On Track'), ca.updated_at DESC";

            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_assoc($result)) {
                    fputcsv($output, [
                        $row['hstd_code'],
                        $row['full_name'],
                        $row['status'],
                        $row['sla_status'] ?? 'N/A',
                        date('d/m/Y', strtotime($row['created_at'])),
                        date('d/m/Y H:i', strtotime($row['updated_at']))
                    ]);
                }
            }
            break;
    }

    fclose($output);
    exit;
}

// Fetch report data for display
$report_data = [];
$stats_summary = [];

switch($report_type) {
    case 'applications':
        // Summary stats
        $sql_stats = "SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Bản nháp' THEN 1 ELSE 0 END) as draft,
                        SUM(CASE WHEN status = 'Đang xử lý' THEN 1 ELSE 0 END) as processing,
                        SUM(CASE WHEN status = 'Đã phê duyệt' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'Đã từ chối' THEN 1 ELSE 0 END) as rejected,
                        SUM(amount) as total_amount
                      FROM credit_applications
                      WHERE DATE(created_at) BETWEEN ? AND ?";

        if ($stmt = mysqli_prepare($link, $sql_stats)) {
            mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $stats_summary = mysqli_fetch_assoc($result);
        }

        // Detail data
        $sql = "SELECT ca.*, c.full_name as customer_name, p.product_name
                FROM credit_applications ca
                JOIN customers c ON ca.customer_id = c.id
                JOIN products p ON ca.product_id = p.id
                WHERE DATE(ca.created_at) BETWEEN ? AND ?";

        if ($status_filter != 'all') {
            $sql .= " AND ca.status = '" . mysqli_real_escape_string($link, $status_filter) . "'";
        }

        $sql .= " ORDER BY ca.created_at DESC LIMIT 100";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $report_data[] = $row;
            }
        }
        break;

    case 'disbursements':
        // Summary stats
        $sql_stats = "SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'Completed' THEN amount ELSE 0 END) as total_disbursed
                      FROM disbursements
                      WHERE DATE(created_date) BETWEEN ? AND ?";

        if ($stmt = mysqli_prepare($link, $sql_stats)) {
            mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $stats_summary = mysqli_fetch_assoc($result);
        }

        // Detail data
        $sql = "SELECT d.*, ca.hstd_code
                FROM disbursements d
                JOIN credit_applications ca ON d.application_id = ca.id
                WHERE DATE(d.created_date) BETWEEN ? AND ?";

        if ($status_filter != 'all') {
            $sql .= " AND d.status = '" . mysqli_real_escape_string($link, $status_filter) . "'";
        }

        $sql .= " ORDER BY d.created_date DESC LIMIT 100";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $report_data[] = $row;
            }
        }
        break;

    case 'sla':
        // Summary stats
        $sql_stats = "SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN sla_status = 'On Track' THEN 1 ELSE 0 END) as on_track,
                        SUM(CASE WHEN sla_status = 'Warning' THEN 1 ELSE 0 END) as warning,
                        SUM(CASE WHEN sla_status = 'Overdue' THEN 1 ELSE 0 END) as overdue
                      FROM credit_applications
                      WHERE status = 'Đang xử lý'";

        $result = mysqli_query($link, $sql_stats);
        $stats_summary = mysqli_fetch_assoc($result);

        // Detail data
        $sql = "SELECT ca.*, c.full_name as customer_name
                FROM credit_applications ca
                JOIN customers c ON ca.customer_id = c.id
                WHERE ca.status = 'Đang xử lý'
                ORDER BY FIELD(ca.sla_status, 'Overdue', 'Warning', 'On Track'), ca.updated_at DESC
                LIMIT 100";

        $result = mysqli_query($link, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $report_data[] = $row;
        }
        break;
}

$pageTitle = "Báo cáo";
include 'includes/header.php';
?>

<main class="flex-1 workspace overflow-y-auto p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Báo cáo & Thống kê</h1>
        <p class="text-sm text-gray-600 mt-1">Xuất báo cáo và phân tích dữ liệu</p>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <form method="GET" action="reports.php" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Report Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Loại báo cáo</label>
                <select name="type" class="w-full border-gray-300 rounded-md">
                    <option value="applications" <?php echo $report_type == 'applications' ? 'selected' : ''; ?>>Hồ sơ tín dụng</option>
                    <option value="disbursements" <?php echo $report_type == 'disbursements' ? 'selected' : ''; ?>>Giải ngân</option>
                    <option value="sla" <?php echo $report_type == 'sla' ? 'selected' : ''; ?>>SLA Compliance</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="w-full border-gray-300 rounded-md">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
                <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="w-full border-gray-300 rounded-md">
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                <select name="status" class="w-full border-gray-300 rounded-md">
                    <option value="all">Tất cả</option>
                    <?php if ($report_type == 'applications'): ?>
                        <option value="Bản nháp" <?php echo $status_filter == 'Bản nháp' ? 'selected' : ''; ?>>Bản nháp</option>
                        <option value="Đang xử lý" <?php echo $status_filter == 'Đang xử lý' ? 'selected' : ''; ?>>Đang xử lý</option>
                        <option value="Đã phê duyệt" <?php echo $status_filter == 'Đã phê duyệt' ? 'selected' : ''; ?>>Đã phê duyệt</option>
                        <option value="Đã từ chối" <?php echo $status_filter == 'Đã từ chối' ? 'selected' : ''; ?>>Đã từ chối</option>
                    <?php else: ?>
                        <option value="Draft" <?php echo $status_filter == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Submit -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md">
                    Xem báo cáo
                </button>
                <a href="reports.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>"
                   class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-md inline-flex items-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Statistics -->
    <?php if (!empty($stats_summary)): ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <?php if ($report_type == 'applications'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-sm text-gray-600">Tổng số hồ sơ</div>
                <div class="text-3xl font-bold text-blue-600"><?php echo number_format($stats_summary['total']); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-sm text-gray-600">Đã phê duyệt</div>
                <div class="text-3xl font-bold text-green-600"><?php echo number_format($stats_summary['approved']); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-sm text-gray-600">Đã từ chối</div>
                <div class="text-3xl font-bold text-red-600"><?php echo number_format($stats_summary['rejected']); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-sm text-gray-600">Tổng số tiền</div>
                <div class="text-2xl font-bold text-purple-600"><?php echo number_format($stats_summary['total_amount'], 0, ',', '.'); ?> VND</div>
            </div>
        <?php elseif ($report_type == 'disbursements'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-sm text-gray-600">Tổng số giải ngân</div>
                <div class="text-3xl font-bold text-blue-600"><?php echo number_format($stats_summary['total']); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-sm text-gray-600">Đã hoàn thành</div>
                <div class="text-3xl font-bold text-green-600"><?php echo number_format($stats_summary['completed']); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md col-span-2">
                <div class="text-sm text-gray-600">Tổng đã giải ngân</div>
                <div class="text-2xl font-bold text-purple-600"><?php echo number_format($stats_summary['total_disbursed'], 0, ',', '.'); ?> VND</div>
            </div>
        <?php elseif ($report_type == 'sla'): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-sm text-gray-600">Tổng hồ sơ đang xử lý</div>
                <div class="text-3xl font-bold text-blue-600"><?php echo number_format($stats_summary['total']); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-sm text-gray-600">Đúng hạn</div>
                <div class="text-3xl font-bold text-green-600"><?php echo number_format($stats_summary['on_track']); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-sm text-gray-600">Cảnh báo</div>
                <div class="text-3xl font-bold text-yellow-600"><?php echo number_format($stats_summary['warning']); ?></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-sm text-gray-600">Quá hạn</div>
                <div class="text-3xl font-bold text-red-600"><?php echo number_format($stats_summary['overdue']); ?></div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Report Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <?php if ($report_type == 'applications'): ?>
                        <tr>
                            <th class="p-3 font-semibold text-gray-600">Mã HS</th>
                            <th class="p-3 font-semibold text-gray-600">Khách hàng</th>
                            <th class="p-3 font-semibold text-gray-600">Sản phẩm</th>
                            <th class="p-3 font-semibold text-gray-600 text-right">Số tiền</th>
                            <th class="p-3 font-semibold text-gray-600">Trạng thái</th>
                            <th class="p-3 font-semibold text-gray-600">SLA</th>
                            <th class="p-3 font-semibold text-gray-600">Ngày tạo</th>
                        </tr>
                    <?php elseif ($report_type == 'disbursements'): ?>
                        <tr>
                            <th class="p-3 font-semibold text-gray-600">Mã GN</th>
                            <th class="p-3 font-semibold text-gray-600">Mã HS</th>
                            <th class="p-3 font-semibold text-gray-600 text-right">Số tiền</th>
                            <th class="p-3 font-semibold text-gray-600">Trạng thái</th>
                            <th class="p-3 font-semibold text-gray-600">Ngày tạo</th>
                            <th class="p-3 font-semibold text-gray-600">Ngày giải ngân</th>
                        </tr>
                    <?php else: // SLA ?>
                        <tr>
                            <th class="p-3 font-semibold text-gray-600">Mã HS</th>
                            <th class="p-3 font-semibold text-gray-600">Khách hàng</th>
                            <th class="p-3 font-semibold text-gray-600">Trạng thái</th>
                            <th class="p-3 font-semibold text-gray-600">SLA</th>
                            <th class="p-3 font-semibold text-gray-600">Ngày tạo</th>
                            <th class="p-3 font-semibold text-gray-600">Cập nhật lần cuối</th>
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php if (empty($report_data)): ?>
                        <tr>
                            <td colspan="7" class="text-center p-8 border-t">
                                <p class="text-gray-500">Không có dữ liệu trong khoảng thời gian đã chọn.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($report_data as $row): ?>
                            <tr class="hover:bg-gray-50 border-t">
                                <?php if ($report_type == 'applications'): ?>
                                    <td class="p-3 font-mono text-blue-600">
                                        <a href="application_detail.php?id=<?php echo $row['id']; ?>" class="hover:underline">
                                            <?php echo htmlspecialchars($row['hstd_code']); ?>
                                        </a>
                                    </td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td class="p-3 text-right"><?php echo number_format($row['amount'], 0, ',', '.'); ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        <?php
                                            switch ($row['status']) {
                                                case 'Đã phê duyệt': echo 'bg-green-100 text-green-800'; break;
                                                case 'Đã từ chối': echo 'bg-red-100 text-red-800'; break;
                                                default: echo 'bg-yellow-100 text-yellow-800'; break;
                                            }
                                        ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <?php if ($row['sla_status']): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                            <?php
                                                switch ($row['sla_status']) {
                                                    case 'On Track': echo 'bg-green-100 text-green-800'; break;
                                                    case 'Warning': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'Overdue': echo 'bg-red-100 text-red-800'; break;
                                                }
                                            ?>">
                                                <?php echo $row['sla_status']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-gray-500"><?php echo date("d/m/Y", strtotime($row['created_at'])); ?></td>
                                <?php elseif ($report_type == 'disbursements'): ?>
                                    <td class="p-3 font-mono text-blue-600">
                                        <a href="disbursement_detail.php?id=<?php echo $row['id']; ?>" class="hover:underline">
                                            <?php echo htmlspecialchars($row['disbursement_code']); ?>
                                        </a>
                                    </td>
                                    <td class="p-3 font-mono"><?php echo htmlspecialchars($row['hstd_code']); ?></td>
                                    <td class="p-3 text-right"><?php echo number_format($row['amount'], 0, ',', '.'); ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        <?php
                                            switch ($row['status']) {
                                                case 'Completed': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'Approved': echo 'bg-green-100 text-green-800'; break;
                                                case 'Rejected': echo 'bg-red-100 text-red-800'; break;
                                                default: echo 'bg-yellow-100 text-yellow-800'; break;
                                            }
                                        ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-500"><?php echo date("d/m/Y", strtotime($row['created_date'])); ?></td>
                                    <td class="p-3 text-gray-500"><?php echo $row['disbursement_date'] ? date("d/m/Y", strtotime($row['disbursement_date'])) : 'N/A'; ?></td>
                                <?php else: // SLA ?>
                                    <td class="p-3 font-mono text-blue-600">
                                        <a href="application_detail.php?id=<?php echo $row['id']; ?>" class="hover:underline">
                                            <?php echo htmlspecialchars($row['hstd_code']); ?>
                                        </a>
                                    </td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['status']); ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        <?php
                                            switch ($row['sla_status']) {
                                                case 'On Track': echo 'bg-green-100 text-green-800'; break;
                                                case 'Warning': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'Overdue': echo 'bg-red-100 text-red-800'; break;
                                            }
                                        ?>">
                                            <?php echo $row['sla_status'] ?? 'N/A'; ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-500"><?php echo date("d/m/Y", strtotime($row['created_at'])); ?></td>
                                    <td class="p-3 text-gray-500"><?php echo date("d/m/Y H:i", strtotime($row['updated_at'])); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (count($report_data) >= 100): ?>
    <div class="mt-4 text-center text-sm text-gray-600">
        Hiển thị 100 bản ghi đầu tiên. Xuất ra Excel để xem toàn bộ dữ liệu.
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
