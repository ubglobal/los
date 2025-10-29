<?php
// File: index.php (Workspace) - SECURE VERSION
require_once "config/session.php";
init_secure_session();
require_once "config/db.php";
require_once "includes/functions.php";

// Redirect if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check session timeout
check_session_timeout();

// If user is Admin, redirect to admin panel
if ($_SESSION['role'] === 'Admin') {
    header("location: admin/index.php");
    exit;
}

$user_id = $_SESSION['id'];
$my_tasks = get_applications_for_user($link, $user_id);

$pageTitle = "Hộp công việc";
include 'includes/header.php';
?>

<!-- Main Workspace -->
<main class="flex-1 workspace overflow-y-auto p-6">
    <div class="flex justify-between items-center border-b mb-6 pb-4">
        <h1 class="text-2xl font-bold text-gray-800">Hộp công việc của bạn</h1>
        <?php if ($_SESSION['role'] == 'CVQHKH'): ?>
            <a href="create_application.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md inline-flex items-center">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                Khởi tạo Hồ sơ
            </a>
        <?php endif; ?>
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
