<?php
// File: process_action.php
session_start();
require_once "config/db.php";
require_once "includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    die("Access Denied.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $application_id = $_POST['application_id'];
    $action = $_POST['action'] ?? null;
    $comment = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['id'];
    $user_role = $_SESSION['role'];

    $app = get_application_details($link, $application_id);

    // --- DATA MANAGEMENT ACTIONS (add/delete collateral etc.) ---
    if (strpos($action, 'delete_') === 0) {
        $parts = explode('_', $action);
        $item_type = $parts[1];
        $item_id = $parts[2];
        
        $table_map = [
            'collateral' => 'application_collaterals',
            'repayment' => 'application_repayment_sources',
            'document' => 'application_documents'
        ];
        
        if (array_key_exists($item_type, $table_map)) {
            $table_name = $table_map[$item_type];
            // For documents, unlink the file first
            if ($item_type === 'document') {
                $sql_get_file = "SELECT file_path FROM application_documents WHERE id = ?";
                if($stmt_get_file = mysqli_prepare($link, $sql_get_file)) {
                    mysqli_stmt_bind_param($stmt_get_file, "i", $item_id);
                    mysqli_stmt_execute($stmt_get_file);
                    $result = mysqli_stmt_get_result($stmt_get_file);
                    if($doc = mysqli_fetch_assoc($result)) {
                        if (file_exists('uploads/' . $doc['file_path'])) {
                            unlink('uploads/' . $doc['file_path']);
                        }
                    }
                }
            }
            
            $sql = "DELETE FROM $table_name WHERE id = ? AND application_id = ?";
            if($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $item_id, $application_id);
                mysqli_stmt_execute($stmt);
            }
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


    // --- FILE UPLOAD ACTION ---
    if ($action == 'upload_document') {
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
            $document_name = trim($_POST['document_name']);
            if (empty($document_name)) {
                 header("location: application_detail.php?id=" . $application_id . "&error=doc_name_required");
                 exit;
            }

            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_name = time() . '_' . basename(preg_replace("/[^a-zA-Z0-9.\-_]/", "_", $_FILES['document_file']['name']));
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target_file)) {
                $sql = "INSERT INTO application_documents (application_id, document_name, file_path, uploaded_by_id) VALUES (?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "issi", $application_id, $document_name, $file_name, $user_id);
                    mysqli_stmt_execute($stmt);
                }
            } else {
                 header("location: application_detail.php?id=" . $application_id . "&error=upload_failed");
                 exit;
            }
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
                
                if ($app['amount'] > $cpd_user['approval_limit'] && $gdk_user) {
                    update_application_status($link, $application_id, 'Chờ phê duyệt cấp cao', $gdk_user['id']);
                    add_history($link, $application_id, $user_id, 'Trình duyệt cấp cao', $comment ?: 'Vượt hạn mức CPD, trình GĐK.');
                } else {
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
                update_application_status($link, $application_id, 'Đã từ chối', null, 'Đã từ chối');
                add_history($link, $application_id, $user_id, 'Từ chối', $comment);
            }
            break;
    }
    
    header("location: index.php");
    exit;
}
?>

