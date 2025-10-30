<?php
/**
 * File: disbursement_create.php - v3.0
 * Purpose: Create new disbursement request
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

$application_id = (int)($_GET['application_id'] ?? 0);
if ($application_id <= 0) {
    header("location: index.php");
    exit;
}

// Get application details
$app = get_application_details($link, $application_id);
if (!$app) {
    http_response_code(404);
    die("Không tìm thấy hồ sơ.");
}

// Access control - Only creator or Admin
$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];

if ($user_role != 'Admin' && $app['created_by_id'] != $user_id) {
    http_response_code(403);
    die("Bạn không có quyền tạo yêu cầu giải ngân cho hồ sơ này.");
}

// Permission check
if (!has_permission($link, $user_id, 'disbursement.input')) {
    http_response_code(403);
    die("Bạn không có quyền tạo yêu cầu giải ngân.");
}

// Check if application is approved
if ($app['status'] != 'Đã phê duyệt') {
    die("Chỉ có thể tạo yêu cầu giải ngân cho hồ sơ đã được phê duyệt.");
}

// Check if legal is completed
if (!$app['legal_completed']) {
    die("Vui lòng hoàn tất thủ tục pháp lý trước khi tạo yêu cầu giải ngân.");
}

// Get customer and facilities
$customer = get_customer_by_id($link, $app['customer_id']);
$facilities = get_facilities_by_application($link, $application_id);

// Filter only active facilities
$active_facilities = array_filter($facilities, function($f) {
    return $f['status'] == 'Active';
});

if (empty($active_facilities)) {
    die("Không có hạn mức nào đang hoạt động. Vui lòng kích hoạt hạn mức trước.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $facility_id = (int)($_POST['facility_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $disbursement_type = $_POST['disbursement_type'] ?? 'Full';
    $beneficiary_account = trim($_POST['beneficiary_account'] ?? '');
    $beneficiary_name = trim($_POST['beneficiary_name'] ?? '');
    $beneficiary_bank = trim($_POST['beneficiary_bank'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validation
    $errors = [];

    if ($facility_id <= 0) {
        $errors[] = "Vui lòng chọn hạn mức.";
    }

    if ($amount <= 0) {
        $errors[] = "Số tiền giải ngân phải lớn hơn 0.";
    }

    if (empty($beneficiary_account)) {
        $errors[] = "Vui lòng nhập số tài khoản thụ hưởng.";
    }

    if (empty($beneficiary_name)) {
        $errors[] = "Vui lòng nhập tên người thụ hưởng.";
    }

    if (empty($beneficiary_bank)) {
        $errors[] = "Vui lòng nhập tên ngân hàng.";
    }

    // Check facility availability
    if ($facility_id > 0 && $amount > 0) {
        $facility = get_facility_by_id($link, $facility_id);
        if ($facility) {
            $check = check_facility_availability($link, $facility_id, $amount);
            if (!$check['available']) {
                $errors[] = $check['message'];
            }
        } else {
            $errors[] = "Hạn mức không tồn tại.";
        }
    }

    // If no errors, create disbursement
    if (empty($errors)) {
        $disbursement_data = [
            'application_id' => $application_id,
            'facility_id' => $facility_id,
            'amount' => $amount,
            'disbursement_type' => $disbursement_type,
            'beneficiary_account' => $beneficiary_account . ' - ' . $beneficiary_name . ' - ' . $beneficiary_bank,
            'notes' => $notes
        ];

        $result = create_disbursement($link, $disbursement_data);

        if ($result['success']) {
            header("location: disbursement_detail.php?id=" . $result['disbursement_id'] . "&success=created");
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

$pageTitle = "Tạo Yêu cầu Giải ngân - " . $app['hstd_code'];
include 'includes/header.php';
?>

<main class="flex-1 workspace overflow-y-auto p-6">
    <!-- Header -->
    <div class="bg-gray-50 p-3 border-b flex justify-between items-center sticky top-0 z-20">
        <div>
            <span class="font-bold text-gray-700">Tạo Yêu cầu Giải ngân</span> |
            <span class="font-bold text-gray-700">Hồ sơ:</span> <a href="application_detail.php?id=<?php echo $app['id']; ?>" class="text-blue-600 hover:underline"><?php echo $app['hstd_code']; ?></a> |
            <span class="font-bold text-gray-700">Khách hàng:</span> <?php echo htmlspecialchars($customer['full_name']); ?>
        </div>
        <div>
            <a href="application_detail.php?id=<?php echo $app['id']; ?>" class="bg-white hover:bg-gray-100 text-gray-800 font-semibold py-2 px-4 border border-gray-300 rounded shadow-sm">Quay lại Hồ sơ</a>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mt-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Có lỗi xảy ra:</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    <?php foreach($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="bg-white p-6 rounded-lg shadow-md mt-4">
        <form action="disbursement_create.php?application_id=<?php echo $application_id; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <!-- Application Info Summary -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="font-semibold text-gray-800 mb-2">Thông tin Hồ sơ</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Mã hồ sơ:</span>
                        <p class="font-semibold font-mono"><?php echo $app['hstd_code']; ?></p>
                    </div>
                    <div>
                        <span class="text-gray-600">Sản phẩm:</span>
                        <p class="font-semibold"><?php echo htmlspecialchars($app['product_name']); ?></p>
                    </div>
                    <div>
                        <span class="text-gray-600">Số tiền phê duyệt:</span>
                        <p class="font-semibold text-green-600"><?php echo number_format($app['amount'], 0, ',', '.'); ?> VND</p>
                    </div>
                </div>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-4">Thông tin Giải ngân</h2>

            <!-- Facility Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Chọn Hạn mức <span class="text-red-600">*</span>
                </label>
                <select name="facility_id" id="facility-select" required class="w-full border-gray-300 rounded-md" onchange="updateFacilityInfo()">
                    <option value="">-- Chọn hạn mức --</option>
                    <?php foreach($active_facilities as $facility): ?>
                        <option value="<?php echo $facility['id']; ?>"
                                data-amount="<?php echo $facility['amount']; ?>"
                                data-disbursed="<?php echo $facility['disbursed_amount']; ?>"
                                data-available="<?php echo $facility['available_amount']; ?>"
                                <?php echo (isset($_POST['facility_id']) && $_POST['facility_id'] == $facility['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($facility['facility_code']); ?> - <?php echo htmlspecialchars($facility['facility_type']); ?>
                            (Khả dụng: <?php echo number_format($facility['available_amount'], 0, ',', '.'); ?> VND)
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Facility Info Display -->
                <div id="facility-info" class="mt-3 p-3 bg-gray-50 rounded hidden">
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Tổng hạn mức:</span>
                            <p id="facility-total" class="font-semibold"></p>
                        </div>
                        <div>
                            <span class="text-gray-600">Đã giải ngân:</span>
                            <p id="facility-disbursed" class="font-semibold text-red-600"></p>
                        </div>
                        <div>
                            <span class="text-gray-600">Còn lại:</span>
                            <p id="facility-available" class="font-semibold text-green-600"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Disbursement Amount and Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Số tiền giải ngân (VND) <span class="text-red-600">*</span>
                    </label>
                    <input type="number" name="amount" id="amount-input" required min="1" step="1"
                           value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : ''; ?>"
                           class="w-full border-gray-300 rounded-md"
                           placeholder="Nhập số tiền giải ngân"
                           oninput="checkAmount()">
                    <p id="amount-warning" class="mt-1 text-sm text-red-600 hidden"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Loại giải ngân <span class="text-red-600">*</span>
                    </label>
                    <select name="disbursement_type" required class="w-full border-gray-300 rounded-md">
                        <option value="Full" <?php echo (isset($_POST['disbursement_type']) && $_POST['disbursement_type'] == 'Full') ? 'selected' : ''; ?>>
                            Giải ngân toàn bộ
                        </option>
                        <option value="Partial" <?php echo (isset($_POST['disbursement_type']) && $_POST['disbursement_type'] == 'Partial') ? 'selected' : ''; ?>>
                            Giải ngân từng phần
                        </option>
                    </select>
                </div>
            </div>

            <!-- Beneficiary Information -->
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Thông tin Người thụ hưởng</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Số tài khoản <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="beneficiary_account" required
                           value="<?php echo isset($_POST['beneficiary_account']) ? htmlspecialchars($_POST['beneficiary_account']) : ''; ?>"
                           class="w-full border-gray-300 rounded-md"
                           placeholder="Nhập số tài khoản">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tên người thụ hưởng <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="beneficiary_name" required
                           value="<?php echo isset($_POST['beneficiary_name']) ? htmlspecialchars($_POST['beneficiary_name']) : htmlspecialchars($customer['full_name']); ?>"
                           class="w-full border-gray-300 rounded-md"
                           placeholder="Nhập tên người thụ hưởng">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ngân hàng <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="beneficiary_bank" required
                           value="<?php echo isset($_POST['beneficiary_bank']) ? htmlspecialchars($_POST['beneficiary_bank']) : ''; ?>"
                           class="w-full border-gray-300 rounded-md"
                           placeholder="Nhập tên ngân hàng (VD: VCB, ACB, Vietcombank)">
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ghi chú
                </label>
                <textarea name="notes" rows="4" class="w-full border-gray-300 rounded-md"
                          placeholder="Nhập ghi chú về yêu cầu giải ngân (nếu có)..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>

            <!-- Information Notice -->
            <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Lưu ý:</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Sau khi tạo yêu cầu, bạn cần cập nhật đầy đủ các điều kiện giải ngân</li>
                                <li>Chỉ khi tất cả điều kiện bắt buộc được đáp ứng mới có thể trình giải ngân</li>
                                <li>Yêu cầu sẽ được chuyển qua Kiểm soát → CPD/GDK → Thủ quỹ để thực hiện</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <a href="application_detail.php?id=<?php echo $application_id; ?>"
                   class="px-6 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 font-semibold">
                    Hủy
                </a>
                <button type="submit" id="submit-btn"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold">
                    Tạo Yêu cầu Giải ngân
                </button>
            </div>
        </form>
    </div>
</main>

<script>
// Update facility info display
function updateFacilityInfo() {
    const select = document.getElementById('facility-select');
    const selectedOption = select.options[select.selectedIndex];
    const facilityInfo = document.getElementById('facility-info');

    if (selectedOption.value) {
        const amount = parseFloat(selectedOption.dataset.amount);
        const disbursed = parseFloat(selectedOption.dataset.disbursed);
        const available = parseFloat(selectedOption.dataset.available);

        document.getElementById('facility-total').textContent = formatNumber(amount) + ' VND';
        document.getElementById('facility-disbursed').textContent = formatNumber(disbursed) + ' VND';
        document.getElementById('facility-available').textContent = formatNumber(available) + ' VND';

        facilityInfo.classList.remove('hidden');
    } else {
        facilityInfo.classList.add('hidden');
    }
}

// Check amount against available facility balance
function checkAmount() {
    const select = document.getElementById('facility-select');
    const selectedOption = select.options[select.selectedIndex];
    const amountInput = document.getElementById('amount-input');
    const warning = document.getElementById('amount-warning');
    const submitBtn = document.getElementById('submit-btn');

    if (selectedOption.value && amountInput.value) {
        const available = parseFloat(selectedOption.dataset.available);
        const requestedAmount = parseFloat(amountInput.value);

        if (requestedAmount > available) {
            warning.textContent = 'Số tiền vượt quá số dư khả dụng của hạn mức (' + formatNumber(available) + ' VND)';
            warning.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            warning.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

// Format number with thousand separators
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFacilityInfo();
});
</script>

<?php include 'includes/footer.php'; ?>
