<?php
// File: process_action.php - SECURE VERSION v3.0
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

    // --- ADD COLLATERAL ACTION ---
    if ($action === 'add_collateral') {
        $collateral_type_id = (int)($_POST['collateral_type_id'] ?? 0);
        $description = trim($_POST['collateral_description'] ?? '');
        $value = $_POST['collateral_value'] ?? '';

        // Validation
        if ($collateral_type_id <= 0) {
            header("location: application_detail.php?id=" . $application_id . "&error=collateral_type_required");
            exit;
        }

        if (empty($description)) {
            header("location: application_detail.php?id=" . $application_id . "&error=collateral_description_required");
            exit;
        }

        if (strlen($description) > 1000) {
            header("location: application_detail.php?id=" . $application_id . "&error=collateral_description_too_long");
            exit;
        }

        if (empty($value) || !is_numeric($value) || $value <= 0) {
            header("location: application_detail.php?id=" . $application_id . "&error=collateral_value_invalid");
            exit;
        }

        // Verify collateral_type_id exists
        $check_sql = "SELECT id FROM collateral_types WHERE id = ?";
        if ($check_stmt = mysqli_prepare($link, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, "i", $collateral_type_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            if (mysqli_num_rows($check_result) == 0) {
                mysqli_stmt_close($check_stmt);
                header("location: application_detail.php?id=" . $application_id . "&error=invalid_collateral_type");
                exit;
            }
            mysqli_stmt_close($check_stmt);
        }

        // Insert into database
        $sql = "INSERT INTO application_collaterals
                (application_id, collateral_type_id, description, estimated_value)
                VALUES (?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "iisd", $application_id, $collateral_type_id, $description, $value);
            if (mysqli_stmt_execute($stmt)) {
                error_log("Collateral added: app_id={$application_id}, type={$collateral_type_id}, value={$value}, user={$user_id}");
            } else {
                error_log("Collateral insert failed: " . mysqli_error($link));
                header("location: application_detail.php?id=" . $application_id . "&error=collateral_add_failed");
                mysqli_stmt_close($stmt);
                exit;
            }
            mysqli_stmt_close($stmt);
        }

        header("location: application_detail.php?id=" . $application_id . "&success=collateral_added");
        exit;
    }

    // --- ADD REPAYMENT SOURCE ACTION ---
    if ($action === 'add_repayment') {
        $source_type = trim($_POST['repayment_source_type'] ?? '');
        $description = trim($_POST['repayment_description'] ?? '');
        $monthly_income = $_POST['repayment_monthly_income'] ?? '';

        // Validation
        if (empty($source_type)) {
            header("location: application_detail.php?id=" . $application_id . "&error=repayment_type_required");
            exit;
        }

        if (strlen($source_type) > 50) {
            header("location: application_detail.php?id=" . $application_id . "&error=repayment_type_too_long");
            exit;
        }

        if (empty($description)) {
            header("location: application_detail.php?id=" . $application_id . "&error=repayment_description_required");
            exit;
        }

        if (strlen($description) > 1000) {
            header("location: application_detail.php?id=" . $application_id . "&error=repayment_description_too_long");
            exit;
        }

        if (empty($monthly_income) || !is_numeric($monthly_income) || $monthly_income <= 0) {
            header("location: application_detail.php?id=" . $application_id . "&error=repayment_income_invalid");
            exit;
        }

        // Insert into database
        $sql = "INSERT INTO application_repayment_sources
                (application_id, source_type, source_description, estimated_monthly_amount, verification_status)
                VALUES (?, ?, ?, ?, 'Chưa xác minh')";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "issd", $application_id, $source_type, $description, $monthly_income);
            if (mysqli_stmt_execute($stmt)) {
                error_log("Repayment source added: app_id={$application_id}, type={$source_type}, amount={$monthly_income}, user={$user_id}");
            } else {
                error_log("Repayment insert failed: " . mysqli_error($link));
                header("location: application_detail.php?id=" . $application_id . "&error=repayment_add_failed");
                mysqli_stmt_close($stmt);
                exit;
            }
            mysqli_stmt_close($stmt);
        }

        header("location: application_detail.php?id=" . $application_id . "&success=repayment_added");
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

    // --- WORKFLOW ACTIONS (v3.0 Enhanced) ---

    // Check if this is a workflow action (use workflow_engine)
    $workflow_actions = ['Next', 'Approve', 'Reject', 'Return', 'Request Info'];

    if (in_array($action, $workflow_actions)) {
        // Use workflow engine for standard workflow transitions
        $result = execute_transition($link, $application_id, $action, $user_id, $comment);

        if (!$result['success']) {
            header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
            exit;
        }

        header("location: application_detail.php?id=" . $application_id . "&success=" . urlencode($result['message']));
        exit;
    }

    // Additional specialized actions
    switch ($action) {
        // Legacy support for old action names (backward compatibility)
        case 'send_for_review':
            $result = execute_transition($link, $application_id, 'Next', $user_id, $comment ?: 'Trình hồ sơ thẩm định.');
            if (!$result['success']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
                exit;
            }
            break;

        case 'return_for_info':
            $result = execute_transition($link, $application_id, 'Request Info', $user_id, $comment ?: 'Vui lòng bổ sung thông tin theo yêu cầu.');
            if (!$result['success']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
                exit;
            }
            break;

        case 'submit_for_approval':
            $result = execute_transition($link, $application_id, 'Next', $user_id, $comment ?: 'Đã thẩm định, đề nghị phê duyệt.');
            if (!$result['success']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
                exit;
            }
            break;

        case 'approve':
            // v3.0: Check approval limit before approving
            $check_limit = check_approval_limit($link, $user_id, $app['amount']);
            if (!$check_limit['can_approve']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($check_limit['message']));
                exit;
            }

            $result = execute_transition($link, $application_id, 'Approve', $user_id, $comment ?: 'Đồng ý cấp tín dụng.');
            if (!$result['success']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
                exit;
            }
            break;

        case 'reject':
            if (empty($comment)) {
                header("location: application_detail.php?id=" . $application_id . "&error=reject_reason_required");
                exit;
            }
            if (strlen($comment) > 1000) {
                header("location: application_detail.php?id=" . $application_id . "&error=comment_too_long");
                exit;
            }

            $result = execute_transition($link, $application_id, 'Reject', $user_id, $comment);
            if (!$result['success']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
                exit;
            }
            break;

        // v3.0: New escalation action
        case 'escalate':
            if (empty($comment)) {
                header("location: application_detail.php?id=" . $application_id . "&error=escalation_reason_required");
                exit;
            }

            // Get GDK user as default escalation target
            $gdk_user = get_user_by_role($link, 'GDK');
            if (!$gdk_user) {
                header("location: application_detail.php?id=" . $application_id . "&error=no_escalation_target");
                exit;
            }

            $escalation_data = [
                'application_id' => $application_id,
                'escalation_type' => 'Rejection Review',
                'reason' => $comment,
                'escalated_by_id' => $user_id,
                'escalated_to_id' => $gdk_user['id']
            ];

            $result = create_escalation($link, $escalation_data);
            if (!$result['success']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
                exit;
            }
            break;

        // v3.0: Request exception for approval condition
        case 'request_exception':
            $condition_id = (int)($_POST['condition_id'] ?? 0);
            if ($condition_id <= 0 || empty($comment)) {
                header("location: application_detail.php?id=" . $application_id . "&error=invalid_exception_request");
                exit;
            }

            $result = request_exception($link, $condition_id, $user_id, $comment);
            if (!$result['success']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
                exit;
            }
            break;

        // v3.0: Approve exception
        case 'approve_exception':
            $condition_id = (int)($_POST['condition_id'] ?? 0);
            if ($condition_id <= 0) {
                header("location: application_detail.php?id=" . $application_id . "&error=invalid_condition");
                exit;
            }

            $result = approve_exception($link, $condition_id, $user_id, $comment);
            if (!$result['success']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
                exit;
            }
            break;

        // v3.0: Reject exception
        case 'reject_exception':
            $condition_id = (int)($_POST['condition_id'] ?? 0);
            if ($condition_id <= 0 || empty($comment)) {
                header("location: application_detail.php?id=" . $application_id . "&error=rejection_reason_required");
                exit;
            }

            $result = reject_exception($link, $condition_id, $user_id, $comment);
            if (!$result['success']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
                exit;
            }
            break;

        // v3.0: Mark legal completion
        case 'mark_legal_completed':
            $effective_date = $_POST['effective_date'] ?? null;
            $legal_notes = trim($_POST['legal_notes'] ?? '');

            if (empty($effective_date)) {
                header("location: application_detail.php?id=" . $application_id . "&error=effective_date_required");
                exit;
            }

            $sql = "UPDATE credit_applications
                    SET legal_completed = 1,
                        legal_completed_date = CURDATE(),
                        effective_date = ?,
                        legal_notes = ?
                    WHERE id = ?";

            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssi", $effective_date, $legal_notes, $application_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                add_history($link, $application_id, $user_id, 'Hoàn tất pháp lý',
                    "Ngày hiệu lực: {$effective_date}. " . ($legal_notes ?: 'Đã hoàn tất thủ tục pháp lý.'));
            }
            break;

        // v3.0: Activate facility
        case 'activate_facility':
            $facility_id = (int)($_POST['facility_id'] ?? 0);
            if ($facility_id <= 0) {
                header("location: application_detail.php?id=" . $application_id . "&error=invalid_facility");
                exit;
            }

            $result = activate_facility($link, $facility_id, $user_id);
            if (!$result['success']) {
                header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
                exit;
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
