<?php
// File: admin/manage_document_definitions.php - v3.0
// Document Definition Management (Admin Only)

require_once "../config/session.php";
require_once "includes/admin_init.php";

$pageTitle = "Qu£n lý Ënh ngh)a tài liÇu";

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    require_once "../config/csrf.php";
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed");
    }

    $action = $_POST['action'] ?? '';

    if ($action == 'add') {
        $doc_name = trim($_POST['doc_name']);
        $doc_type = trim($_POST['doc_type']);
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $description = trim($_POST['description']);

        if (!empty($doc_name) && !empty($doc_type)) {
            $sql = "INSERT INTO document_definitions (doc_name, doc_type, is_required, description) VALUES (?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssis", $doc_name, $doc_type, $is_required, $description);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "ã thêm Ënh ngh)a tài liÇu mÛi thành công.";
                } else {
                    $error_msg = "L×i khi thêm Ënh ngh)a tài liÇu: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $error_msg = "Vui lòng iÁn §y ç thông tin b¯t buÙc.";
        }
    } elseif ($action == 'edit') {
        $doc_id = (int)$_POST['doc_id'];
        $doc_name = trim($_POST['doc_name']);
        $doc_type = trim($_POST['doc_type']);
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $description = trim($_POST['description']);

        if ($doc_id > 0 && !empty($doc_name) && !empty($doc_type)) {
            $sql = "UPDATE document_definitions SET doc_name = ?, doc_type = ?, is_required = ?, description = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssisi", $doc_name, $doc_type, $is_required, $description, $doc_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "ã c­p nh­t Ënh ngh)a tài liÇu thành công.";
                } else {
                    $error_msg = "L×i khi c­p nh­t Ënh ngh)a tài liÇu: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $error_msg = "Dï liÇu không hãp lÇ.";
        }
    } elseif ($action == 'delete') {
        $doc_id = (int)$_POST['doc_id'];

        if ($doc_id > 0) {
            // Check if document definition is used in any applications
            $sql_check = "SELECT COUNT(*) as count FROM application_documents WHERE document_definition_id = ?";
            if ($stmt = mysqli_prepare($link, $sql_check)) {
                mysqli_stmt_bind_param($stmt, "i", $doc_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);

                if ($row['count'] > 0) {
                    $error_msg = "Không thÃ xóa Ënh ngh)a tài liÇu này vì ang °ãc sí dång trong " . $row['count'] . " hÓ s¡.";
                } else {
                    // Safe to delete
                    $sql_delete = "DELETE FROM document_definitions WHERE id = ?";
                    if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
                        mysqli_stmt_bind_param($stmt_delete, "i", $doc_id);
                        if (mysqli_stmt_execute($stmt_delete)) {
                            $success_msg = "ã xóa Ënh ngh)a tài liÇu thành công.";
                        } else {
                            $error_msg = "L×i khi xóa Ënh ngh)a tài liÇu: " . mysqli_error($link);
                        }
                        mysqli_stmt_close($stmt_delete);
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Get all document definitions
$sql = "SELECT * FROM document_definitions ORDER BY doc_type, doc_name";
$result = mysqli_query($link, $sql);
$doc_definitions = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $doc_definitions[] = $row;
    }
}

include "includes/header.php";
?>

<main class="flex-1 workspace overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Qu£n lý Ënh ngh)a tài liÇu</h1>
        <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md">
            + Thêm Ënh ngh)a mÛi
        </button>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($success_msg); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="p-3 font-semibold text-gray-600">ID</th>
                    <th class="p-3 font-semibold text-gray-600">Tên tài liÇu</th>
                    <th class="p-3 font-semibold text-gray-600">Lo¡i</th>
                    <th class="p-3 font-semibold text-gray-600">B¯t buÙc</th>
                    <th class="p-3 font-semibold text-gray-600">Mô t£</th>
                    <th class="p-3 font-semibold text-gray-600">Hành Ùng</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($doc_definitions)): ?>
                    <tr>
                        <td colspan="6" class="text-center p-8 text-gray-500">
                            Ch°a có Ënh ngh)a tài liÇu nào. Hãy thêm mÛi.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($doc_definitions as $doc): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3"><?php echo (int)$doc['id']; ?></td>
                            <td class="p-3 font-semibold"><?php echo htmlspecialchars($doc['doc_name']); ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($doc['doc_type']); ?>
                                </span>
                            </td>
                            <td class="p-3">
                                <?php if ($doc['is_required']): ?>
                                    <span class="text-red-600 font-semibold">B¯t buÙc</span>
                                <?php else: ?>
                                    <span class="text-gray-500">Không b¯t buÙc</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-sm text-gray-600">
                                <?php echo htmlspecialchars($doc['description'] ?? ''); ?>
                            </td>
                            <td class="p-3">
                                <button onclick='openEditModal(<?php echo json_encode($doc); ?>)'
                                        class="text-blue-600 hover:text-blue-800 mr-3">
                                    Sía
                                </button>
                                <button onclick="confirmDelete(<?php echo $doc['id']; ?>, '<?php echo addslashes($doc['doc_name']); ?>')"
                                        class="text-red-600 hover:text-red-800">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Add/Edit Modal -->
<div id="docModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 mb-4">Thêm Ënh ngh)a tài liÇu</h3>
            <form id="docForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="doc_id" id="docId" value="">

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Tên tài liÇu *</label>
                    <input type="text" name="doc_name" id="docName" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Lo¡i tài liÇu *</label>
                    <select name="doc_type" id="docType" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- ChÍn lo¡i --</option>
                        <option value="Identity">Gi¥y tÝ tùy thân</option>
                        <option value="Financial">Tài chính</option>
                        <option value="Legal">Pháp lý</option>
                        <option value="Collateral">Tài s£n £m b£o</option>
                        <option value="Business">Kinh doanh</option>
                        <option value="Other">Khác</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_required" id="isRequired" class="mr-2">
                        <span class="text-gray-700 text-sm font-bold">Tài liÇu b¯t buÙc</span>
                    </label>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Mô t£</label>
                    <textarea name="description" id="docDescription" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Hçy
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        L°u
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="doc_id" id="deleteDocId" value="">
</form>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Thêm Ënh ngh)a tài liÇu';
    document.getElementById('formAction').value = 'add';
    document.getElementById('docId').value = '';
    document.getElementById('docName').value = '';
    document.getElementById('docType').value = '';
    document.getElementById('isRequired').checked = false;
    document.getElementById('docDescription').value = '';
    document.getElementById('docModal').classList.remove('hidden');
}

function openEditModal(doc) {
    document.getElementById('modalTitle').textContent = 'Sía Ënh ngh)a tài liÇu';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('docId').value = doc.id;
    document.getElementById('docName').value = doc.doc_name;
    document.getElementById('docType').value = doc.doc_type;
    document.getElementById('isRequired').checked = doc.is_required == 1;
    document.getElementById('docDescription').value = doc.description || '';
    document.getElementById('docModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('docModal').classList.add('hidden');
}

function confirmDelete(id, name) {
    if (confirm('B¡n có ch¯c ch¯n muÑn xóa Ënh ngh)a tài liÇu "' + name + '"?')) {
        document.getElementById('deleteDocId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal when clicking outside
document.getElementById('docModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include "includes/footer.php"; ?>
