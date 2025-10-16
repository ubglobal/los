<?php
// File: create_application.php
session_start();
require_once "config/db.php";
require_once "includes/functions.php";

// CVQHKH access only
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'CVQHKH') {
    header("location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_POST['customer_id'];
    $product_id = $_POST['product_id'];
    $amount = $_POST['amount'];
    $purpose = trim($_POST['purpose']);
    $created_by_id = $_SESSION['id'];
    
    // Simple validation
    if (empty($customer_id) || empty($product_id) || empty($amount) || empty($purpose)) {
        $error = "Vui lòng điền đầy đủ thông tin.";
    } else {
        $hstd_code = "APP." . date("Y") . "." . rand(1000, 9999);
        $status = "Đang xử lý";
        $stage = "Khởi tạo hồ sơ tín dụng";
        $assigned_to_id = $created_by_id;

        $sql = "INSERT INTO credit_applications (hstd_code, customer_id, product_id, amount, purpose, status, stage, assigned_to_id, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "siidsssii", $hstd_code, $customer_id, $product_id, $amount, $purpose, $status, $stage, $assigned_to_id, $created_by_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $new_app_id = mysqli_insert_id($stmt);
                add_history($link, $new_app_id, $created_by_id, 'Khởi tạo', 'Hồ sơ được tạo mới.');
                header("location: application_detail.php?id=" . $new_app_id);
                exit;
            } else {
                $error = "Đã có lỗi xảy ra. Vui lòng thử lại.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$all_customers = get_all_customers($link);
$all_products = get_all_products($link);

$pageTitle = "Khởi tạo Hồ sơ Tín dụng mới";
include 'includes/header.php';
?>

<main class="flex-1 workspace overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Khởi tạo Hồ sơ Tín dụng mới</h1>
        <a href="index.php" class="bg-white hover:bg-gray-100 text-gray-800 font-semibold py-2 px-4 border border-gray-300 rounded shadow-sm">Quay lại Hộp CV</a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md max-w-2xl mx-auto">
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="create_application.php" method="POST" class="space-y-4">
            <div>
                <label for="customer_id" class="block text-sm font-medium text-gray-700">Chọn Khách hàng</label>
                <select id="customer_id" name="customer_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">-- Vui lòng chọn --</option>
                    <?php foreach ($all_customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['full_name']) . " (" . htmlspecialchars($customer['customer_code']) . ")"; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="product_id" class="block text-sm font-medium text-gray-700">Chọn Sản phẩm Tín dụng</label>
                <select id="product_id" name="product_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">-- Vui lòng chọn --</option>
                     <?php foreach ($all_products as $product): ?>
                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Số tiền đề nghị vay (VND)</label>
                <input type="number" id="amount" name="amount" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Vd: 700000000">
            </div>

            <div>
                <label for="purpose" class="block text-sm font-medium text-gray-700">Mục đích vay chi tiết</label>
                <textarea id="purpose" name="purpose" rows="3" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Vd: Vay mua xe Toyota Vios 1.5G 2024"></textarea>
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Tạo và Mở Hồ sơ</button>
            </div>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

