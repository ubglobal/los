<?php
// File: index.php (Dashboard) - v3.0 Enhanced
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

error_log("=== INDEX.PHP START ===");

require_once "config/session.php";
error_log("Session.php loaded");

init_secure_session();
error_log("Session initialized");

require_once "config/db.php";
error_log("db.php loaded");

require_once "includes/functions.php";
error_log("functions.php loaded");

// v3.0: New modules for dashboard
require_once "includes/workflow_engine.php";
error_log("workflow_engine.php loaded");

require_once "includes/facility_functions.php";
error_log("facility_functions.php loaded");

require_once "includes/disbursement_functions.php";
error_log("disbursement_functions.php loaded");

require_once "includes/permission_functions.php";
error_log("permission_functions.php loaded");

// Redirect if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    error_log("Not logged in, redirecting to login.php");
    header("location: login.php");
    exit;
}

error_log("User logged in: " . $_SESSION["username"] . ", Role: " . $_SESSION["role"]);

// Check session timeout
check_session_timeout();
error_log("Session timeout check passed");

// If user is Admin, redirect to admin panel
if ($_SESSION['role'] === 'Admin') {
    error_log("Admin user detected, redirecting to admin/index.php");
    header("location: admin/index.php");
    exit;
}

error_log("Non-admin user, continuing to dashboard");

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];
$user_branch = $_SESSION['branch'] ?? '';

// Get my tasks (existing)
$my_tasks = get_applications_for_user($link, $user_id);

// v3.0: Get statistics based on role
$stats = [];

// Common stats for all roles
$sql_total_apps = "SELECT COUNT(*) as total FROM credit_applications WHERE created_by_id = ?";
if ($stmt = mysqli_prepare($link, $sql_total_apps)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['my_applications'] = $row['total'];
    mysqli_stmt_close($stmt);
}

// Get SLA status counts for assigned applications
$sql_sla = "SELECT sla_status, COUNT(*) as count
            FROM credit_applications
            WHERE assigned_to_id = ? AND status = 'Đang xử lý'
            GROUP BY sla_status";
if ($stmt = mysqli_prepare($link, $sql_sla)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['sla_on_track'] = 0;
    $stats['sla_warning'] = 0;
    $stats['sla_overdue'] = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        switch ($row['sla_status']) {
            case 'On Track': $stats['sla_on_track'] = $row['count']; break;
            case 'Warning': $stats['sla_warning'] = $row['count']; break;
            case 'Overdue': $stats['sla_overdue'] = $row['count']; break;
        }
    }
    mysqli_stmt_close($stmt);
}

// Role-specific statistics
switch ($user_role) {
    case 'CVQHKH':
        // Draft applications
        $sql = "SELECT COUNT(*) as count FROM credit_applications WHERE created_by_id = ? AND status = 'Bản nháp'";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $stats['draft_apps'] = $row['count'];
        }

        // Returned applications (need info)
        $sql = "SELECT COUNT(*) as count FROM credit_applications WHERE created_by_id = ? AND status = 'Yêu cầu bổ sung'";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $stats['returned_apps'] = $row['count'];
        }

        // Approved applications awaiting disbursement
        $sql = "SELECT COUNT(*) as count FROM credit_applications
                WHERE created_by_id = ? AND status = 'Đã phê duyệt' AND legal_completed = 1";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $stats['ready_for_disbursement'] = $row['count'];
        }
        break;

    case 'CVTĐ':
        // Applications awaiting review
        $sql = "SELECT COUNT(*) as count FROM credit_applications WHERE assigned_to_id = ? AND stage = 'Thẩm định'";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $stats['awaiting_review'] = $row['count'];
        }
        break;

    case 'CPD':
    case 'GDK':
        // Applications awaiting approval
        $sql = "SELECT COUNT(*) as count FROM credit_applications
                WHERE assigned_to_id = ? AND (stage = 'Phê duyệt' OR stage = 'Phê duyệt cấp cao')";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $stats['awaiting_approval'] = $row['count'];
        }

        // Pending exceptions
        $sql = "SELECT COUNT(*) as count FROM approval_conditions
                WHERE is_exception_requested = 1 AND is_exception_approved IS NULL";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $stats['pending_exceptions'] = $row['count'];
        }

        // Pending escalations (for GDK)
        if ($user_role == 'GDK') {
            $sql = "SELECT COUNT(*) as count FROM escalations WHERE escalated_to_id = ? AND status = 'Pending'";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                $stats['pending_escalations'] = $row['count'];
            }
        }
        break;

    case 'Kiểm soát':
        // Disbursements awaiting conditions check
        $sql = "SELECT COUNT(*) as count FROM disbursements WHERE status = 'Awaiting Conditions Check'";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $stats['awaiting_check'] = $row['count'];
        }
        break;

    case 'Thủ quỹ':
        // Approved disbursements awaiting execution
        $sql = "SELECT COUNT(*) as count FROM disbursements WHERE status = 'Approved'";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $stats['awaiting_execution'] = $row['count'];
        }
        break;
}

// v3.0: Prepare data for charts
$chart_data = [];

// 1. Application Status Distribution (for status pie chart)
$sql_status = "SELECT status, COUNT(*) as count
               FROM credit_applications
               WHERE created_by_id = ? OR assigned_to_id = ?
               GROUP BY status";
if ($stmt = mysqli_prepare($link, $sql_status)) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $chart_data['status_labels'] = [];
    $chart_data['status_counts'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $chart_data['status_labels'][] = $row['status'];
        $chart_data['status_counts'][] = $row['count'];
    }
    mysqli_stmt_close($stmt);
}

// 2. SLA Compliance Data (for bar chart)
$chart_data['sla_labels'] = ['Đúng hạn', 'Cảnh báo', 'Quá hạn'];
$chart_data['sla_counts'] = [
    $stats['sla_on_track'] ?? 0,
    $stats['sla_warning'] ?? 0,
    $stats['sla_overdue'] ?? 0
];

// 3. Applications Timeline (last 7 days)
$sql_timeline = "SELECT DATE(created_at) as date, COUNT(*) as count
                 FROM credit_applications
                 WHERE (created_by_id = ? OR assigned_to_id = ?)
                   AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC";
if ($stmt = mysqli_prepare($link, $sql_timeline)) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $chart_data['timeline_labels'] = [];
    $chart_data['timeline_counts'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $chart_data['timeline_labels'][] = date('d/m', strtotime($row['date']));
        $chart_data['timeline_counts'][] = $row['count'];
    }
    mysqli_stmt_close($stmt);
}

// Fill in missing days for timeline (if needed)
if (count($chart_data['timeline_labels']) < 7) {
    $timeline_dates = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('d/m', strtotime("-$i days"));
        $timeline_dates[] = $date;
    }
    $chart_data['timeline_labels'] = $timeline_dates;

    // Fill counts with 0 for missing dates
    if (count($chart_data['timeline_counts']) < 7) {
        while (count($chart_data['timeline_counts']) < 7) {
            $chart_data['timeline_counts'][] = 0;
        }
    }
}

$pageTitle = "Dashboard";
include 'includes/header.php';
?>

<!-- Main Workspace -->
<main class="flex-1 workspace overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-sm text-gray-600 mt-1">Chào mừng, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo htmlspecialchars($user_role); ?>)</p>
        </div>
        <div class="flex space-x-3">
            <?php if ($user_role == 'CVQHKH'): ?>
                <a href="create_application.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    Khởi tạo Hồ sơ
                </a>
            <?php endif; ?>
            <a href="reports.php" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-md inline-flex items-center">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                Báo cáo
            </a>
        </div>
    </div>

    <!-- v3.0: Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Applications (Common for all) -->
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Hồ sơ của tôi</dt>
                        <dd class="text-3xl font-bold text-gray-900"><?php echo $stats['my_applications']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- SLA On Track -->
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Đúng hạn SLA</dt>
                        <dd class="text-3xl font-bold text-green-700"><?php echo $stats['sla_on_track']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- SLA Warning -->
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Cảnh báo SLA</dt>
                        <dd class="text-3xl font-bold text-yellow-700"><?php echo $stats['sla_warning']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- SLA Overdue -->
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Quá hạn SLA</dt>
                        <dd class="text-3xl font-bold text-red-700"><?php echo $stats['sla_overdue']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Role-Specific Cards -->
        <?php if ($user_role == 'CVQHKH'): ?>
            <!-- Draft Applications -->
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-gray-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Bản nháp</dt>
                            <dd class="text-3xl font-bold text-gray-700"><?php echo $stats['draft_apps']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Returned Applications -->
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-orange-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Cần bổ sung</dt>
                            <dd class="text-3xl font-bold text-orange-700"><?php echo $stats['returned_apps']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Ready for Disbursement -->
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Sẵn sàng giải ngân</dt>
                            <dd class="text-3xl font-bold text-purple-700"><?php echo $stats['ready_for_disbursement']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

        <?php elseif ($user_role == 'CVTĐ'): ?>
            <!-- Awaiting Review -->
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-indigo-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Chờ thẩm định</dt>
                            <dd class="text-3xl font-bold text-indigo-700"><?php echo $stats['awaiting_review']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

        <?php elseif ($user_role == 'CPD' || $user_role == 'GDK'): ?>
            <!-- Awaiting Approval -->
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-teal-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Chờ phê duyệt</dt>
                            <dd class="text-3xl font-bold text-teal-700"><?php echo $stats['awaiting_approval']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Pending Exceptions -->
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-pink-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Ngoại lệ chờ duyệt</dt>
                            <dd class="text-3xl font-bold text-pink-700"><?php echo $stats['pending_exceptions']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <?php if ($user_role == 'GDK' && isset($stats['pending_escalations'])): ?>
            <!-- Pending Escalations -->
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-rose-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Khiếu nại chờ xử lý</dt>
                            <dd class="text-3xl font-bold text-rose-700"><?php echo $stats['pending_escalations']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif ($user_role == 'Kiểm soát'): ?>
            <!-- Awaiting Conditions Check -->
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-cyan-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Chờ kiểm tra điều kiện</dt>
                            <dd class="text-3xl font-bold text-cyan-700"><?php echo $stats['awaiting_check']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

        <?php elseif ($user_role == 'Thủ quỹ'): ?>
            <!-- Awaiting Execution -->
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-emerald-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Chờ thực hiện giải ngân</dt>
                            <dd class="text-3xl font-bold text-emerald-700"><?php echo $stats['awaiting_execution']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- v3.0: Charts and Visualizations -->
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Biểu đồ phân tích</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Application Status Chart -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Phân bổ trạng thái hồ sơ</h3>
                <canvas id="statusChart"></canvas>
            </div>

            <!-- SLA Compliance Chart -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Tuân thủ SLA</h3>
                <canvas id="slaChart"></canvas>
            </div>

            <!-- Applications Timeline Chart -->
            <div class="bg-white p-6 rounded-lg shadow-md lg:col-span-2">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Xu hướng hồ sơ (7 ngày gần đây)</h3>
                <canvas id="timelineChart"></canvas>
            </div>
        </div>
    </div>

    <!-- My Tasks Section -->
    <div class="mb-4">
        <h2 class="text-xl font-bold text-gray-800">Công việc của tôi</h2>
        <p class="text-sm text-gray-600">Danh sách hồ sơ được giao hoặc tạo bởi bạn</p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-3 font-semibold text-gray-600">Mã hồ sơ</th>
                        <th class="p-3 font-semibold text-gray-600">Tên khách hàng</th>
                        <th class="p-3 font-semibold text-gray-600">Sản phẩm</th>
                        <th class="p-3 font-semibold text-gray-600 text-right">Số tiền (VND)</th>
                        <th class="p-3 font-semibold text-gray-600">Trạng thái/Giai đoạn</th>
                        <th class="p-3 font-semibold text-gray-600">Ngày cập nhật</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($my_tasks)): ?>
                        <tr>
                            <td colspan="6" class="text-center p-8 border-t">
                                <p class="text-gray-500">Không có công việc nào trong hộp thư của bạn.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($my_tasks as $task): ?>
                        <tr class="hover:bg-gray-50 border-t cursor-pointer" onclick="window.location='application_detail.php?id=<?php echo (int)$task['id']; ?>';">
                            <td class="p-3 font-mono text-blue-600"><?php echo htmlspecialchars($task['hstd_code']); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($task['customer_name']); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($task['product_name']); ?></td>
                            <td class="p-3 text-right"><?php echo number_format($task['amount'], 0, ',', '.'); ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                <?php
                                    switch ($task['stage']) {
                                        case 'Yêu cầu bổ sung': echo 'bg-red-100 text-red-800'; break;
                                        case 'Chờ phê duyệt': case 'Chờ phê duyệt cấp cao': echo 'bg-blue-100 text-blue-800'; break;
                                        default: echo 'bg-yellow-100 text-yellow-800'; break;
                                    }
                                ?>">
                                    <?php echo htmlspecialchars($task['stage']); ?>
                                </span>
                            </td>
                            <td class="p-3 text-gray-500"><?php echo date("d/m/Y H:i", strtotime($task['updated_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Chart.js Configuration and Rendering

// 1. Application Status Distribution - Doughnut Chart
const statusCtx = document.getElementById('statusChart');
if (statusCtx) {
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chart_data['status_labels'] ?? []); ?>,
            datasets: [{
                label: 'Số hồ sơ',
                data: <?php echo json_encode($chart_data['status_counts'] ?? []); ?>,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',   // blue
                    'rgba(16, 185, 129, 0.8)',   // green
                    'rgba(245, 158, 11, 0.8)',   // amber
                    'rgba(239, 68, 68, 0.8)',    // red
                    'rgba(139, 92, 246, 0.8)',   // violet
                    'rgba(236, 72, 153, 0.8)',   // pink
                    'rgba(20, 184, 166, 0.8)',   // teal
                    'rgba(156, 163, 175, 0.8)'   // gray
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed + ' hồ sơ';
                            return label;
                        }
                    }
                }
            }
        }
    });
}

// 2. SLA Compliance - Bar Chart
const slaCtx = document.getElementById('slaChart');
if (slaCtx) {
    new Chart(slaCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_data['sla_labels'] ?? []); ?>,
            datasets: [{
                label: 'Số hồ sơ',
                data: <?php echo json_encode($chart_data['sla_counts'] ?? []); ?>,
                backgroundColor: [
                    'rgba(16, 185, 129, 0.8)',   // green - On Track
                    'rgba(245, 158, 11, 0.8)',   // amber - Warning
                    'rgba(239, 68, 68, 0.8)'     // red - Overdue
                ],
                borderColor: [
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' hồ sơ';
                        }
                    }
                }
            }
        }
    });
}

// 3. Applications Timeline - Line Chart
const timelineCtx = document.getElementById('timelineChart');
if (timelineCtx) {
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_data['timeline_labels'] ?? []); ?>,
            datasets: [{
                label: 'Hồ sơ mới',
                data: <?php echo json_encode($chart_data['timeline_counts'] ?? []); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' hồ sơ';
                        }
                    }
                }
            }
        }
    });
}
</script>
