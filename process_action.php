<?php
// File: process_action.php - SECURE VERSION
require_once "config/session.php";
init_secure_session();
require_once "config/db.php";
require_once "config/csrf.php";
require_once "includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    die("Access Denied.");
}

// Check session timeout
check_session_timeout();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $application_id = (int)($_POST['application_id'] ?? 0);
    $action = $_POST['action'] ?? null;
    $comment = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['id'];
    $user_role = $_SESSION['role'];

    // Validate application_id
    if ($application_id <= 0) {
        http_response_code(400);
        die("Invalid application ID.");
    }

    $app = get_application_details($link, $application_id);
    if (!$app) {
        http_response_code(404);
        die("Application not found.");
    }

    // --- DATA MANAGEMENT ACTIONS (add/delete collateral etc.) ---
    if (strpos($action, 'delete_') === 0) {
        $parts = explode('_', $action);
        if (count($parts) != 3) {
            http_response_code(400);
            die("Invalid action format.");
        }

        $item_type = $parts[1];
        $item_id = (int)$parts[2];

        $table_map = [
            'collateral' => 'application_collaterals',
            'repayment' => 'application_repayment_sources',
            'document' => 'application_documents'
        ];

        if (!array_key_exists($item_type, $table_map)) {
            error_log("Invalid item_type attempted: " . $item_type);
            http_response_code(400);
            die("Invalid item type.");
        }

        $table_name = $table_map[$item_type];

        // For documents, unlink the file first with path traversal protection
        if ($item_type === 'document') {
            $sql_get_file = "SELECT file_path FROM application_documents WHERE id = ? AND application_id = ?";
            if($stmt_get_file = mysqli_prepare($link, $sql_get_file)) {
                mysqli_stmt_bind_param($stmt_get_file, "ii", $item_id, $application_id);
                mysqli_stmt_execute($stmt_get_file);
                $result = mysqli_stmt_get_result($stmt_get_file);
                if($doc = mysqli_fetch_assoc($result)) {
                    // Security: Use basename to prevent path traversal
                    $safe_filename = basename($doc['file_path']);
                    $full_path = realpath('uploads/' . $safe_filename);
                    $upload_dir = realpath('uploads/');

                    // Verify file is within uploads directory
                    if ($full_path && $upload_dir && strpos($full_path, $upload_dir) === 0) {
                        if (file_exists($full_path)) {
                            unlink($full_path);
                        }
                    } else {
                        error_log("Path traversal attempt detected: " . $doc['file_path']);
                    }
                }
                mysqli_stmt_close($stmt_get_file);
            }
        }

        // Delete from database
        $sql = "DELETE FROM $table_name WHERE id = ? AND application_id = ?";
        if($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $item_id, $application_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header("location: application_detail.php?id=" . $application_id);
        exit;
    }

    if ($action === 'add_collateral' || $action === 'add_repayment') {
        // Logic for adding collateral or repayment
        // ... (Implementation omitted for brevity, but would insert into respective tables)
        header("location: application_detail.php?id=" . $application_id);
        exit;
    }

    // --- FILE UPLOAD ACTION - SECURE VERSION ---
    if ($action == 'upload_document') {
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
            $document_name = trim($_POST['document_name']);

            // Validate document name length
            if (empty($document_name)) {
                header("location: application_detail.php?id=" . $application_id . "&error=doc_name_required");
                exit;
            }
            if (strlen($document_name) > 255) {
                header("location: application_detail.php?id=" . $application_id . "&error=doc_name_too_long");
                exit;
            }

            // 1. Check file size (max 10MB)
            $max_size = 10 * 1024 * 1024; // 10MB
            if ($_FILES['document_file']['size'] > $max_size) {
                header("location: application_detail.php?id=" . $application_id . "&error=file_too_large");
                exit;
            }

            // 2. Whitelist MIME types
            $allowed_mime_types = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword', // .doc
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
                'application/vnd.ms-excel', // .xls
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' // .xlsx
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['document_file']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_mime_types)) {
                error_log("Invalid file type upload attempt: " . $mime_type);
                header("location: application_detail.php?id=" . $application_id . "&error=invalid_file_type");
                exit;
            }

            // 3. Check file extension
            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
            $original_filename = $_FILES['document_file']['name'];
            $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

            if (!in_array($file_extension, $allowed_extensions)) {
                error_log("Invalid file extension upload attempt: " . $file_extension);
                header("location: application_detail.php?id=" . $application_id . "&error=invalid_extension");
                exit;
            }

            // 4. Generate random filename (do not use original filename)
            $new_filename = bin2hex(random_bytes(16)) . '.' . $file_extension;
            $upload_dir = 'uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $target_file = $upload_dir . $new_filename;

            // 5. Move uploaded file
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target_file)) {
                // 6. Insert into database with additional security metadata
                $file_size = $_FILES['document_file']['size'];
                $sql = "INSERT INTO application_documents (application_id, document_name, file_path, uploaded_by_id)
                        VALUES (?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "issi", $application_id, $document_name, $new_filename, $user_id);
                    if (mysqli_stmt_execute($stmt)) {
                        error_log("File uploaded successfully: app_id={$application_id}, file={$new_filename}, user={$user_id}");
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                error_log("File upload failed: app_id={$application_id}");
                header("location: application_detail.php?id=" . $application_id . "&error=upload_failed");
                exit;
            }
        } else {
            // Handle upload errors
            $error_message = "Upload error code: " . ($_FILES['document_file']['error'] ?? 'unknown');
            error_log($error_message);
            header("location: application_detail.php?id=" . $application_id . "&error=upload_error");
            exit;
        }

        header("location: application_detail.php?id=" . $application_id);
        exit;
    }

    // --- WORKFLOW ACTIONS ---
    switch ($action) {
        case 'send_for_review':
            if ($user_role == 'CVQHKH') {
                $reviewer = get_user_by_role($link, 'CVTĐ');
                if ($reviewer) {
                    update_application_status($link, $application_id, 'Chờ thẩm định', $reviewer['id']);
                    add_history($link, $application_id, $user_id, 'Gửi đi', $comment ?: 'Trình hồ sơ thẩm định.');
                }
            }
            break;

        case 'return_for_info':
            if ($user_role == 'CVTĐ') {
                update_application_status($link, $application_id, 'Yêu cầu bổ sung', $app['created_by_id']);
                add_history($link, $application_id, $user_id, 'Yêu cầu bổ sung', $comment ?: 'Vui lòng bổ sung thông tin theo yêu cầu.');
            }
            break;

        case 'submit_for_approval':
            if ($user_role == 'CVTĐ') {
                $cpd_user = get_user_by_role($link, 'CPD');
                $gdk_user = get_user_by_role($link, 'GDK');

                if ($cpd_user && $app['amount'] > $cpd_user['approval_limit'] && $gdk_user) {
                    update_application_status($link, $application_id, 'Chờ phê duyệt cấp cao', $gdk_user['id']);
                    add_history($link, $application_id, $user_id, 'Trình duyệt cấp cao', $comment ?: 'Vượt hạn mức CPD, trình GĐK.');
                } elseif ($cpd_user) {
                    update_application_status($link, $application_id, 'Chờ phê duyệt', $cpd_user['id']);
                    add_history($link, $application_id, $user_id, 'Trình duyệt', $comment ?: 'Đã thẩm định, đề nghị phê duyệt.');
                }
            }
            break;

        case 'approve':
            if ($user_role == 'CPD' || $user_role == 'GDK') {
                update_application_status($link, $application_id, 'Đã phê duyệt', null, 'Đã phê duyệt');
                add_history($link, $application_id, $user_id, 'Phê duyệt', $comment ?: 'Đồng ý cấp tín dụng.');
            }
            break;

        case 'reject':
            if ($user_role == 'CPD' || $user_role == 'GDK') {
                if (empty($comment)) {
                    header("location: application_detail.php?id=" . $application_id . "&error=reject_reason_required");
                    exit;
                }
                // Validate comment length
                if (strlen($comment) > 1000) {
                    header("location: application_detail.php?id=" . $application_id . "&error=comment_too_long");
                    exit;
                }
                update_application_status($link, $application_id, 'Đã từ chối', null, 'Đã từ chối');
                add_history($link, $application_id, $user_id, 'Từ chối', $comment);
            }
            break;

        default:
            error_log("Unknown action attempted: " . $action);
            http_response_code(400);
            die("Invalid action.");
    }

    header("location: index.php");
    exit;
} else {
    // Only POST requests allowed
    http_response_code(405);
    die("Method not allowed.");
}
?>
