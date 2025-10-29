<?php
// File: admin/manage_users.php - SECURE VERSION
require_once "includes/admin_init.php";

$pageTitle = "Quản lý Người dùng";
include 'includes/header.php';

// Handle form submission for adding/editing users
$user_id = $username = $full_name = $role = $branch = $password = "";
$approval_limit = null;
$is_editing = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $user_id = $_POST['user_id'] ?? null;
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $role = trim($_POST['role']);
    $branch = trim($_POST['branch']);
    $password = $_POST['password'];
    $approval_limit = !empty($_POST['approval_limit']) ? trim($_POST['approval_limit']) : null;

    if (empty($username) || empty($full_name) || empty($role) || empty($branch)) {
        $errors[] = "Vui lòng điền đầy đủ các trường bắt buộc.";
    }

    if (empty($user_id) && empty($password)) {
        $errors[] = "Mật khẩu là bắt buộc cho người dùng mới.";
    }

    if (empty($errors)) {
        if ($user_id) { // Update existing user
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, full_name = ?, role = ?, branch = ?, password_hash = ?, approval_limit = ? WHERE id = ?";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, "sssssdi", $username, $full_name, $role, $branch, $password_hash, $approval_limit, $user_id);
            } else {
                $sql = "UPDATE users SET username = ?, full_name = ?, role = ?, branch = ?, approval_limit = ? WHERE id = ?";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, "ssssdi", $username, $full_name, $role, $branch, $approval_limit, $user_id);
            }
        } else { // Insert new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, full_name, role, branch, password_hash, approval_limit) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "sssssd", $username, $full_name, $role, $branch, $password_hash, $approval_limit);
        }

        if (mysqli_stmt_execute($stmt)) {
            header("location: manage_users.php");
            exit;
        } else {
            $errors[] = "Đã có lỗi xảy ra. Vui lòng thử lại.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch user for editing
if (isset($_GET['edit'])) {
    $is_editing = true;
    $user_id = $_GET['edit'];
    $user_data = get_user_by_id($link, $user_id);
    if ($user_data) {
        $username = $user_data['username'];
        $full_name = $user_data['full_name'];
        $role = $user_data['role'];
        $branch = $user_data['branch'];
        $approval_limit = $user_data['approval_limit'];
    }
}

$all_users = get_all_users($link);
?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Quản lý Người dùng</h1>

    <!-- Add/Edit Form -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4"><?php echo $is_editing ? "Chỉnh sửa Người dùng" : "Thêm Người dùng mới"; ?></h2>
        
        <?php if(!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="manage_users.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Tên đăng nhập</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                 <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Họ và Tên</label>
                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                 <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Vai trò</label>
                    <select name="role" id="role" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                        <option value="CVQHKH" <?php if($role == 'CVQHKH') echo 'selected'; ?>>CVQHKH</option>
                        <option value="CVTĐ" <?php if($role == 'CVTĐ') echo 'selected'; ?>>CVTĐ</option>
                        <option value="CPD" <?php if($role == 'CPD') echo 'selected'; ?>>CPD</option>
                        <option value="GDK" <?php if($role == 'GDK') echo 'selected'; ?>>GDK</option>
                        <option value="Admin" <?php if($role == 'Admin') echo 'selected'; ?>>Admin</option>
                    </select>
                </div>
                 <div>
                    <label for="branch" class="block text-sm font-medium text-gray-700">Chi nhánh</label>
                    <input type="text" name="branch" id="branch" value="<?php echo htmlspecialchars($branch); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                 <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu mới</label>
                    <input type="password" name="password" id="password" placeholder="<?php echo $is_editing ? 'Để trống nếu không đổi' : ''; ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="approval_limit" class="block text-sm font-medium text-gray-700">Hạn mức phê duyệt (VND)</label>
                    <input type="number" name="approval_limit" id="approval_limit" value="<?php echo htmlspecialchars($approval_limit); ?>" placeholder="Chỉ dành cho CPD/GDK" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <?php if ($is_editing): ?>
                    <a href="manage_users.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">Hủy</a>
                <?php endif; ?>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?php echo $is_editing ? "Cập nhật" : "Thêm mới"; ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Users List -->
    <div class="bg-white p-6 rounded-lg shadow-md">
         <h2 class="text-xl font-semibold mb-4">Danh sách Người dùng</h2>
         <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 border-b">ID</th>
                        <th class="py-2 px-4 border-b">Tên đăng nhập</th>
                        <th class="py-2 px-4 border-b">Họ và Tên</th>
                        <th class="py-2 px-4 border-b">Vai trò</th>
                        <th class="py-2 px-4 border-b">Chi nhánh</th>
                        <th class="py-2 px-4 border-b text-right">Hạn mức P.Duyệt (VND)</th>
                        <th class="py-2 px-4 border-b">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-b"><?php echo $user['id']; ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($user['branch']); ?></td>
                        <td class="py-2 px-4 border-b text-right"><?php echo $user['approval_limit'] ? number_format($user['approval_limit'], 0, ',', '.') : 'N/A'; ?></td>
                        <td class="py-2 px-4 border-b">
                            <a href="manage_users.php?edit=<?php echo $user['id']; ?>" class="text-blue-600 hover:underline">Sửa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

