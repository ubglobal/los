<?php
// File: admin/manage_products.php - SECURE VERSION
require_once "includes/admin_init.php";

$pageTitle = "Quản lý Sản phẩm";
include 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_name'])) {
    // Verify CSRF token
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $product_id = $_POST['product_id'] ?? null;
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    
    if (!empty($product_name)) {
        if ($product_id) { // Update
            $sql = "UPDATE products SET name = ?, description = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $product_name, $description, $product_id);
        } else { // Insert
            $sql = "INSERT INTO products (name, description) VALUES (?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $product_name, $description);
        }
        mysqli_stmt_execute($stmt);
        header("location: manage_products.php");
        exit;
    }
}

// Fetch item for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_GET['edit']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_product = mysqli_fetch_assoc($result);
}

$all_products = get_all_products($link);
?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Quản lý Sản phẩm Tín dụng</h1>

    <!-- Add/Edit Form -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4"><?php echo $edit_product ? "Chỉnh sửa Sản phẩm" : "Thêm Sản phẩm mới"; ?></h2>
        <form action="manage_products.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="product_id" value="<?php echo $edit_product['id'] ?? ''; ?>">
            <div>
                <label for="product_name" class="block text-sm font-medium text-gray-700">Tên sản phẩm</label>
                <input type="text" name="product_name" id="product_name" value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Mô tả</label>
                <textarea name="description" id="description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                 <?php if ($edit_product): ?>
                    <a href="manage_products.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">Hủy</a>
                <?php endif; ?>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?php echo $edit_product ? "Cập nhật" : "Thêm mới"; ?>
                </button>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white p-6 rounded-lg shadow-md">
         <h2 class="text-xl font-semibold mb-4">Danh sách Sản phẩm</h2>
         <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 border-b">ID</th>
                        <th class="py-2 px-4 border-b">Tên sản phẩm</th>
                        <th class="py-2 px-4 border-b">Mô tả</th>
                        <th class="py-2 px-4 border-b">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_products as $product): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-b"><?php echo $product['id']; ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($product['description']); ?></td>
                        <td class="py-2 px-4 border-b">
                            <a href="manage_products.php?edit=<?php echo $product['id']; ?>" class="text-blue-600 hover:underline">Sửa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

