<?php
// File: admin/manage_customers.php - SECURE VERSION
require_once "includes/admin_init.php";

$pageTitle = "Quản lý Khách hàng";
include 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_customer') {
    // Verify CSRF token
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $customer_type = $_POST['customer_type'];
    $full_name = trim($_POST['full_name']);
    $id_number = ($customer_type == 'CÁ NHÂN') ? trim($_POST['id_number']) : null;
    $dob = ($customer_type == 'CÁ NHÂN') ? trim($_POST['dob']) : null;
    $company_tax_code = ($customer_type == 'DOANH NGHIỆP') ? trim($_POST['company_tax_code']) : null;
    $company_representative = ($customer_type == 'DOANH NGHIỆP') ? trim($_POST['company_representative']) : null;
    $phone_number = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $error = null;

    // Validation
    if (empty($full_name)) {
        $error = "Vui lòng nhập tên khách hàng.";
    }

    // Phone validation (Vietnamese format)
    if (!empty($phone_number) && !preg_match('/^(0|\+84)[0-9]{9}$/', $phone_number)) {
        $error = "Số điện thoại không hợp lệ (10 số, bắt đầu bằng 0 hoặc +84).";
    }

    // Email validation
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    }

    // Check for duplicates - FIX BUG-010
    if (empty($error)) {
        $check_sql = "SELECT id FROM customers WHERE full_name = ?";
        $check_params = [$full_name];

        if ($customer_type == 'CÁ NHÂN' && !empty($id_number)) {
            $check_sql .= " OR id_number = ?";
            $check_params[] = $id_number;
        }

        if ($customer_type == 'DOANH NGHIỆP' && !empty($company_tax_code)) {
            $check_sql .= " OR company_tax_code = ?";
            $check_params[] = $company_tax_code;
        }

        if ($check_stmt = mysqli_prepare($link, $check_sql)) {
            $types = str_repeat('s', count($check_params));
            mysqli_stmt_bind_param($check_stmt, $types, ...$check_params);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {
                $error = "Khách hàng đã tồn tại (tên, CCCD hoặc MST trùng).";
            }
            mysqli_stmt_close($check_stmt);
        }
    }

    // Generate unique customer code using sequence - FIX BUG-009
    if (empty($error)) {
        $seq_sql = "INSERT INTO customer_code_sequence (customer_type) VALUES (?)";
        $customer_code = null;

        if ($seq_stmt = mysqli_prepare($link, $seq_sql)) {
            mysqli_stmt_bind_param($seq_stmt, "s", $customer_type);
            if (mysqli_stmt_execute($seq_stmt)) {
                $sequence_id = mysqli_insert_id($link);
                $prefix = ($customer_type == 'CÁ NHÂN') ? 'CN' : 'DN';
                $customer_code = $prefix . "." . str_pad($sequence_id, 6, '0', STR_PAD_LEFT);
            } else {
                error_log("Failed to generate customer code: " . mysqli_error($link));
                $error = "Lỗi hệ thống khi tạo mã khách hàng.";
            }
            mysqli_stmt_close($seq_stmt);
        }
    }

    // Insert customer if no errors
    if (empty($error) && $customer_code !== null) {
        $sql = "INSERT INTO customers (customer_code, customer_type, full_name, id_number, dob, address, phone_number, email, company_tax_code, company_representative)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            $dob_param = !empty($dob) ? $dob : null;
            mysqli_stmt_bind_param($stmt, "ssssssssss",
                $customer_code, $customer_type, $full_name, $id_number, $dob_param,
                $address, $phone_number, $email,
                $company_tax_code, $company_representative
            );

            if (mysqli_stmt_execute($stmt)) {
                error_log("New customer created: code={$customer_code}, type={$customer_type}, name={$full_name}");
                header("location: manage_customers.php?success=1");
                exit;
            } else {
                error_log("Customer insert failed: " . mysqli_error($link));
                $error = "Lỗi khi thêm khách hàng. Vui lòng thử lại.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // If we reach here, there was an error
    if (!empty($error)) {
        $_SESSION['customer_error'] = $error;
    }
    header("location: manage_customers.php");
    exit;
}

$all_customers = get_all_customers($link);
?>

<main class="p-6">
    <h1 class="text-2xl font-bold mb-4">Quản lý Khách hàng</h1>

    <!-- Add Form -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4">Thêm Khách hàng mới</h2>

        <?php if (isset($_SESSION['customer_error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['customer_error']); ?></span>
            </div>
            <?php unset($_SESSION['customer_error']); ?>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Khách hàng đã được thêm thành công!</span>
            </div>
        <?php endif; ?>

        <form action="manage_customers.php" method="POST" class="space-y-4" id="customer-form">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="add_customer">
            <input type="hidden" name="full_name" id="full_name_hidden">

            <div>
                <label class="block text-sm font-medium text-gray-700">Loại khách hàng</label>
                <select name="customer_type" id="customer_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                    <option value="CÁ NHÂN">Cá nhân</option>
                    <option value="DOANH NGHIỆP">Doanh nghiệp</option>
                </select>
            </div>
            
            <div id="individual-fields">
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium">Họ và Tên</label><input type="text" id="full_name_individual" class="mt-1 block w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-sm font-medium">Số CCCD/CMND</label><input type="text" name="id_number" class="mt-1 block w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-sm font-medium">Ngày sinh</label><input type="date" name="dob" class="mt-1 block w-full border-gray-300 rounded-md"></div>
                </div>
            </div>

            <div id="corporate-fields" class="hidden">
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium">Tên Doanh nghiệp</label><input type="text" id="full_name_corporate" class="mt-1 block w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-sm font-medium">Mã số thuế</label><input type="text" name="company_tax_code" class="mt-1 block w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-sm font-medium">Người đại diện</label><input type="text" name="company_representative" class="mt-1 block w-full border-gray-300 rounded-md"></div>
                 </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium">Số điện thoại</label><input type="text" name="phone_number" class="mt-1 block w-full border-gray-300 rounded-md"></div>
                <div><label class="block text-sm font-medium">Email</label><input type="email" name="email" class="mt-1 block w-full border-gray-300 rounded-md"></div>
            </div>
            <div><label class="block text-sm font-medium">Địa chỉ</label><textarea name="address" rows="2" class="mt-1 block w-full border-gray-300 rounded-md"></textarea></div>

            <div class="flex justify-end"><button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Thêm mới</button></div>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white p-6 rounded-lg shadow-md">
         <h2 class="text-xl font-semibold mb-4">Danh sách Khách hàng</h2>
         <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr><th class="py-2 px-4 border-b">Mã KH</th><th class="py-2 px-4 border-b">Tên Khách hàng</th><th class="py-2 px-4 border-b">Loại KH</th><th class="py-2 px-4 border-b">Số GTTT / MST</th><th class="py-2 px-4 border-b">Hành động</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($all_customers as $customer): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($customer['customer_code']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($customer['full_name']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($customer['customer_type']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($customer['id_number'] ?: $customer['company_tax_code']); ?></td>
                        <td class="py-2 px-4 border-b"><a href="customer_detail.php?id=<?php echo $customer['id']; ?>" class="text-blue-600 hover:underline">Xem/Sửa chi tiết</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script>
    // JS from previous turn to toggle fields and set hidden full_name
    const typeSelect = document.getElementById('customer_type');
    const individualFields = document.getElementById('individual-fields');
    const corporateFields = document.getElementById('corporate-fields');
    const hiddenFullName = document.getElementById('full_name_hidden');
    const individualNameInput = document.getElementById('full_name_individual');
    const corporateNameInput = document.getElementById('full_name_corporate');
    const form = document.getElementById('customer-form');

    function toggleCustomerFields() {
        if (typeSelect.value === 'CÁ NHÂN') {
            individualFields.classList.remove('hidden');
            corporateFields.classList.add('hidden');
        } else {
            individualFields.classList.add('hidden');
            corporateFields.classList.remove('hidden');
        }
    }

    form.addEventListener('submit', function() {
        if (typeSelect.value === 'CÁ NHÂN') {
            hiddenFullName.value = individualNameInput.value;
        } else {
            hiddenFullName.value = corporateNameInput.value;
        }
    });

    typeSelect.addEventListener('change', toggleCustomerFields);
    toggleCustomerFields();
</script>

<?php include 'includes/footer.php'; ?>

