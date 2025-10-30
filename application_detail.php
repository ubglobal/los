<?php
// File: application_detail.php - v3.0 Enhanced
require_once "config/session.php";
init_secure_session();
require_once "config/db.php";
require_once "config/csrf.php";
require_once "includes/functions.php";

// v3.0: New business logic modules
require_once "includes/workflow_engine.php";
require_once "includes/facility_functions.php";
require_once "includes/disbursement_functions.php";
require_once "includes/exception_escalation_functions.php";
require_once "includes/permission_functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check session timeout
check_session_timeout();

$application_id = (int)($_GET['id'] ?? 0);
if ($application_id <= 0) {
    header("location: index.php");
    exit;
}

$app = get_application_details($link, $application_id);
if (!$app) {
    http_response_code(404);
    die("Không tìm thấy hồ sơ.");
}

// ==== IDOR PROTECTION: CHECK ACCESS RIGHTS ====
$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];

// Admin can view all applications
if ($user_role !== 'Admin') {
    $has_access = false;

    // Check if user is currently assigned to this application
    if ($app['assigned_to_id'] == $user_id) {
        $has_access = true;
    }

    // Check if user created this application
    if ($app['created_by_id'] == $user_id) {
        $has_access = true;
    }

    // Check if user has ever worked on this application (in history)
    $sql_check_history = "SELECT COUNT(*) as cnt FROM application_history WHERE application_id = ? AND user_id = ?";
    if ($stmt_check = mysqli_prepare($link, $sql_check_history)) {
        mysqli_stmt_bind_param($stmt_check, "ii", $application_id, $user_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $row_check = mysqli_fetch_assoc($result_check);
        if ($row_check['cnt'] > 0) {
            $has_access = true;
        }
        mysqli_stmt_close($stmt_check);
    }

    // Optional: Allow users from same branch to view (uncomment if needed)
    /*
    $creator = get_user_by_id($link, $app['created_by_id']);
    if ($creator && $creator['branch'] == $_SESSION['branch']) {
        $has_access = true;
    }
    */

    if (!$has_access) {
        error_log("IDOR attempt: user_id={$user_id}, app_id={$application_id}");
        http_response_code(403);
        die("Bạn không có quyền truy cập hồ sơ này. (Error: 403 Forbidden)");
    }
}
// ==== END IDOR PROTECTION ====

// Fetch all related data
$customer = get_customer_by_id($link, $app['customer_id']);
$credit_ratings = get_credit_ratings_for_customer($link, $app['customer_id']);
$related_parties = get_related_parties_for_customer($link, $app['customer_id']);
$credit_history = get_credit_history_for_customer($link, $app['customer_id'], $app['id']);
$history = get_application_history($link, $application_id);
$collaterals = get_collaterals_for_app($link, $application_id);
$collateral_types = get_all_collateral_types($link);
$repayment_sources = get_repayment_sources_for_app($link, $application_id);
$documents = get_documents_for_app($link, $application_id);

// v3.0: Fetch new data
$facilities = get_facilities_by_application($link, $application_id);
$approval_conditions = []; // Get from approval_conditions table
$sql_conditions = "SELECT * FROM approval_conditions WHERE application_id = ? ORDER BY condition_type, id";
if ($stmt = mysqli_prepare($link, $sql_conditions)) {
    mysqli_stmt_bind_param($stmt, "i", $application_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $approval_conditions[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$disbursements = get_disbursements_by_application($link, $application_id);
$escalations = get_escalations_by_application($link, $application_id);

// Get current workflow step
$current_step = null;
if ($app['status'] != 'Đã phê duyệt' && $app['status'] != 'Đã từ chối') {
    $current_step = get_current_step($link, $application_id, 'Credit_Approval');
}

// Calculate SLA status color
$sla_color = 'gray';
$sla_text = 'N/A';
if (isset($app['sla_status'])) {
    switch ($app['sla_status']) {
        case 'On Track':
            $sla_color = 'green';
            $sla_text = 'Đúng hạn';
            break;
        case 'Warning':
            $sla_color = 'yellow';
            $sla_text = 'Cảnh báo';
            break;
        case 'Overdue':
            $sla_color = 'red';
            $sla_text = 'Quá hạn';
            break;
    }
}

// User can edit if they are CVQHKH and application is in editable stage AND they have access rights
$is_editable = ($user_role == 'CVQHKH'
    && ($app['stage'] == 'Khởi tạo hồ sơ tín dụng' || $app['stage'] == 'Yêu cầu bổ sung')
    && ($app['assigned_to_id'] == $user_id || $app['created_by_id'] == $user_id));

$pageTitle = "Chi tiết Hồ sơ " . $app['hstd_code'];
include 'includes/header.php';
?>

<main class="flex-1 workspace overflow-y-auto p-6">
    <div class="bg-gray-50 p-3 border-b flex justify-between items-center sticky top-0 z-20">
        <div class="flex items-center space-x-4">
            <div>
                <span class="font-bold text-gray-700">Mã HS:</span> <span class="font-mono text-blue-600"><?php echo $app['hstd_code']; ?></span> |
                <span class="font-bold text-gray-700">Khách hàng:</span> <?php echo htmlspecialchars($customer['full_name']); ?> |
                <span class="font-bold text-gray-700">Trạng thái:</span> <span class="font-semibold text-yellow-600"><?php echo htmlspecialchars($app['stage']); ?></span>
            </div>
            <!-- v3.0: SLA Status Badge -->
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?php echo $sla_color; ?>-100 text-<?php echo $sla_color; ?>-800">
                    SLA: <?php echo $sla_text; ?>
                </span>
            </div>
        </div>
        <div><a href="index.php" class="bg-white hover:bg-gray-100 text-gray-800 font-semibold py-2 px-4 border border-gray-300 rounded shadow-sm">Quay lại Hộp CV</a></div>
    </div>

    <form action="process_action.php" method="POST" enctype="multipart/form-data" id="main-form">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
        
        <div class="bg-white p-6 rounded-lg shadow-md mt-4">
            <!-- TABS -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-4 overflow-x-auto" aria-label="Tabs" id="main-tabs">
                    <a href="#khoanvay" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-blue-600 border-blue-500">Khoản vay</a>
                    <a href="#khachhang" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 border-transparent">Khách hàng</a>
                    <a href="#tsdb" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 border-transparent">Tài sản BĐ</a>
                    <a href="#nguontrano" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 border-transparent">Nguồn trả nợ</a>
                    <a href="#hosodinhkem" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 border-transparent">Hồ sơ đính kèm</a>
                    <!-- v3.0: New tabs -->
                    <a href="#hanmuc" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 border-transparent">Hạn mức <span class="ml-1 px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-800"><?php echo count($facilities); ?></span></a>
                    <a href="#dieukien" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 border-transparent">Điều kiện phê duyệt <span class="ml-1 px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-800"><?php echo count($approval_conditions); ?></span></a>
<a href="#giainganan" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 border-transparent">Giải ngân <span class="ml-1 px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-800"><?php echo count($disbursements); ?></span></a>
                    <a href="#lichsu" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 border-transparent">Lịch sử & Ý kiến</a>
                </nav>
            </div>
            
            <!-- TAB PANELS -->
            <div class="mt-6">
                <div id="panel-khoanvay" class="tab-panel space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="block text-sm font-medium text-gray-500">Sản phẩm tín dụng</label><p class="mt-1 text-base font-semibold text-gray-900"><?php echo htmlspecialchars($app['product_name']); ?></p></div>
                        <div><label class="block text-sm font-medium text-gray-500">Số tiền đề nghị vay (VND)</label><p class="mt-1 text-base font-semibold text-gray-900"><?php echo number_format($app['amount'], 0, ',', '.'); ?></p></div>
                        <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-500">Mục đích vay</label><p class="mt-1 text-base text-gray-900"><?php echo htmlspecialchars($app['purpose']); ?></p></div>
                    </div>
                </div>

                <div id="panel-khachhang" class="tab-panel hidden space-y-6">
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold mb-3 text-gray-700">Thông tin chung</h4>
                         <?php if ($customer['customer_type'] == 'CÁ NHÂN'): ?>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div><strong class="text-gray-500">Họ và tên:</strong><p><?php echo htmlspecialchars($customer['full_name']); ?></p></div>
                                <div><strong class="text-gray-500">Số CCCD/CMND:</strong><p><?php echo htmlspecialchars($customer['id_number']); ?></p></div>
                                <div><strong class="text-gray-500">Ngày sinh:</strong><p><?php echo $customer['dob'] ? date("d/m/Y", strtotime($customer['dob'])) : 'N/A'; ?></p></div>
                                <div class="col-span-3"><strong class="text-gray-500">Địa chỉ:</strong><p><?php echo htmlspecialchars($customer['address']); ?></p></div>
                            </div>
                        <?php else: ?>
                             <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div><strong class="text-gray-500">Tên Doanh nghiệp:</strong><p><?php echo htmlspecialchars($customer['full_name']); ?></p></div>
                                <div><strong class="text-gray-500">Mã số thuế:</strong><p><?php echo htmlspecialchars($customer['company_tax_code']); ?></p></div>
                                <div><strong class="text-gray-500">Người đại diện:</strong><p><?php echo htmlspecialchars($customer['company_representative']); ?></p></div>
                                <div class="col-span-3"><strong class="text-gray-500">Địa chỉ trụ sở:</strong><p><?php echo htmlspecialchars($customer['address']); ?></p></div>
                            </div>
                        <?php endif; ?>
                    </div>
                     <div class="border rounded-lg p-4">
                        <h4 class="font-semibold mb-3 text-gray-700">Xếp hạng tín dụng</h4>
                        <table class="w-full text-sm"><tbody>
                            <?php foreach($credit_ratings as $rating): ?>
                            <tr><td class="py-1 w-1/4"><strong><?php echo htmlspecialchars($rating['rating_type']); ?>:</strong></td><td class="py-1">Xếp hạng <strong class="text-blue-600"><?php echo htmlspecialchars($rating['rating']); ?></strong> bởi <?php echo htmlspecialchars($rating['organization']); ?> ngày <?php echo date("d/m/Y", strtotime($rating['rating_date'])); ?></td></tr>
                            <?php endforeach; if(empty($credit_ratings)) echo '<tr><td class="text-gray-500">Chưa có thông tin.</td></tr>'; ?>
                        </tbody></table>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold mb-3 text-gray-700">Người có liên quan</h4>
                         <table class="w-full text-sm"><tbody>
                            <?php foreach($related_parties as $party): ?>
                            <tr><td class="py-1 w-1/4"><strong><?php echo htmlspecialchars($party['relationship_type']); ?>:</strong></td><td class="py-1"><?php echo htmlspecialchars($party['related_customer_name']); ?> (Mã KH: <?php echo htmlspecialchars($party['related_customer_code']); ?>)</td></tr>
                            <?php endforeach; if(empty($related_parties)) echo '<tr><td class="text-gray-500">Chưa có thông tin.</td></tr>'; ?>
                        </tbody></table>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold mb-3 text-gray-700">Lịch sử Tín dụng tại U&Bank</h4>
                        <table class="w-full text-sm">
                            <thead class="font-medium text-gray-500"><tr><td class="py-1">Mã HS</td><td class="py-1">Sản phẩm</td><td class="py-1 text-right">Số tiền</td><td class="py-1">Trạng thái</td></tr></thead>
                            <tbody>
                            <?php foreach($credit_history as $hist_app): ?>
                            <tr><td class="py-1"><?php echo htmlspecialchars($hist_app['hstd_code']); ?></td><td class="py-1"><?php echo htmlspecialchars($hist_app['product_name']); ?></td><td class="py-1 text-right"><?php echo number_format($hist_app['amount'], 0, ',', '.'); ?></td><td class="py-1"><?php echo htmlspecialchars($hist_app['status']); ?></td></tr>
                             <?php endforeach; if(empty($credit_history)) echo '<tr><td colspan="4" class="text-gray-500 pt-2">Chưa có lịch sử.</td></tr>'; ?>
                        </tbody></table>
                    </div>
                </div>

                <div id="panel-tsdb" class="tab-panel hidden">
                    <h3 class="text-lg font-bold mb-4">Danh sách Tài sản Bảo đảm</h3>
                     <table class="min-w-full bg-white">
                        <thead class="bg-gray-50"><tr><th class="py-2 px-4 border-b">Loại TSBĐ</th><th class="py-2 px-4 border-b">Mô tả chi tiết</th><th class="py-2 px-4 border-b text-right">Giá trị (VND)</th><th class="py-2 px-4 border-b"></th></tr></thead>
                        <tbody id="collaterals-table-body">
                            <?php foreach($collaterals as $c): ?>
                            <tr><td class="py-2 px-4 border-b"><?php echo htmlspecialchars($c['type_name']); ?></td><td class="py-2 px-4 border-b"><?php echo htmlspecialchars($c['description']); ?></td><td class="py-2 px-4 border-b text-right"><?php echo number_format($c['estimated_value'], 0, ',', '.'); ?></td><td class="py-2 px-4 border-b text-right"><?php if($is_editable): ?><button type="submit" name="action" value="delete_collateral_<?php echo $c['id']; ?>" class="text-red-600 hover:underline text-xs" form="main-form">Xóa</button><?php endif; ?></td></tr>
                            <?php endforeach; if(empty($collaterals)) echo '<tr><td colspan="4" class="py-4 text-center text-gray-500">Chưa có TSBĐ.</td></tr>'; ?>
                        </tbody>
                    </table>
                     <?php if($is_editable): ?>
                    <div class="mt-4 p-4 border-t bg-gray-50 rounded-b-lg">
                        <h4 class="font-medium mb-2">Thêm TSBĐ mới</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                             <select name="collateral_type_id" class="border-gray-300 rounded-md">
                                 <?php foreach($collateral_types as $type): ?>
                                     <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                 <?php endforeach; ?>
                             </select>
                             <input type="text" name="collateral_description" placeholder="Mô tả chi tiết" class="border-gray-300 rounded-md">
                             <input type="number" name="collateral_value" placeholder="Giá trị (VND)" class="border-gray-300 rounded-md">
                        </div>
                         <div class="text-right mt-3">
                             <button type="submit" name="action" value="add_collateral" class="bg-gray-600 text-white px-4 py-2 rounded-md text-sm">Thêm TSBĐ</button>
                         </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="panel-nguontrano" class="tab-panel hidden">
                    <h3 class="text-lg font-bold mb-4">Danh sách Nguồn trả nợ</h3>
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50"><tr><th class="py-2 px-4 border-b">Loại nguồn thu</th><th class="py-2 px-4 border-b">Mô tả</th><th class="py-2 px-4 border-b text-right">Thu nhập tháng (VND)</th><th class="py-2 px-4 border-b"></th></tr></thead>
                        <tbody id="repayment-sources-table-body">
                             <?php foreach($repayment_sources as $s): ?>
                            <tr><td class="py-2 px-4 border-b"><?php echo htmlspecialchars($s['source_type']); ?></td><td class="py-2 px-4 border-b"><?php echo htmlspecialchars($s['source_description']); ?></td><td class="py-2 px-4 border-b text-right"><?php echo number_format($s['estimated_monthly_amount'], 0, ',', '.'); ?></td><td class="py-2 px-4 border-b text-right"><?php if($is_editable): ?><button type="submit" name="action" value="delete_repayment_<?php echo $s['id']; ?>" class="text-red-600 hover:underline text-xs">Xóa</button><?php endif; ?></td></tr>
                            <?php endforeach; if(empty($repayment_sources)) echo '<tr><td colspan="4" class="py-4 text-center text-gray-500">Chưa có nguồn trả nợ.</td></tr>'; ?>
                        </tbody>
                    </table>
                     <?php if($is_editable): ?>
                     <div class="mt-4 p-4 border-t bg-gray-50 rounded-b-lg">
                        <h4 class="font-medium mb-2">Thêm Nguồn trả nợ</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                             <input type="text" name="repayment_source_type" placeholder="Loại nguồn (Vd: Lương, Kinh doanh)" class="border-gray-300 rounded-md">
                             <input type="text" name="repayment_description" placeholder="Mô tả chi tiết" class="border-gray-300 rounded-md">
                             <input type="number" name="repayment_monthly_income" placeholder="Thu nhập/tháng (VND)" class="border-gray-300 rounded-md">
                        </div>
                         <div class="text-right mt-3">
                             <button type="submit" name="action" value="add_repayment" class="bg-gray-600 text-white px-4 py-2 rounded-md text-sm">Thêm Nguồn</button>
                         </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="panel-hosodinhkem" class="tab-panel hidden">
                     <h3 class="text-lg font-bold mb-4">Danh sách Hồ sơ đính kèm</h3>
                     <table class="min-w-full bg-white">
                        <thead class="bg-gray-50"><tr><th class="py-2 px-4 border-b">Tên tài liệu</th><th class="py-2 px-4 border-b">Người tải lên</th><th class="py-2 px-4 border-b">Ngày tải</th><th class="py-2 px-4 border-b"></th></tr></thead>
                        <tbody id="documents-table-body">
                             <?php foreach($documents as $doc): ?>
                            <tr>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($doc['uploader_name']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo date("d/m/Y H:i", strtotime($doc['uploaded_at'])); ?></td>
                                <td class="py-2 px-4 border-b text-right space-x-4">
                                    <a href="download_document.php?id=<?php echo $doc['id']; ?>" target="_blank" class="text-blue-600 hover:underline text-sm">Tải về</a>
                                    <?php if($is_editable): ?><button type="submit" name="action" value="delete_document_<?php echo $doc['id']; ?>" class="text-red-600 hover:underline text-sm">Xóa</button><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; if(empty($documents)) echo '<tr><td colspan="4" class="py-4 text-center text-gray-500">Chưa có tài liệu nào.</td></tr>'; ?>
                        </tbody>
                    </table>
                     <?php if($is_editable): ?>
                     <div class="mt-4 p-4 border-t bg-gray-50 rounded-b-lg">
                        <h4 class="font-medium mb-2">Tải lên tài liệu mới</h4>
                        <div class="flex items-center space-x-4">
                            <input type="text" name="document_name" placeholder="Tên tài liệu (vd: CCCD, HĐLĐ...)" class="flex-grow border-gray-300 rounded-md">
                            <input type="file" name="document_file" class="text-sm">
                            <button type="submit" name="action" value="upload_document" class="bg-gray-600 text-white px-4 py-2 rounded-md text-sm">Tải lên</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- v3.0: NEW TAB - Hạn mức -->
                <div id="panel-hanmuc" class="tab-panel hidden">
                    <h3 class="text-lg font-bold mb-4">Danh sách Hạn mức Tín dụng</h3>
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-4 border-b text-left">Mã hạn mức</th>
                                <th class="py-2 px-4 border-b text-left">Loại</th>
                                <th class="py-2 px-4 border-b text-right">Hạn mức</th>
                                <th class="py-2 px-4 border-b text-right">Đã giải ngân</th>
                                <th class="py-2 px-4 border-b text-right">Còn lại</th>
                                <th class="py-2 px-4 border-b text-center">Trạng thái</th>
                                <th class="py-2 px-4 border-b text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($facilities as $facility):
                                $utilization = get_facility_utilization($link, $facility['id']);
                            ?>
                            <tr>
                                <td class="py-2 px-4 border-b font-mono text-sm"><?php echo htmlspecialchars($facility['facility_code']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($facility['facility_type']); ?></td>
                                <td class="py-2 px-4 border-b text-right font-semibold"><?php echo number_format($facility['amount'], 0, ',', '.'); ?></td>
                                <td class="py-2 px-4 border-b text-right text-red-600"><?php echo number_format($facility['disbursed_amount'], 0, ',', '.'); ?></td>
                                <td class="py-2 px-4 border-b text-right text-green-600"><?php echo number_format($facility['available_amount'], 0, ',', '.'); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <?php
                                    $status_color = 'gray';
                                    if ($facility['status'] == 'Active') $status_color = 'green';
                                    elseif ($facility['status'] == 'Pending') $status_color = 'yellow';
                                    elseif ($facility['status'] == 'Closed') $status_color = 'red';
                                    ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                        <?php echo htmlspecialchars($facility['status']); ?>
                                    </span>
                                </td>
                                <td class="py-2 px-4 border-b text-center">
                                    <?php if ($facility['status'] == 'Pending' && ($user_role == 'CPD' || $user_role == 'GDK' || $user_role == 'Admin')): ?>
                                        <button type="button" onclick="activateFacility(<?php echo $facility['id']; ?>)" class="text-blue-600 hover:underline text-sm">
                                            Kích hoạt
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($facilities)): ?>
                            <tr><td colspan="7" class="py-4 text-center text-gray-500">Chưa có hạn mức nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Facility Utilization Chart (if facilities exist) -->
                    <?php if (!empty($facilities)): ?>
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold mb-3">Tỷ lệ sử dụng hạn mức</h4>
                        <?php foreach($facilities as $facility):
                            $utilization = get_facility_utilization($link, $facility['id']);
                            $util_percent = $utilization['utilization_percent'];
                            $bar_color = $util_percent < 50 ? 'green' : ($util_percent < 80 ? 'yellow' : 'red');
                        ?>
                        <div class="mb-3">
                            <div class="flex justify-between text-sm mb-1">
                                <span><?php echo htmlspecialchars($facility['facility_code']); ?></span>
                                <span class="font-semibold"><?php echo number_format($util_percent, 1); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-<?php echo $bar_color; ?>-500 h-2 rounded-full" style="width: <?php echo $util_percent; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- v3.0: NEW TAB - Điều kiện phê duyệt -->
                <div id="panel-dieukien" class="tab-panel hidden">
                    <h3 class="text-lg font-bold mb-4">Điều kiện Phê duyệt</h3>

                    <?php if ($app['status'] == 'Đã phê duyệt' || !empty($approval_conditions)): ?>
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-4 border-b text-left">Loại</th>
                                <th class="py-2 px-4 border-b text-left">Điều kiện</th>
                                <th class="py-2 px-4 border-b text-center">Bắt buộc</th>
                                <th class="py-2 px-4 border-b text-center">Trạng thái</th>
                                <th class="py-2 px-4 border-b text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($approval_conditions as $condition): ?>
                            <tr>
                                <td class="py-2 px-4 border-b">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($condition['condition_type']); ?>
                                    </span>
                                </td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($condition['condition_text']); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <?php if ($condition['mandatory']): ?>
                                        <span class="text-red-600 font-bold">✓</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border-b text-center">
                                    <?php if ($condition['is_exception_requested'] && !$condition['is_exception_approved']): ?>
                                        <span class="text-yellow-600 text-sm">⚠ Đang xin ngoại lệ</span>
                                    <?php elseif ($condition['is_exception_approved']): ?>
                                        <span class="text-green-600 text-sm">✓ Ngoại lệ được chấp thuận</span>
                                    <?php elseif ($condition['is_met']): ?>
                                        <span class="text-green-600 font-bold">✓ Đã đáp ứng</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">○ Chưa đáp ứng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border-b text-center text-sm">
                                    <?php if (!$condition['is_met'] && !$condition['is_exception_requested'] && $condition['allow_exception'] && $user_role == 'CVQHKH'): ?>
                                        <button type="button" onclick="requestException(<?php echo $condition['id']; ?>)" class="text-blue-600 hover:underline">
                                            Xin ngoại lệ
                                        </button>
                                    <?php elseif ($condition['is_exception_requested'] && !$condition['is_exception_approved'] && ($user_role == 'CPD' || $user_role == 'GDK')): ?>
                                        <button type="button" onclick="approveException(<?php echo $condition['id']; ?>)" class="text-green-600 hover:underline mr-2">
                                            Chấp thuận
                                        </button>
                                        <button type="button" onclick="rejectException(<?php echo $condition['id']; ?>)" class="text-red-600 hover:underline">
                                            Từ chối
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($approval_conditions)): ?>
                            <tr><td colspan="5" class="py-4 text-center text-gray-500">Chưa có điều kiện phê duyệt.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="text-center text-gray-500 py-8">
                        Điều kiện phê duyệt sẽ được tạo sau khi hồ sơ được phê duyệt.
                    </div>
                    <?php endif; ?>
                </div>

                <!-- v3.0: NEW TAB - Giải ngân -->
                <div id="panel-giainganan" class="tab-panel hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Danh sách Yêu cầu Giải ngân</h3>
                        <?php if ($app['status'] == 'Đã phê duyệt' && $app['legal_completed'] && $user_role == 'CVQHKH'): ?>
                            <a href="disbursement_create.php?application_id=<?php echo $app['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                                + Tạo yêu cầu giải ngân
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($disbursements)): ?>
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-4 border-b text-left">Mã GN</th>
                                <th class="py-2 px-4 border-b text-left">Hạn mức</th>
                                <th class="py-2 px-4 border-b text-right">Số tiền</th>
                                <th class="py-2 px-4 border-b text-left">Loại</th>
                                <th class="py-2 px-4 border-b text-center">Trạng thái</th>
                                <th class="py-2 px-4 border-b text-left">Ngày tạo</th>
                                <th class="py-2 px-4 border-b text-center">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($disbursements as $disb): ?>
                            <tr>
                                <td class="py-2 px-4 border-b font-mono text-sm"><?php echo htmlspecialchars($disb['disbursement_code']); ?></td>
                                <td class="py-2 px-4 border-b text-sm"><?php echo htmlspecialchars($disb['facility_code'] ?? 'N/A'); ?></td>
                                <td class="py-2 px-4 border-b text-right font-semibold"><?php echo number_format($disb['amount'], 0, ',', '.'); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($disb['disbursement_type']); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <?php
                                    $status_color = 'gray';
                                    $status_text = $disb['status'];
                                    switch($disb['status']) {
                                        case 'Draft': $status_color = 'gray'; break;
                                        case 'Awaiting Conditions Check': $status_color = 'yellow'; $status_text = 'Chờ kiểm tra'; break;
                                        case 'Awaiting Approval': $status_color = 'yellow'; $status_text = 'Chờ duyệt'; break;
                                        case 'Approved': $status_color = 'green'; $status_text = 'Đã duyệt'; break;
                                        case 'Completed': $status_color = 'blue'; $status_text = 'Hoàn thành'; break;
                                        case 'Rejected': $status_color = 'red'; $status_text = 'Từ chối'; break;
                                        case 'Cancelled': $status_color = 'gray'; $status_text = 'Đã hủy'; break;
                                    }
                                    ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="py-2 px-4 border-b text-sm"><?php echo date("d/m/Y", strtotime($disb['created_date'])); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <a href="disbursement_detail.php?id=<?php echo $disb['id']; ?>" class="text-blue-600 hover:underline text-sm">
                                        Xem chi tiết
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="text-center text-gray-500 py-8">
                        <?php if ($app['status'] != 'Đã phê duyệt'): ?>
                            Giải ngân chỉ khả dụng sau khi hồ sơ được phê duyệt.
                        <?php elseif (!$app['legal_completed']): ?>
                            Vui lòng hoàn tất thủ tục pháp lý trước khi tạo yêu cầu giải ngân.
                        <?php else: ?>
                            Chưa có yêu cầu giải ngân nào.
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="panel-lichsu" class="tab-panel hidden space-y-6">
                    <div>
                        <h3 class="text-lg font-bold mb-4">Ý kiến Thẩm định/Phê duyệt</h3>
                        <textarea id="comment-textarea" name="comment" class="w-full p-2 border rounded-md" rows="4" placeholder="Nhập ý kiến của bạn tại đây..."></textarea>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold mb-4">Lịch sử Phê duyệt</h3>
                        <table class="w-full text-sm">
                           <thead class="bg-gray-100"><tr class="border-b"><th class="p-2">Ngày xử lý</th><th class="p-2">Người xử lý</th><th class="p-2">Hành động</th><th class="p-2">Ghi chú</th></tr></thead>
                           <tbody>
                                <?php foreach($history as $h): ?>
                                <tr class="border-b"><td class="p-2"><?php echo date("d/m/Y H:i", strtotime($h['timestamp'])); ?></td><td class="p-2"><?php echo htmlspecialchars($h['full_name']); ?> (<?php echo htmlspecialchars($h['role']); ?>)</td><td class="p-2 font-medium"><?php echo htmlspecialchars($h['action']); ?></td><td class="p-2 italic text-gray-600">"<?php echo htmlspecialchars($h['comment']); ?>"</td></tr>
                                <?php endforeach; ?>
                                <?php if(empty($history)): ?>
                                <tr><td colspan="4" class="p-3 text-center text-gray-500">Chưa có lịch sử.</td></tr>
                                <?php endif; ?>
                           </tbody>
                         </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS AT BOTTOM (v3.0 Enhanced) -->
        <?php if ($app['assigned_to_id'] == $user_id && $app['status'] == 'Đang xử lý'): ?>
        <div class="mt-6 pt-4 border-t flex justify-end space-x-3 bg-gray-50 -mx-6 -mb-6 px-6 py-4 rounded-b-lg sticky bottom-0">
             <?php if ($user_role == 'CVQHKH'): ?>
                <button type="button" id="submit-for-review-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Gửi Thẩm định</button>
            <?php elseif ($user_role == 'CVTĐ'): ?>
                <button type="submit" name="action" value="return_for_info" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">Yêu cầu Bổ sung</button>
                <button type="submit" name="action" value="submit_for_approval" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Trình Phê duyệt</button>
            <?php elseif ($user_role == 'CPD' || $user_role == 'GDK'): ?>
                <button type="submit" name="action" value="reject" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Từ chối</button>
                <button type="submit" name="action" value="approve" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Phê duyệt</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- v3.0: Additional Action Buttons for Approved Applications -->
        <?php if ($app['status'] == 'Đã phê duyệt'): ?>
        <div class="mt-6 pt-4 border-t bg-blue-50 -mx-6 -mb-6 px-6 py-4 rounded-b-lg">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    <span class="font-bold">Trạng thái pháp lý:</span>
                    <?php if ($app['legal_completed']): ?>
                        <span class="text-green-600">✓ Đã hoàn tất (<?php echo date("d/m/Y", strtotime($app['legal_completed_date'])); ?>)</span>
                        <span class="ml-2 text-gray-600">| Ngày hiệu lực: <?php echo date("d/m/Y", strtotime($app['effective_date'])); ?></span>
                    <?php else: ?>
                        <span class="text-yellow-600">⚠ Chưa hoàn tất</span>
                    <?php endif; ?>
                </div>
                <div class="flex space-x-3">
                    <?php if (!$app['legal_completed'] && ($user_role == 'Admin' || $user_role == 'GDK')): ?>
                        <button type="button" onclick="showLegalCompletionModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded">
                            Hoàn tất Pháp lý
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- v3.0: Escalation button for rejected applications -->
        <?php if ($app['status'] == 'Đã từ chối' && $user_role == 'CVQHKH' && $app['created_by_id'] == $user_id): ?>
        <div class="mt-6 pt-4 border-t bg-red-50 -mx-6 -mb-6 px-6 py-4 rounded-b-lg">
            <div class="flex justify-between items-center">
                <div class="text-sm text-red-700">
                    <span class="font-bold">Hồ sơ đã bị từ chối.</span> Bạn có thể khiếu nại quyết định này lên cấp cao hơn.
                </div>
                <button type="button" onclick="showEscalationModal()" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2 px-4 rounded">
                    Khiếu nại
                </button>
            </div>
        </div>
        <?php endif; ?>
    </form>
</main>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
    <div class="mt-3 text-center">
      <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      </div>
      <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Xác nhận Gửi Hồ sơ</h3>
      <div class="mt-2 px-7 py-3 text-left">
        <p class="text-sm text-gray-500">Vui lòng kiểm tra lại thông tin trước khi gửi đi.</p>
        <div class="mt-4 border-t border-b py-2 space-y-2">
            <p><strong class="font-medium text-gray-600 w-28 inline-block">Mã HS:</strong> <span id="modal-hstd-code" class="font-mono"></span></p>
            <p><strong class="font-medium text-gray-600 w-28 inline-block">Khách hàng:</strong> <span id="modal-customer-name"></span></p>
            <p><strong class="font-medium text-gray-600 w-28 inline-block">Sản phẩm:</strong> <span id="modal-product-name"></span></p>
            <p><strong class="font-medium text-gray-600 w-28 inline-block">Số tiền:</strong> <span id="modal-amount"></span></p>
            <p><strong class="font-medium text-gray-600 w-28 inline-block align-top">Ý kiến:</strong> <span id="modal-comment" class="italic text-gray-700 inline-block w-2/3"></span></p>
        </div>
      </div>
      <div class="items-center px-4 py-3">
        <button id="cancel-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 mr-2">Hủy</button>
        <button id="confirm-submit-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Xác nhận Gửi đi</button>
      </div>
    </div>
  </div>
</div>

<!-- v3.0: Legal Completion Modal -->
<div id="legal-completion-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
    <div class="mt-3">
      <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Hoàn tất Thủ tục Pháp lý</h3>
      <form id="legal-completion-form" action="process_action.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
        <input type="hidden" name="action" value="mark_legal_completed">

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Ngày hiệu lực <span class="text-red-600">*</span></label>
            <input type="date" name="effective_date" required class="mt-1 w-full border-gray-300 rounded-md">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Ghi chú pháp lý</label>
            <textarea name="legal_notes" rows="4" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nhập ghi chú về hoàn tất thủ tục pháp lý..."></textarea>
          </div>
        </div>

        <div class="mt-5 flex justify-end space-x-3">
          <button type="button" onclick="closeLegalCompletionModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Hủy</button>
          <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">Xác nhận</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- v3.0: Escalation Modal -->
<div id="escalation-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
    <div class="mt-3">
      <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Khiếu nại Quyết định Từ chối</h3>
      <form id="escalation-form" action="process_action.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
        <input type="hidden" name="action" value="escalate">

        <div class="space-y-4">
          <div class="p-3 bg-yellow-50 border-l-4 border-yellow-400 text-sm">
            <p class="font-medium text-yellow-800">Lưu ý:</p>
            <p class="text-yellow-700">Khiếu nại sẽ được gửi lên Giám đốc  Kinh doanh (GDK) để xem xét lại quyết định từ chối.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Lý do khiếu nại <span class="text-red-600">*</span></label>
            <textarea name="comment" rows="6" required class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nhập lý do chi tiết tại sao bạn cho rằng quyết định từ chối cần được xem xét lại..."></textarea>
          </div>
        </div>

        <div class="mt-5 flex justify-end space-x-3">
          <button type="button" onclick="closeEscalationModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Hủy</button>
          <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">Gửi khiếu nại</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- v3.0: Exception Request Modal -->
<div id="exception-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
    <div class="mt-3">
      <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Xin Ngoại lệ Điều kiện</h3>
      <form id="exception-form" action="process_action.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
        <input type="hidden" name="action" value="request_exception">
        <input type="hidden" id="exception-condition-id" name="condition_id" value="">

        <div class="space-y-4">
          <div class="p-3 bg-blue-50 border-l-4 border-blue-400 text-sm">
            <p class="font-medium text-blue-800">Điều kiện:</p>
            <p id="exception-condition-text" class="text-blue-700"></p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Lý do xin ngoại lệ <span class="text-red-600">*</span></label>
            <textarea name="comment" rows="6" required class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nhập lý do chi tiết tại sao điều kiện này cần được ngoại lệ..."></textarea>
          </div>
        </div>

        <div class="mt-5 flex justify-end space-x-3">
          <button type="button" onclick="closeExceptionModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Hủy</button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Gửi yêu cầu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- v3.0: Activate Facility Modal -->
<div id="activate-facility-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
    <div class="mt-3">
      <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Kích hoạt Hạn mức</h3>
      <form id="activate-facility-form" action="process_action.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
        <input type="hidden" name="action" value="activate_facility">
        <input type="hidden" id="facility-id-input" name="facility_id" value="">

        <div class="space-y-4">
          <div class="p-3 bg-yellow-50 border-l-4 border-yellow-400 text-sm">
            <p class="font-medium text-yellow-800">Lưu ý:</p>
            <p class="text-yellow-700">Hãy đảm bảo tài sản bảo đảm (nếu có) đã được nhập kho trước khi kích hoạt hạn mức.</p>
          </div>
          <p class="text-sm text-gray-600">Bạn có chắc chắn muốn kích hoạt hạn mức này?</p>
        </div>

        <div class="mt-5 flex justify-end space-x-3">
          <button type="button" onclick="closeActivateFacilityModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Hủy</button>
          <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Kích hoạt</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab-button');
    const panels = document.querySelectorAll('.tab-panel');
    const mainForm = document.getElementById('main-form');
    const submitBtn = document.getElementById('submit-for-review-btn');
    const modal = document.getElementById('confirmation-modal');
    const cancelBtn = document.getElementById('cancel-modal-btn');
    const confirmBtn = document.getElementById('confirm-submit-btn');

    // --- FORM ACTIONS (DELETE, ETC) ---
    mainForm.addEventListener('click', function(e) {
        if (e.target.tagName === 'BUTTON' && e.target.name === 'action' && e.target.value.startsWith('delete_')) {
            if (!confirm('Bạn có chắc chắn muốn xóa mục này không?')) {
                e.preventDefault();
            }
        }
    });
    
    // --- TABS ---
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            tabs.forEach(t => {
                t.classList.remove('text-blue-600', 'border-blue-500');
                t.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'border-transparent');
            });
            panels.forEach(p => p.classList.add('hidden'));
            this.classList.add('text-blue-600', 'border-blue-500');
            this.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'border-transparent');
            const panelId = this.getAttribute('href').substring(1);
            document.getElementById('panel-' + panelId).classList.remove('hidden');
        });
    });

    // --- SUBMIT VALIDATION & MODAL ---
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            // 1. Validation
            const hasCollateral = document.querySelector('#collaterals-table-body tr td:not([colspan="4"])') !== null;
            const hasRepayment = document.querySelector('#repayment-sources-table-body tr td:not([colspan="4"])') !== null;
            const hasDocument = document.querySelector('#documents-table-body tr td:not([colspan="4"])') !== null;

            let missing = [];
            if (!hasCollateral) missing.push("Tài sản Bảo đảm");
            if (!hasRepayment) missing.push("Nguồn trả nợ");
            if (!hasDocument) missing.push("Hồ sơ đính kèm");

            if (missing.length > 0) {
                alert("Hồ sơ chưa đầy đủ. Vui lòng bổ sung các thông tin sau:\n- " + missing.join("\n- "));
                return;
            }
            
            // 2. Populate and show modal
            document.getElementById('modal-hstd-code').textContent = '<?php echo $app['hstd_code']; ?>';
            document.getElementById('modal-customer-name').textContent = '<?php echo htmlspecialchars($customer['full_name']); ?>';
            document.getElementById('modal-product-name').textContent = '<?php echo htmlspecialchars($app['product_name']); ?>';
            document.getElementById('modal-amount').textContent = '<?php echo number_format($app['amount'], 0, ',', '.'); ?> VND';
            const comment = document.getElementById('comment-textarea').value;
            document.getElementById('modal-comment').textContent = comment ? comment : "(Không có ý kiến)";

            modal.classList.remove('hidden');
        });
    }

    if(cancelBtn) {
        cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));
    }
    
    if(confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'send_for_review';
            mainForm.appendChild(actionInput);
            mainForm.submit();
        });
    }

});

// v3.0: New modal functions
function showLegalCompletionModal() {
    document.getElementById('legal-completion-modal').classList.remove('hidden');
}

function closeLegalCompletionModal() {
    document.getElementById('legal-completion-modal').classList.add('hidden');
}

function showEscalationModal() {
    document.getElementById('escalation-modal').classList.remove('hidden');
}

function closeEscalationModal() {
    document.getElementById('escalation-modal').classList.add('hidden');
}

function requestException(conditionId) {
    // Get condition text from table
    const conditionRow = event.target.closest('tr');
    const conditionText = conditionRow.querySelector('td:nth-child(2)').textContent;

    document.getElementById('exception-condition-id').value = conditionId;
    document.getElementById('exception-condition-text').textContent = conditionText;
    document.getElementById('exception-modal').classList.remove('hidden');
}

function closeExceptionModal() {
    document.getElementById('exception-modal').classList.add('hidden');
}

function approveException(conditionId) {
    if (confirm('Bạn có chắc chắn muốn chấp thuận ngoại lệ cho điều kiện này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process_action.php';

        const inputs = [
            {name: 'csrf_token', value: '<?php echo generate_csrf_token(); ?>'},
            {name: 'application_id', value: '<?php echo $app['id']; ?>'},
            {name: 'action', value: 'approve_exception'},
            {name: 'condition_id', value: conditionId}
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

function rejectException(conditionId) {
    const reason = prompt('Nhập lý do từ chối ngoại lệ:');
    if (reason && reason.trim() !== '') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process_action.php';

        const inputs = [
            {name: 'csrf_token', value: '<?php echo generate_csrf_token(); ?>'},
            {name: 'application_id', value: '<?php echo $app['id']; ?>'},
            {name: 'action', value: 'reject_exception'},
            {name: 'condition_id', value: conditionId},
            {name: 'comment', value: reason}
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

function activateFacility(facilityId) {
    document.getElementById('facility-id-input').value = facilityId;
    document.getElementById('activate-facility-modal').classList.remove('hidden');
}

function closeActivateFacilityModal() {
    document.getElementById('activate-facility-modal').classList.add('hidden');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = [
        'legal-completion-modal',
        'escalation-modal',
        'exception-modal',
        'activate-facility-modal'
    ];

    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    });
};
</script>

<?php include 'includes/footer.php'; ?>

