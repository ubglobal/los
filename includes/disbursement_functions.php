<?php
/**
 * Disbursement Functions - Quản lý quy trình giải ngân
 *
 * Handles:
 * - Create disbursement requests
 * - Check preconditions (legal_completed, facility_activated)
 * - Workflow: Init → Check Conditions → Approve → Disbursed
 * - Update facility disbursed_amount
 *
 * @author Claude AI
 * @version 3.0
 * @date 2024-10-30
 */

// Prevent direct access
if (!defined('DISBURSEMENT_FUNCTIONS_LOADED')) {
    define('DISBURSEMENT_FUNCTIONS_LOADED', true);
}

require_once __DIR__ . '/facility_functions.php';

/**
 * Create new disbursement request
 *
 * Preconditions:
 * - Application must have legal_completed = TRUE
 * - Application must have effective_date set
 * - Facility must be Active
 * - If collateral required, must be in warehouse and activated
 *
 * @param array $data Disbursement data
 * @return array ['success' => bool, 'message' => string, 'disbursement_id' => int]
 */
function create_disbursement($link, $data) {
    // Validate required fields
    $required = ['application_id', 'facility_id', 'amount', 'purpose', 'beneficiary_name'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return ['success' => false, 'message' => "Thiếu field: {$field}"];
        }
    }

    // Check preconditions
    $precond_check = check_disbursement_preconditions($link, $data['application_id'], $data['facility_id']);

    if (!$precond_check['allowed']) {
        return ['success' => false, 'message' => $precond_check['message']];
    }

    // Check facility availability
    $facility_check = check_facility_availability($link, $data['facility_id'], $data['amount']);

    if (!$facility_check['available']) {
        return ['success' => false, 'message' => $facility_check['message']];
    }

    // Generate disbursement code
    $disbursement_code = generate_disbursement_code($link, $data['application_id']);

    // Insert disbursement
    $sql = "INSERT INTO disbursements
            (disbursement_code, application_id, facility_id, disbursement_type, amount,
             currency, purpose, beneficiary_type, beneficiary_name, beneficiary_account,
             beneficiary_bank, status, stage, assigned_to_id, created_by_id, requested_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft', 'Khởi tạo', ?, ?, CURDATE())";

    if ($stmt = mysqli_prepare($link, $sql)) {
        $disbursement_type = $data['disbursement_type'] ?? 'Lần đầu';
        $currency = $data['currency'] ?? 'VND';
        $beneficiary_type = $data['beneficiary_type'] ?? 'Chính chủ';
        $beneficiary_account = $data['beneficiary_account'] ?? null;
        $beneficiary_bank = $data['beneficiary_bank'] ?? null;
        $assigned_to_id = null; // Will be set based on workflow
        $created_by_id = $_SESSION['id'] ?? 1;

        mysqli_stmt_bind_param($stmt, "siiidsssssiii",
            $disbursement_code,
            $data['application_id'],
            $data['facility_id'],
            $disbursement_type,
            $data['amount'],
            $currency,
            $data['purpose'],
            $beneficiary_type,
            $data['beneficiary_name'],
            $beneficiary_account,
            $beneficiary_bank,
            $assigned_to_id,
            $created_by_id
        );

        if (mysqli_stmt_execute($stmt)) {
            $disbursement_id = mysqli_insert_id($link);

            // Create default disbursement conditions
            create_default_disbursement_conditions($link, $disbursement_id, $data['application_id']);

            // Log in history
            log_disbursement_history($link, $disbursement_id, $created_by_id, 'Khởi tạo',
                'Khởi tạo', 'Khởi tạo', 'Tạo hồ sơ giải ngân mới');

            return [
                'success' => true,
                'message' => 'Tạo hồ sơ giải ngân thành công',
                'disbursement_id' => $disbursement_id,
                'disbursement_code' => $disbursement_code
            ];
        } else {
            return ['success' => false, 'message' => 'Lỗi tạo giải ngân: ' . mysqli_error($link)];
        }
    }

    return ['success' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Check disbursement preconditions
 *
 * @return array ['allowed' => bool, 'message' => string, 'checks' => array]
 */
function check_disbursement_preconditions($link, $application_id, $facility_id) {
    $checks = [];
    $all_passed = true;

    // Check 1: Application must be "Đã có hiệu lực"
    $app_sql = "SELECT legal_completed, effective_date, status FROM credit_applications WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $app_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $app = mysqli_fetch_assoc($result);

        if (!$app) {
            return ['allowed' => false, 'message' => 'Hồ sơ không tồn tại', 'checks' => []];
        }

        // Check legal completion
        if (!$app['legal_completed']) {
            $checks[] = '❌ Hồ sơ chưa hoàn tất thủ tục pháp lý';
            $all_passed = false;
        } else {
            $checks[] = '✓ Hồ sơ đã hoàn tất thủ tục pháp lý';
        }

        // Check effective date
        if (!$app['effective_date']) {
            $checks[] = '❌ Hồ sơ chưa có ngày hiệu lực';
            $all_passed = false;
        } else {
            $checks[] = '✓ Hồ sơ đã có hiệu lực: ' . $app['effective_date'];
        }

        // Check status
        if ($app['status'] !== 'Đã phê duyệt') {
            $checks[] = '❌ Hồ sơ chưa được phê duyệt (status: ' . $app['status'] . ')';
            $all_passed = false;
        } else {
            $checks[] = '✓ Hồ sơ đã được phê duyệt';
        }
    }

    // Check 2: Facility must be Active
    $facility = get_facility_by_id($link, $facility_id);

    if (!$facility) {
        $checks[] = '❌ Hạn mức không tồn tại';
        $all_passed = false;
    } else {
        if ($facility['status'] !== 'Active') {
            $checks[] = '❌ Hạn mức chưa kích hoạt (status: ' . $facility['status'] . ')';
            $all_passed = false;
        } else {
            $checks[] = '✓ Hạn mức đã kích hoạt';
        }

        // Check 3: If collateral required, check warehouse status
        if ($facility['collateral_required']) {
            $coll_sql = "SELECT COUNT(*) as total
                        FROM application_collaterals
                        WHERE application_id = ?
                          AND warehouse_status = 'In Warehouse'
                          AND facility_activated = 1";

            if ($stmt = mysqli_prepare($link, $coll_sql)) {
                mysqli_stmt_bind_param($stmt, "i", $application_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $coll = mysqli_fetch_assoc($result);

                if ($coll['total'] == 0) {
                    $checks[] = '❌ TSBĐ chưa nhập kho hoặc chưa kích hoạt';
                    $all_passed = false;
                } else {
                    $checks[] = '✓ TSBĐ đã nhập kho và kích hoạt (' . $coll['total'] . ' TSBĐ)';
                }
            }
        } else {
            $checks[] = '✓ Không yêu cầu TSBĐ';
        }
    }

    $message = $all_passed ?
        'Tất cả điều kiện giải ngân đã đáp ứng' :
        'Một số điều kiện giải ngân chưa đáp ứng. Vui lòng hoàn tất trước khi giải ngân.';

    return [
        'allowed' => $all_passed,
        'message' => $message,
        'checks' => $checks
    ];
}

/**
 * Generate unique disbursement code
 * FIX BUG-017: Use sequence table for guaranteed uniqueness
 */
function generate_disbursement_code($link, $application_id) {
    $current_year = date("Y");

    // Insert into sequence table to get unique ID
    $seq_sql = "INSERT INTO disbursement_code_sequence (year) VALUES (?)";
    if ($seq_stmt = mysqli_prepare($link, $seq_sql)) {
        mysqli_stmt_bind_param($seq_stmt, "i", $current_year);
        if (mysqli_stmt_execute($seq_stmt)) {
            $sequence_id = mysqli_insert_id($link);
            mysqli_stmt_close($seq_stmt);

            // Format: DISB.YEAR.XXXXXX (6-digit padded sequence)
            return "DISB." . $current_year . "." . str_pad($sequence_id, 6, '0', STR_PAD_LEFT);
        }
        mysqli_stmt_close($seq_stmt);
    }

    // Fallback (should never happen if database is working)
    error_log("Failed to generate disbursement code via sequence table");
    return "DISB." . $current_year . "." . uniqid();
}

/**
 * Create default disbursement conditions based on application
 */
function create_default_disbursement_conditions($link, $disbursement_id, $application_id) {
    // Get facility linked to this disbursement
    $disb_sql = "SELECT facility_id FROM disbursements WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $disb_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $disbursement_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $disb = mysqli_fetch_assoc($result);

        if ($disb) {
            $facility = get_facility_by_id($link, $disb['facility_id']);

            // Default conditions
            $conditions = [
                ['text' => 'Hợp đồng tín dụng đã ký kết và có hiệu lực', 'type' => 'Legal', 'mandatory' => true],
                ['text' => 'Khách hàng đã mở tài khoản thanh toán tại Ngân hàng', 'type' => 'Other', 'mandatory' => true]
            ];

            // If collateral required
            if ($facility && $facility['collateral_required']) {
                $conditions[] = [
                    'text' => 'TSBĐ đã được đăng ký thế chấp tại cơ quan có thẩm quyền',
                    'type' => 'Collateral',
                    'mandatory' => true
                ];
                $conditions[] = [
                    'text' => 'Hợp đồng bảo hiểm TSBĐ thụ hưởng cho Ngân hàng',
                    'type' => 'Insurance',
                    'mandatory' => true
                ];
            }

            // Insert conditions
            foreach ($conditions as $cond) {
                $ins_sql = "INSERT INTO disbursement_conditions
                           (disbursement_id, condition_text, condition_type, is_mandatory)
                           VALUES (?, ?, ?, ?)";

                if ($ins_stmt = mysqli_prepare($link, $ins_sql)) {
                    mysqli_stmt_bind_param($ins_stmt, "issi",
                        $disbursement_id,
                        $cond['text'],
                        $cond['type'],
                        $cond['mandatory']
                    );
                    mysqli_stmt_execute($ins_stmt);
                }
            }
        }
    }
}

/**
 * Get disbursement by ID
 */
function get_disbursement_by_id($link, $disbursement_id) {
    $sql = "SELECT d.*, f.facility_code, a.hstd_code,
                   cu.full_name as created_by_name, ru.full_name as reviewed_by_name,
                   au.full_name as approved_by_name
            FROM disbursements d
            LEFT JOIN facilities f ON d.facility_id = f.id
            LEFT JOIN credit_applications a ON d.application_id = a.id
            LEFT JOIN users cu ON d.created_by_id = cu.id
            LEFT JOIN users ru ON d.reviewed_by_id = ru.id
            LEFT JOIN users au ON d.approved_by_id = au.id
            WHERE d.id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $disbursement_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    return null;
}

/**
 * Get disbursements by application
 */
function get_disbursements_by_application($link, $application_id) {
    $sql = "SELECT d.*, f.facility_code
            FROM disbursements d
            LEFT JOIN facilities f ON d.facility_id = f.id
            WHERE d.application_id = ?
            ORDER BY d.created_at DESC";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    return [];
}

/**
 * Get disbursement conditions
 */
function get_disbursement_conditions($link, $disbursement_id) {
    $sql = "SELECT dc.*, u.full_name as met_by_name
            FROM disbursement_conditions dc
            LEFT JOIN users u ON dc.met_by_id = u.id
            WHERE dc.disbursement_id = ?
            ORDER BY dc.is_mandatory DESC, dc.id ASC";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $disbursement_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    return [];
}

/**
 * Check if all mandatory conditions are met
 *
 * @return array ['all_met' => bool, 'met_count' => int, 'total_count' => int]
 */
function check_all_conditions_met($link, $disbursement_id) {
    $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_met = 1 THEN 1 ELSE 0 END) as met,
                SUM(CASE WHEN is_mandatory = 1 AND is_met = 0 THEN 1 ELSE 0 END) as unmet_mandatory
            FROM disbursement_conditions
            WHERE disbursement_id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $disbursement_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stats = mysqli_fetch_assoc($result);

        return [
            'all_met' => ($stats['unmet_mandatory'] == 0),
            'met_count' => $stats['met'],
            'total_count' => $stats['total'],
            'unmet_mandatory_count' => $stats['unmet_mandatory']
        ];
    }

    return ['all_met' => false, 'met_count' => 0, 'total_count' => 0];
}

/**
 * Mark condition as met
 * FIX BUG-013: Use correct column name 'verification_notes'
 */
function mark_condition_met($link, $condition_id, $user_id, $notes = '') {
    $sql = "UPDATE disbursement_conditions
            SET is_met = 1,
                met_date = CURDATE(),
                met_by_id = ?,
                verification_notes = ?
            WHERE id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "isi", $user_id, $notes, $condition_id);
        return mysqli_stmt_execute($stmt);
    }

    return false;
}

/**
 * Execute disbursement action (workflow transition)
 *
 * @param string $action Next, Approve, Reject, Return, Request Info
 * @return array ['success' => bool, 'message' => string]
 */
function execute_disbursement_action($link, $disbursement_id, $action, $user_id, $comment = '') {
    $disbursement = get_disbursement_by_id($link, $disbursement_id);

    if (!$disbursement) {
        return ['success' => false, 'message' => 'Hồ sơ giải ngân không tồn tại'];
    }

    mysqli_begin_transaction($link);

    try {
        $new_status = $disbursement['status'];
        $new_stage = $disbursement['stage'];
        $from_stage = $disbursement['stage'];
        $rejection_reason = null;

        switch ($action) {
            case 'Next':
                // Move to next stage
                if ($disbursement['stage'] == 'Khởi tạo') {
                    $new_stage = 'Kiểm tra điều kiện';
                    $new_status = 'Pending';
                } elseif ($disbursement['stage'] == 'Kiểm tra điều kiện') {
                    // Check if all conditions met
                    $cond_check = check_all_conditions_met($link, $disbursement_id);

                    if (!$cond_check['all_met']) {
                        mysqli_rollback($link);
                        return [
                            'success' => false,
                            'message' => "Còn {$cond_check['unmet_mandatory_count']} điều kiện bắt buộc chưa đáp ứng"
                        ];
                    }

                    $new_stage = 'Chờ phê duyệt';
                    $new_status = 'In Review';
                }
                break;

            case 'Approve':
                // Final approval → Disbursed
                $new_status = 'Approved';
                $new_stage = 'Đã phê duyệt';

                // Update facility disbursed amount
                $update_facility = update_disbursed_amount($link, $disbursement['facility_id'], $disbursement['amount']);

                if (!$update_facility['success']) {
                    mysqli_rollback($link);
                    return $update_facility;
                }

                // FIX BUG-014: Set disbursement date using correct column name 'disbursed_date'
                $disburse_sql = "UPDATE disbursements
                                SET disbursed_date = CURDATE(),
                                    approved_by_id = ?
                                WHERE id = ?";
                if ($stmt = mysqli_prepare($link, $disburse_sql)) {
                    mysqli_stmt_bind_param($stmt, "ii", $user_id, $disbursement_id);
                    mysqli_stmt_execute($stmt);
                }
                break;

            case 'Reject':
                $new_status = 'Rejected';
                $new_stage = 'Đã từ chối';
                $rejection_reason = $comment;
                break;

            case 'Return':
                // Return to previous stage
                if ($disbursement['stage'] == 'Chờ phê duyệt') {
                    $new_stage = 'Kiểm tra điều kiện';
                } elseif ($disbursement['stage'] == 'Kiểm tra điều kiện') {
                    $new_stage = 'Khởi tạo';
                }
                $new_status = 'Pending';
                break;

            case 'Request Info':
                // Request more info, stay in current stage but notify previous
                $new_status = 'Pending';
                break;

            default:
                mysqli_rollback($link);
                return ['success' => false, 'message' => 'Action không hợp lệ'];
        }

        // Update disbursement
        $update_sql = "UPDATE disbursements
                      SET status = ?,
                          stage = ?,
                          rejection_reason = ?,
                          updated_at = NOW()
                      WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $update_sql)) {
            mysqli_stmt_bind_param($stmt, "sssi",
                $new_status,
                $new_stage,
                $rejection_reason,
                $disbursement_id
            );
            mysqli_stmt_execute($stmt);
        }

        // Log history
        log_disbursement_history($link, $disbursement_id, $user_id, $action,
            $from_stage, $new_stage, $comment);

        mysqli_commit($link);

        return [
            'success' => true,
            'message' => 'Thao tác thành công',
            'new_status' => $new_status,
            'new_stage' => $new_stage
        ];

    } catch (Exception $e) {
        mysqli_rollback($link);
        error_log("Disbursement action error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
    }
}

/**
 * Log disbursement history
 * FIX BUG-012: Use correct column names from database schema
 */
function log_disbursement_history($link, $disbursement_id, $user_id, $action, $from_stage, $to_stage, $comment = '') {
    $sql = "INSERT INTO disbursement_history
            (disbursement_id, performed_by_id, action, old_status, new_status, notes)
            VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "iissss",
            $disbursement_id,
            $user_id,
            $action,
            $from_stage,
            $to_stage,
            $comment
        );
        mysqli_stmt_execute($stmt);
    }
}

/**
 * Get disbursement history
 * FIX BUG-012: Use correct column names
 */
function get_disbursement_history($link, $disbursement_id) {
    $sql = "SELECT dh.*, u.full_name as user_name
            FROM disbursement_history dh
            JOIN users u ON dh.performed_by_id = u.id
            WHERE dh.disbursement_id = ?
            ORDER BY dh.created_at DESC";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $disbursement_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    return [];
}

?>
