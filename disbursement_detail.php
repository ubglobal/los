<?php
/**
 * File: disbursement_detail.php - v3.0
 * Purpose: Display disbursement request details with workflow actions
 * Author: Claude AI
 * Date: 2025-10-30
 */

require_once "config/session.php";
init_secure_session();
require_once "config/db.php";
require_once "config/csrf.php";
require_once "includes/functions.php";

// v3.0: Business logic modules
require_once "includes/workflow_engine.php";
require_once "includes/facility_functions.php";
require_once "includes/disbursement_functions.php";
require_once "includes/permission_functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check session timeout
check_session_timeout();

$disbursement_id = (int)($_GET['id'] ?? 0);
if ($disbursement_id <= 0) {
    header("location: index.php");
    exit;
}

// Get disbursement details
$disbursement = get_disbursement_by_id($link, $disbursement_id);
if (!$disbursement) {
    http_response_code(404);
    die("Không tìm thấy yêu cầu giải ngân.");
}

// Get related data
$application = get_application_details($link, $disbursement['application_id']);
$customer = get_customer_by_id($link, $application['customer_id']);
$facility = get_facility_by_id($link, $disbursement['facility_id']);
$conditions = get_disbursement_conditions($link, $disbursement_id);
$history = get_disbursement_history($link, $disbursement_id);

// Access control
$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];

// Check if user can access this disbursement
$has_access = false;
if ($user_role == 'Admin') {
    $has_access = true;
} elseif ($disbursement['created_by_id'] == $user_id) {
    $has_access = true;
} elseif ($application['created_by_id'] == $user_id) {
    $has_access = true;
} elseif ($application['assigned_to_id'] == $user_id) {
    $has_access = true;
}

if (!$has_access) {
    http_response_code(403);
    die("Bạn không có quyền truy cập yêu cầu giải ngân này.");
}

// Determine available actions based on status and role
$can_update_conditions = ($disbursement['status'] == 'Draft' && $user_role == 'CVQHKH' && $disbursement['created_by_id'] == $user_id);
$can_submit = ($disbursement['status'] == 'Draft' && $user_role == 'CVQHKH' && $disbursement['created_by_id'] == $user_id);
$can_check = ($disbursement['status'] == 'Awaiting Conditions Check' && ($user_role == 'Kiểm soát' || $user_role == 'Admin'));
$can_approve = ($disbursement['status'] == 'Awaiting Approval' && ($user_role == 'CPD' || $user_role == 'GDK' || $user_role == 'Admin'));
$can_execute = ($disbursement['status'] == 'Approved' && ($user_role == 'Thủ quỹ' || $user_role == 'Admin'));
$can_cancel = (($disbursement['status'] == 'Draft' || $disbursement['status'] == 'Rejected') && ($disbursement['created_by_id'] == $user_id || $user_role == 'Admin'));

$pageTitle = "Chi tiết Giải ngân " . $disbursement['disbursement_code'];
include 'includes/header.php';
?>

<main class="flex-1 workspace overflow-y-auto p-6">
    <!-- Header -->
    <div class="bg-gray-50 p-3 border-b flex justify-between items-center sticky top-0 z-20">
        <div class="flex items-center space-x-4">
            <div>
                <span class="font-bold text-gray-700">Mã GN:</span> <span class="font-mono text-blue-600"><?php echo $disbursement['disbursement_code']; ?></span> |
                <span class="font-bold text-gray-700">Hồ sơ:</span> <a href="application_detail.php?id=<?php echo $application['id']; ?>" class="text-blue-600 hover:underline"><?php echo $application['hstd_code']; ?></a> |
                <span class="font-bold text-gray-700">Khách hàng:</span> <?php echo htmlspecialchars($customer['full_name']); ?>
            </div>
            <!-- Status Badge -->
            <div>
                <?php
                // FIX BUG-018: Use fixed Tailwind classes instead of dynamic ones
                $status_class = 'bg-gray-100 text-gray-800';
                $status_text = $disbursement['status'];
                switch($disbursement['status']) {
                    case 'Draft':
                        $status_class = 'bg-gray-100 text-gray-800';
                        $status_text = 'Bản nháp';
                        break;
                    case 'Awaiting Conditions Check':
                        $status_class = 'bg-yellow-100 text-yellow-800';
                        $status_text = 'Chờ kiểm tra';
                        break;
                    case 'Awaiting Approval':
                        $status_class = 'bg-yellow-100 text-yellow-800';
                        $status_text = 'Chờ phê duyệt';
                        break;
                    case 'Approved':
                        $status_class = 'bg-green-100 text-green-800';
                        $status_text = 'Đã phê duyệt';
                        break;
                    case 'Executed':
                    case 'Completed':
                        $status_class = 'bg-blue-100 text-blue-800';
                        $status_text = 'Hoàn thành';
                        break;
                    case 'Rejected':
                        $status_class = 'bg-red-100 text-red-800';
                        $status_text = 'Từ chối';
                        break;
                    case 'Cancelled':
                        $status_class = 'bg-gray-100 text-gray-800';
                        $status_text = 'Đã hủy';
                        break;
                }
                ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $status_class; ?>">
                    <?php echo $status_text; ?>
                </span>
            </div>
        </div>
        <div class="flex space-x-2">
            <a href="application_detail.php?id=<?php echo $application['id']; ?>" class="bg-white hover:bg-gray-100 text-gray-800 font-semibold py-2 px-4 border border-gray-300 rounded shadow-sm">Quay lại Hồ sơ</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white p-6 rounded-lg shadow-md mt-4">
        <!-- Disbursement Information -->
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Thông tin Yêu cầu Giải ngân</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Mã giải ngân</label>
                    <p class="mt-1 text-base font-mono font-semibold text-gray-900"><?php echo htmlspecialchars($disbursement['disbursement_code']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Hạn mức</label>
                    <p class="mt-1 text-base font-semibold text-gray-900"><?php echo htmlspecialchars($facility['facility_code']); ?> - <?php echo htmlspecialchars($facility['facility_type']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Số tiền giải ngân</label>
                    <p class="mt-1 text-2xl font-bold text-blue-600"><?php echo number_format($disbursement['amount'], 0, ',', '.'); ?> VND</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Loại giải ngân</label>
                    <p class="mt-1 text-base font-semibold text-gray-900"><?php echo htmlspecialchars($disbursement['disbursement_type']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Tài khoản thụ hưởng</label>
                    <p class="mt-1 text-base font-mono text-gray-900"><?php echo htmlspecialchars($disbursement['beneficiary_account']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Ngày tạo</label>
                    <p class="mt-1 text-base text-gray-900"><?php echo date("d/m/Y H:i", strtotime($disbursement['created_date'])); ?></p>
                </div>
                <?php if ($disbursement['approved_by_id']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Người phê duyệt</label>
                    <p class="mt-1 text-base text-gray-900"><?php echo htmlspecialchars($disbursement['approver_name'] ?? 'N/A'); ?> (<?php echo date("d/m/Y", strtotime($disbursement['approved_date'])); ?>)</p>
                </div>
                <?php endif; ?>
                <?php if ($disbursement['executed_by_id']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Người thực hiện</label>
                    <p class="mt-1 text-base text-gray-900"><?php echo htmlspecialchars($disbursement['executor_name'] ?? 'N/A'); ?>
                    <?php if ($disbursement['disbursed_date']): ?>(<?php echo date("d/m/Y", strtotime($disbursement['disbursed_date'])); ?>)<?php endif; ?></p>
                </div>
                <?php endif; ?>
                <?php if ($disbursement['transaction_reference']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Mã giao dịch</label>
                    <p class="mt-1 text-base font-mono font-semibold text-green-600"><?php echo htmlspecialchars($disbursement['transaction_reference']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($disbursement['notes']): ?>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-500">Ghi chú</label>
                    <p class="mt-1 text-base text-gray-900"><?php echo nl2br(htmlspecialchars($disbursement['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Facility Balance Info -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <h3 class="font-semibold text-gray-800 mb-2">Thông tin Hạn mức</h3>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Tổng hạn mức:</span>
                    <p class="font-semibold text-lg"><?php echo number_format($facility['amount'], 0, ',', '.'); ?> VND</p>
                </div>
                <div>
                    <span class="text-gray-600">Đã giải ngân:</span>
                    <p class="font-semibold text-lg text-red-600"><?php echo number_format($facility['disbursed_amount'], 0, ',', '.'); ?> VND</p>
                </div>
                <div>
                    <span class="text-gray-600">Còn lại:</span>
                    <p class="font-semibold text-lg text-green-600"><?php echo number_format($facility['available_amount'], 0, ',', '.'); ?> VND</p>
                </div>
            </div>
        </div>

        <!-- Disbursement Conditions -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Điều kiện Giải ngân</h3>
            <form id="conditions-form" action="disbursement_action.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="disbursement_id" value="<?php echo $disbursement_id; ?>">

                <table class="min-w-full bg-white border">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4 border-b text-left">Loại</th>
                            <th class="py-2 px-4 border-b text-left">Điều kiện</th>
                            <th class="py-2 px-4 border-b text-center">Bắt buộc</th>
                            <th class="py-2 px-4 border-b text-center">Trạng thái</th>
                            <?php if ($can_update_conditions): ?>
                            <th class="py-2 px-4 border-b text-center">Thao tác</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($conditions as $cond): ?>
                        <tr>
                            <td class="py-2 px-4 border-b">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($cond['condition_type']); ?>
                                </span>
                            </td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($cond['condition_text']); ?></td>
                            <td class="py-2 px-4 border-b text-center">
                                <?php if ($cond['mandatory']): ?>
                                    <span class="text-red-600 font-bold">✓</span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border-b text-center">
                                <?php if ($cond['is_met']): ?>
                                    <span class="text-green-600 font-bold">✓ Đã đáp ứng</span>
                                    <?php if ($cond['met_date']): ?>
                                        <br><span class="text-xs text-gray-500"><?php echo date("d/m/Y", strtotime($cond['met_date'])); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">○ Chưa đáp ứng</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($can_update_conditions): ?>
                            <td class="py-2 px-4 border-b text-center">
                                <?php if (!$cond['is_met']): ?>
                                    <button type="button" onclick="markConditionMet(<?php echo $cond['id']; ?>)" class="text-green-600 hover:underline text-sm">
                                        Đánh dấu đã đáp ứng
                                    </button>
                                <?php else: ?>
                                    <button type="button" onclick="unmarkConditionMet(<?php echo $cond['id']; ?>)" class="text-gray-600 hover:underline text-sm">
                                        Bỏ đánh dấu
                                    </button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($conditions)): ?>
                        <tr><td colspan="<?php echo $can_update_conditions ? '5' : '4'; ?>" class="py-4 text-center text-gray-500">Chưa có điều kiện nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <!-- History -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Lịch sử Xử lý</h3>
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr class="border-b">
                        <th class="p-2 text-left">Ngày</th>
                        <th class="p-2 text-left">Người xử lý</th>
                        <th class="p-2 text-left">Hành động</th>
                        <th class="p-2 text-left">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($history as $h): ?>
                    <tr class="border-b">
                        <td class="p-2"><?php echo date("d/m/Y H:i", strtotime($h['created_at'])); ?></td>
                        <td class="p-2"><?php echo htmlspecialchars($h['user_name']); ?></td>
                        <td class="p-2 font-medium"><?php echo htmlspecialchars($h['action']); ?></td>
                        <td class="p-2 italic text-gray-600">"<?php echo htmlspecialchars($h['notes'] ?? ''); ?>"</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($history)): ?>
                    <tr><td colspan="4" class="p-3 text-center text-gray-500">Chưa có lịch sử.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="bg-white p-4 rounded-lg shadow-md mt-4">
        <form id="action-form" action="disbursement_action.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="disbursement_id" value="<?php echo $disbursement_id; ?>">

            <div class="flex justify-between items-center">
                <div>
                    <?php if ($disbursement['status'] == 'Rejected' && $disbursement['rejection_reason']): ?>
                        <div class="text-sm text-red-700">
                            <span class="font-bold">Lý do từ chối:</span> <?php echo htmlspecialchars($disbursement['rejection_reason']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex space-x-3">
                    <?php if ($can_cancel): ?>
                        <button type="button" onclick="cancelDisbursement()" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded">
                            Hủy yêu cầu
                        </button>
                    <?php endif; ?>

                    <?php if ($can_submit): ?>
                        <button type="button" onclick="submitDisbursement()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                            Trình giải ngân
                        </button>
                    <?php endif; ?>

                    <?php if ($can_check): ?>
                        <button type="button" onclick="showActionModal('check')" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded">
                            Xác nhận điều kiện
                        </button>
                        <button type="button" onclick="showActionModal('reject')" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                            Từ chối
                        </button>
                    <?php endif; ?>

                    <?php if ($can_approve): ?>
                        <button type="button" onclick="showActionModal('reject')" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                            Từ chối
                        </button>
                        <button type="button" onclick="showActionModal('approve')" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded">
                            Phê duyệt giải ngân
                        </button>
                    <?php endif; ?>

                    <?php if ($can_execute): ?>
                        <button type="button" onclick="showExecuteModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded">
                            Thực hiện giải ngân
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</main>

<!-- Action Modal (approve/reject/check) -->
<div id="action-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
    <div class="mt-3">
      <h3 id="modal-title" class="text-lg leading-6 font-medium text-gray-900 mb-4"></h3>
      <form id="modal-form" action="disbursement_action.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="disbursement_id" value="<?php echo $disbursement_id; ?>">
        <input type="hidden" id="modal-action" name="action" value="">

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Ý kiến <span id="comment-required" class="text-red-600 hidden">*</span></label>
            <textarea id="modal-comment" name="comment" rows="4" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nhập ý kiến của bạn..."></textarea>
          </div>
        </div>

        <div class="mt-5 flex justify-end space-x-3">
          <button type="button" onclick="closeActionModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Hủy</button>
          <button type="submit" id="modal-submit-btn" class="px-4 py-2 text-white rounded-md"></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Execute Disbursement Modal -->
<div id="execute-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
    <div class="mt-3">
      <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Thực hiện Giải ngân</h3>
      <form id="execute-form" action="disbursement_action.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="disbursement_id" value="<?php echo $disbursement_id; ?>">
        <input type="hidden" name="action" value="execute_disbursement">

        <div class="space-y-4">
          <div class="p-3 bg-purple-50 border-l-4 border-purple-400 text-sm">
            <p class="font-medium text-purple-800">Thông tin giải ngân:</p>
            <p class="text-purple-700">Số tiền: <strong><?php echo number_format($disbursement['amount'], 0, ',', '.'); ?> VND</strong></p>
            <p class="text-purple-700">Tài khoản: <strong><?php echo htmlspecialchars($disbursement['beneficiary_account']); ?></strong></p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Mã giao dịch <span class="text-red-600">*</span></label>
            <input type="text" name="transaction_ref" required class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nhập mã giao dịch từ hệ thống ngân hàng">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
            <textarea name="comment" rows="3" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nhập ghi chú (nếu có)..."></textarea>
          </div>
        </div>

        <div class="mt-5 flex justify-end space-x-3">
          <button type="button" onclick="closeExecuteModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Hủy</button>
          <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">Xác nhận thực hiện</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Update condition status
function markConditionMet(conditionId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'disbursement_action.php';

    const inputs = [
        {name: 'csrf_token', value: '<?php echo generate_csrf_token(); ?>'},
        {name: 'disbursement_id', value: '<?php echo $disbursement_id; ?>'},
        {name: 'action', value: 'update_condition'},
        {name: 'condition_id', value: conditionId},
        {name: 'is_met', value: '1'}
    ];

    inputs.forEach(input => {
        const field = document.createElement('input');
        field.type = 'hidden';
        field.name = input.name;
        field.value = input.value;
        form.appendChild(field);
    });

    document.body.appendChild(form);
    form.submit();
}

function unmarkConditionMet(conditionId) {
    if (confirm('Bạn có chắc chắn muốn bỏ đánh dấu điều kiện này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'disbursement_action.php';

        const inputs = [
            {name: 'csrf_token', value: '<?php echo generate_csrf_token(); ?>'},
            {name: 'disbursement_id', value: '<?php echo $disbursement_id; ?>'},
            {name: 'action', value: 'update_condition'},
            {name: 'condition_id', value: conditionId},
            {name: 'is_met', value: '0'}
        ];

        inputs.forEach(input => {
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = input.name;
            field.value = input.value;
            form.appendChild(field);
        });

        document.body.appendChild(form);
        form.submit();
    }
}

// Submit disbursement
function submitDisbursement() {
    if (confirm('Bạn có chắc chắn muốn trình giải ngân? Hãy đảm bảo tất cả điều kiện đã được đáp ứng.')) {
        const form = document.getElementById('action-form');
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'submit_disbursement';
        form.appendChild(actionInput);
        form.submit();
    }
}

// Cancel disbursement
function cancelDisbursement() {
    const reason = prompt('Nhập lý do hủy yêu cầu giải ngân:');
    if (reason && reason.trim() !== '') {
        const form = document.getElementById('action-form');
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'cancel_disbursement';
        form.appendChild(actionInput);

        const commentInput = document.createElement('input');
        commentInput.type = 'hidden';
        commentInput.name = 'comment';
        commentInput.value = reason;
        form.appendChild(commentInput);

        form.submit();
    }
}

// Show action modal
function showActionModal(action) {
    const modal = document.getElementById('action-modal');
    const title = document.getElementById('modal-title');
    const actionField = document.getElementById('modal-action');
    const submitBtn = document.getElementById('modal-submit-btn');
    const commentRequired = document.getElementById('comment-required');
    const commentField = document.getElementById('modal-comment');

    switch(action) {
        case 'approve':
            title.textContent = 'Phê duyệt Giải ngân';
            actionField.value = 'approve_disbursement';
            submitBtn.textContent = 'Phê duyệt';
            submitBtn.className = 'px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md';
            commentRequired.classList.add('hidden');
            commentField.required = false;
            break;
        case 'reject':
            title.textContent = 'Từ chối Giải ngân';
            actionField.value = 'reject_disbursement';
            submitBtn.textContent = 'Từ chối';
            submitBtn.className = 'px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md';
            commentRequired.classList.remove('hidden');
            commentField.required = true;
            break;
        case 'check':
            title.textContent = 'Xác nhận Điều kiện';
            actionField.value = 'check_conditions';
            submitBtn.textContent = 'Xác nhận';
            submitBtn.className = 'px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md';
            commentRequired.classList.add('hidden');
            commentField.required = false;
            break;
    }

    modal.classList.remove('hidden');
}

function closeActionModal() {
    document.getElementById('action-modal').classList.add('hidden');
    document.getElementById('modal-comment').value = '';
}

// Show execute modal
function showExecuteModal() {
    document.getElementById('execute-modal').classList.remove('hidden');
}

function closeExecuteModal() {
    document.getElementById('execute-modal').classList.add('hidden');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const actionModal = document.getElementById('action-modal');
    const executeModal = document.getElementById('execute-modal');

    if (event.target === actionModal) {
        closeActionModal();
    }
    if (event.target === executeModal) {
        closeExecuteModal();
    }
};
</script>

<?php include 'includes/footer.php'; ?>
