<?php
// File: admin/customer_detail.php
session_start();
require_once "../config/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'Admin') {
    header("location: ../login.php");
    exit;
}

$customer_id = $_GET['id'] ?? null;
if(!$customer_id) die("Missing customer ID.");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_related_party') {
    $related_customer_id = $_POST['related_customer_id'];
    $relationship_type = trim($_POST['relationship_type']);
    if (!empty($related_customer_id) && !empty($relationship_type)) {
        // Add relationship A -> B
        $sql = "INSERT INTO customer_related_parties (customer_id, related_customer_id, relationship_type) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "iis", $customer_id, $related_customer_id, $relationship_type);
            mysqli_stmt_execute($stmt);
        }
        // Add inverse relationship B -> A
        // This is a simplification; a real system might need more nuanced logic
        $inverse_relationship = "Có liên quan với " . get_customer_by_id($link, $customer_id)['full_name'];
         $sql_inv = "INSERT INTO customer_related_parties (customer_id, related_customer_id, relationship_type) VALUES (?, ?, ?)";
        if($stmt_inv = mysqli_prepare($link, $sql_inv)) {
            mysqli_stmt_bind_param($stmt_inv, "iis", $related_customer_id, $customer_id, $inverse_relationship);
            mysqli_stmt_execute($stmt_inv);
        }
    }
    header("location: customer_detail.php?id=" . $customer_id);
    exit;
}

$customer = get_customer_by_id($link, $customer_id);
$related_parties = get_related_parties_for_customer($link, $customer_id);
$all_customers = get_all_customers($link); // For dropdown

$pageTitle = "Chi tiết Khách hàng";
include 'includes/header.php';
?>
<main class="p-6">
    <a href="manage_customers.php" class="text-blue-600 hover:underline">&larr; Quay lại Danh sách Khách hàng</a>
    <h1 class="text-2xl font-bold my-4">Chi tiết: <?php echo htmlspecialchars($customer['full_name']); ?></h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Customer Info -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Thông tin chung</h2>
            <!-- Display customer info here -->
             <p><strong>Mã KH:</strong> <?php echo htmlspecialchars($customer['customer_code']); ?></p>
             <p><strong>Loại KH:</strong> <?php echo htmlspecialchars($customer['customer_type']); ?></p>
             <!-- ... other fields ... -->
        </div>

        <!-- Related Parties -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Người có liên quan</h2>
            <ul class="space-y-2 mb-4">
                <?php foreach($related_parties as $party): ?>
                    <li class="p-2 bg-gray-50 rounded-md"><strong><?php echo htmlspecialchars($party['relationship_type']); ?>:</strong> <?php echo htmlspecialchars($party['related_customer_name']); ?></li>
                <?php endforeach; ?>
                 <?php if(empty($related_parties)) { echo '<p class="text-gray-500">Chưa có thông tin.</p>'; } ?>
            </ul>

            <form action="customer_detail.php?id=<?php echo $customer_id; ?>" method="POST" class="space-y-3 border-t pt-4">
                <input type="hidden" name="action" value="add_related_party">
                <h3 class="font-medium">Thêm người liên quan mới</h3>
                <div>
                    <label class="block text-sm">Chọn khách hàng liên quan</label>
                    <select name="related_customer_id" class="mt-1 block w-full border-gray-300 rounded-md">
                        <option value="">-- Chọn --</option>
                        <?php foreach($all_customers as $c): if($c['id'] != $customer_id): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                 <div>
                    <label class="block text-sm">Loại quan hệ</label>
                    <input type="text" name="relationship_type" placeholder="VD: Vợ/Chồng, Công ty con" class="mt-1 block w-full border-gray-300 rounded-md">
                </div>
                <div class="text-right"><button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Thêm</button></div>
            </form>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
