<?php
// File: admin/customer_detail.php - SECURE VERSION
require_once "includes/admin_init.php";

// FIX INPUT-001: Type cast customer_id
$customer_id = (int)($_GET['id'] ?? 0);
if($customer_id <= 0) die("Invalid customer ID.");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_related_party') {
    // Verify CSRF token
    verify_csrf_token($_POST['csrf_token'] ?? '');

    // Type casting for security
    $related_customer_id = (int)($_POST['related_customer_id'] ?? 0);
    $relationship_type = trim($_POST['relationship_type'] ?? '');

    $error = null;

    // Validation
    if ($related_customer_id <= 0) {
        $error = "Vui lòng chọn khách hàng liên quan.";
    } elseif (empty($relationship_type)) {
        $error = "Vui lòng nhập loại quan hệ.";
    } elseif (strlen($relationship_type) > 100) {
        $error = "Loại quan hệ quá dài (tối đa 100 ký tự).";
    }

    // FIX BUG-011: Check for duplicate relationship
    if (empty($error)) {
        $check_sql = "SELECT id FROM customer_related_parties WHERE customer_id = ? AND related_customer_id = ?";
        if ($check_stmt = mysqli_prepare($link, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, "ii", $customer_id, $related_customer_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            if (mysqli_num_rows($check_result) > 0) {
                $error = "Quan hệ này đã tồn tại.";
            }
            mysqli_stmt_close($check_stmt);
        }
    }

    // Insert if no errors
    if (empty($error)) {
        // Add relationship A -> B
        $sql = "INSERT INTO customer_related_parties (customer_id, related_customer_id, relationship_type) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "iis", $customer_id, $related_customer_id, $relationship_type);
            if (mysqli_stmt_execute($stmt)) {
                error_log("Related party added: customer_id={$customer_id}, related_id={$related_customer_id}, type={$relationship_type}");

                // Add inverse relationship B -> A
                $current_customer = get_customer_by_id($link, $customer_id);
                $inverse_relationship = "Có liên quan với " . $current_customer['full_name'];
                $sql_inv = "INSERT INTO customer_related_parties (customer_id, related_customer_id, relationship_type) VALUES (?, ?, ?)";
                if ($stmt_inv = mysqli_prepare($link, $sql_inv)) {
                    mysqli_stmt_bind_param($stmt_inv, "iis", $related_customer_id, $customer_id, $inverse_relationship);
                    mysqli_stmt_execute($stmt_inv);
                    mysqli_stmt_close($stmt_inv);
                }

                header("location: customer_detail.php?id=" . $customer_id . "&success=1");
                exit;
            } else {
                error_log("Related party insert failed: " . mysqli_error($link));
                $error = "Lỗi khi thêm người liên quan.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Store error in session
    if (!empty($error)) {
        $_SESSION['related_party_error'] = $error;
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

            <?php if (isset($_SESSION['related_party_error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['related_party_error']); ?></span>
                </div>
                <?php unset($_SESSION['related_party_error']); ?>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">Người liên quan đã được thêm thành công!</span>
                </div>
            <?php endif; ?>

            <ul class="space-y-2 mb-4">
                <?php foreach($related_parties as $party): ?>
                    <li class="p-2 bg-gray-50 rounded-md"><strong><?php echo htmlspecialchars($party['relationship_type']); ?>:</strong> <?php echo htmlspecialchars($party['related_customer_name']); ?></li>
                <?php endforeach; ?>
                 <?php if(empty($related_parties)) { echo '<p class="text-gray-500">Chưa có thông tin.</p>'; } ?>
            </ul>

            <form action="customer_detail.php?id=<?php echo $customer_id; ?>" method="POST" class="space-y-3 border-t pt-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
