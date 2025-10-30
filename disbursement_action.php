<?php
/**
 * File: disbursement_action.php
 * Purpose: Handle disbursement workflow actions
 * Version: 3.0
 * Author: Claude AI
 * Date: 2025-10-30
 */

require_once "config/session.php";
init_secure_session();
require_once "config/db.php";
require_once "config/csrf.php";
require_once "includes/functions.php";

// v3.0: Disbursement modules
require_once "includes/workflow_engine.php";
require_once "includes/facility_functions.php";
require_once "includes/disbursement_functions.php";
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

    $action = $_POST['action'] ?? null;
    $user_id = $_SESSION['id'];
    $user_role = $_SESSION['role'];

    // --- CREATE DISBURSEMENT REQUEST ---
    if ($action === 'create_disbursement') {
        $application_id = (int)($_POST['application_id'] ?? 0);
        $facility_id = (int)($_POST['facility_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $disbursement_type = $_POST['disbursement_type'] ?? 'Full';
        $beneficiary_account = trim($_POST['beneficiary_account'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Validate inputs
        if ($application_id <= 0 || $facility_id <= 0 || $amount <= 0) {
            header("location: application_detail.php?id=" . $application_id . "&error=invalid_disbursement_data");
            exit;
        }

        if (empty($beneficiary_account)) {
            header("location: application_detail.php?id=" . $application_id . "&error=beneficiary_account_required");
            exit;
        }

        // Check permission
        if (!has_permission($link, $user_id, 'disbursement.input')) {
            header("location: application_detail.php?id=" . $application_id . "&error=no_permission");
            exit;
        }

        $disbursement_data = [
            'application_id' => $application_id,
            'facility_id' => $facility_id,
            'amount' => $amount,
            'disbursement_type' => $disbursement_type,
            'beneficiary_account' => $beneficiary_account,
            'notes' => $notes
        ];

        $result = create_disbursement($link, $disbursement_data);

        if ($result['success']) {
            header("location: disbursement_detail.php?id=" . $result['disbursement_id'] . "&success=" . urlencode($result['message']));
            exit;
        } else {
            header("location: application_detail.php?id=" . $application_id . "&error=" . urlencode($result['message']));
            exit;
        }
    }

    // --- DISBURSEMENT WORKFLOW ACTIONS ---
    $disbursement_id = (int)($_POST['disbursement_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($disbursement_id <= 0) {
        http_response_code(400);
        die("Invalid disbursement ID.");
    }

    // Get disbursement details
    $disbursement = get_disbursement_by_id($link, $disbursement_id);
    if (!$disbursement) {
        http_response_code(404);
        die("Disbursement not found.");
    }

    switch ($action) {
        // --- RM: Update disbursement conditions ---
        case 'update_condition':
            $condition_id = (int)($_POST['condition_id'] ?? 0);
            $is_met = isset($_POST['is_met']) ? (bool)$_POST['is_met'] : null;
            $notes = trim($_POST['condition_notes'] ?? '');

            if ($condition_id <= 0 || $is_met === null) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=invalid_condition_data");
                exit;
            }

            $sql = "UPDATE disbursement_conditions
                    SET is_met = ?,
                        met_date = " . ($is_met ? "CURDATE()" : "NULL") . ",
                        verified_by_id = ?,
                        notes = ?
                    WHERE id = ? AND disbursement_id = ?";

            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "iisii", $is_met, $user_id, $notes, $condition_id, $disbursement_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Log in disbursement history
                $history_sql = "INSERT INTO disbursement_history
                               (disbursement_id, user_id, action, comment, timestamp)
                               VALUES (?, ?, 'Update Condition', ?, NOW())";
                if ($hist_stmt = mysqli_prepare($link, $history_sql)) {
                    $hist_comment = "Cập nhật điều kiện: " . ($is_met ? "Đã đáp ứng" : "Chưa đáp ứng");
                    mysqli_stmt_bind_param($hist_stmt, "iis", $disbursement_id, $user_id, $hist_comment);
                    mysqli_stmt_execute($hist_stmt);
                }
            }

            header("location: disbursement_detail.php?id=" . $disbursement_id . "&success=condition_updated");
            exit;

        // --- RM: Submit for disbursement approval ---
        case 'submit_disbursement':
            $result = execute_disbursement_action($link, $disbursement_id, 'Submit', $user_id, $comment);

            if ($result['success']) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&success=" . urlencode($result['message']));
            } else {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=" . urlencode($result['message']));
            }
            exit;

        // --- Kiểm soát: Check conditions ---
        case 'check_conditions':
            if ($user_role !== 'Kiểm soát' && $user_role !== 'Admin') {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=no_permission");
                exit;
            }

            $result = execute_disbursement_action($link, $disbursement_id, 'Check', $user_id, $comment);

            if ($result['success']) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&success=" . urlencode($result['message']));
            } else {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=" . urlencode($result['message']));
            }
            exit;

        // --- CPD/GDK: Approve disbursement ---
        case 'approve_disbursement':
            if ($user_role !== 'CPD' && $user_role !== 'GDK' && $user_role !== 'Admin') {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=no_permission");
                exit;
            }

            // Check approval limit
            $check_limit = check_approval_limit($link, $user_id, $disbursement['amount']);
            if (!$check_limit['can_approve']) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=" . urlencode($check_limit['message']));
                exit;
            }

            $result = execute_disbursement_action($link, $disbursement_id, 'Approve', $user_id, $comment ?: 'Đồng ý giải ngân.');

            if ($result['success']) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&success=" . urlencode($result['message']));
            } else {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=" . urlencode($result['message']));
            }
            exit;

        // --- Reject disbursement ---
        case 'reject_disbursement':
            if (empty($comment)) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=rejection_reason_required");
                exit;
            }

            if ($user_role !== 'CPD' && $user_role !== 'GDK' && $user_role !== 'Kiểm soát' && $user_role !== 'Admin') {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=no_permission");
                exit;
            }

            $result = execute_disbursement_action($link, $disbursement_id, 'Reject', $user_id, $comment);

            if ($result['success']) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&success=" . urlencode($result['message']));
            } else {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=" . urlencode($result['message']));
            }
            exit;

        // --- Thủ quỹ: Execute disbursement ---
        case 'execute_disbursement':
            if ($user_role !== 'Thủ quỹ' && $user_role !== 'Admin') {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=no_permission");
                exit;
            }

            $transaction_ref = trim($_POST['transaction_ref'] ?? '');
            if (empty($transaction_ref)) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=transaction_ref_required");
                exit;
            }

            // Update disbursement with transaction reference
            $sql = "UPDATE disbursements
                    SET transaction_reference = ?
                    WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $transaction_ref, $disbursement_id);
                mysqli_stmt_execute($stmt);
            }

            $result = execute_disbursement_action($link, $disbursement_id, 'Execute', $user_id,
                $comment ?: "Đã thực hiện giải ngân. Mã GD: {$transaction_ref}");

            if ($result['success']) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&success=" . urlencode($result['message']));
            } else {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=" . urlencode($result['message']));
            }
            exit;

        // --- Return to RM for revision ---
        case 'return_disbursement':
            if (empty($comment)) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=return_reason_required");
                exit;
            }

            $result = execute_disbursement_action($link, $disbursement_id, 'Return', $user_id, $comment);

            if ($result['success']) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&success=" . urlencode($result['message']));
            } else {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=" . urlencode($result['message']));
            }
            exit;

        // --- Cancel disbursement ---
        case 'cancel_disbursement':
            if ($user_role !== 'Admin' && $user_id != $disbursement['created_by_id']) {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=no_permission");
                exit;
            }

            if ($disbursement['status'] === 'Completed') {
                header("location: disbursement_detail.php?id=" . $disbursement_id . "&error=cannot_cancel_completed");
                exit;
            }

            $sql = "UPDATE disbursements SET status = 'Cancelled' WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $disbursement_id);
                mysqli_stmt_execute($stmt);
            }

            // Log history
            $history_sql = "INSERT INTO disbursement_history
                           (disbursement_id, user_id, action, comment, timestamp)
                           VALUES (?, ?, 'Cancel', ?, NOW())";
            if ($hist_stmt = mysqli_prepare($link, $history_sql)) {
                mysqli_stmt_bind_param($hist_stmt, "iis", $disbursement_id, $user_id, $comment);
                mysqli_stmt_execute($hist_stmt);
            }

            header("location: disbursement_detail.php?id=" . $disbursement_id . "&success=disbursement_cancelled");
            exit;

        default:
            error_log("Unknown disbursement action attempted: " . $action);
            http_response_code(400);
            die("Invalid action.");
    }

} else {
    // Only POST requests allowed
    http_response_code(405);
    die("Method not allowed.");
}
?>
