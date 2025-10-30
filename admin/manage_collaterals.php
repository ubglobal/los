<?php
// File: admin/manage_collaterals.php - SECURE VERSION
require_once "includes/admin_init.php";

$pageTitle = "Quản lý Loại TSBĐ";
include 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['collateral_name'])) {
    // Verify CSRF token
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $collateral_id = $_POST['collateral_id'] ?? null;
    $collateral_name = trim($_POST['collateral_name']);
    
    if (!empty($collateral_name)) {
        // FIX BUG-021: Use correct column name 'type_name' instead of 'name'
        if ($collateral_id) { // Update
            $sql = "UPDATE collateral_types SET type_name = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "si", $collateral_name, $collateral_id);
        } else { // Insert
            $sql = "INSERT INTO collateral_types (type_name) VALUES (?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "s", $collateral_name);
        }
        mysqli_stmt_execute($stmt);
        header("location: manage_collaterals.php");
        exit;
    }
}

// Fetch item for editing
$edit_collateral = null;
if (isset($_GET['edit'])) {
    $sql = "SELECT * FROM collateral_types WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_GET['edit']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_collateral = mysqli_fetch_assoc($result);
}

$sql = "SELECT * FROM collateral_types ORDER BY name";
$result = mysqli_query($link, $sql);
$all_collaterals = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Quản lý Loại Tài sản Bảo đảm</h1>

    <!-- Add/Edit Form -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4"><?php echo $edit_collateral ? "Chỉnh sửa Loại TSBĐ" : "Thêm Loại TSBĐ mới"; ?></h2>
        <form action="manage_collaterals.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="collateral_id" value="<?php echo $edit_collateral['id'] ?? ''; ?>">
            <div>
                <label for="collateral_name" class="block text-sm font-medium text-gray-700">Tên loại TSBĐ</label>
                <input type="text" name="collateral_name" id="collateral_name" value="<?php echo htmlspecialchars($edit_collateral['type_name'] ?? ''); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
            </div>
            <div class="flex justify-end space-x-3">
                 <?php if ($edit_collateral): ?>
                    <a href="manage_collaterals.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">Hủy</a>
                <?php endif; ?>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?php echo $edit_collateral ? "Cập nhật" : "Thêm mới"; ?>
                </button>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white p-6 rounded-lg shadow-md">
         <h2 class="text-xl font-semibold mb-4">Danh sách các Loại TSBĐ</h2>
         <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 border-b">ID</th>
                        <th class="py-2 px-4 border-b">Tên loại TSBĐ</th>
                        <th class="py-2 px-4 border-b">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_collaterals as $collateral): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-b"><?php echo $collateral['id']; ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($collateral['type_name']); ?></td>
                        <td class="py-2 px-4 border-b">
                            <a href="manage_collaterals.php?edit=<?php echo $collateral['id']; ?>" class="text-blue-600 hover:underline">Sửa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

