<?php
/**
 * Facility Functions - Quản lý hạn mức tín dụng
 *
 * Handles:
 * - Create/Update facilities
 * - Check available amount
 * - Activate collateral → facility
 * - Track disbursed amounts
 *
 * @author Claude AI
 * @version 3.0
 * @date 2024-10-30
 */

// Prevent direct access
if (!defined('FACILITY_FUNCTIONS_LOADED')) {
    define('FACILITY_FUNCTIONS_LOADED', true);
}

/**
 * Get all facilities for an application
 */
function get_facilities_by_application($link, $application_id) {
    $sql = "SELECT f.*, p.name as product_name
            FROM facilities f
            LEFT JOIN products p ON f.product_id = p.id
            WHERE f.application_id = ?
            ORDER BY f.created_at DESC";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    return [];
}

/**
 * Get facility by ID
 */
function get_facility_by_id($link, $facility_id) {
    $sql = "SELECT f.*, p.name as product_name, a.hstd_code
            FROM facilities f
            LEFT JOIN products p ON f.product_id = p.id
            LEFT JOIN credit_applications a ON f.application_id = a.id
            WHERE f.id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $facility_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    return null;
}

/**
 * Get facility by code
 */
function get_facility_by_code($link, $facility_code) {
    $sql = "SELECT f.*, a.hstd_code
            FROM facilities f
            LEFT JOIN credit_applications a ON f.application_id = a.id
            WHERE f.facility_code = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $facility_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    return null;
}

/**
 * Create new facility
 *
 * @param array $data Facility data
 * @return array ['success' => bool, 'message' => string, 'facility_id' => int]
 */
function create_facility($link, $data) {
    // Validate required fields
    $required = ['application_id', 'facility_type', 'product_id', 'amount'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return ['success' => false, 'message' => "Thiếu field: {$field}"];
        }
    }

    // Check application exists and is approved
    $app_sql = "SELECT status FROM credit_applications WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $app_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $data['application_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $app = mysqli_fetch_assoc($result);

        if (!$app) {
            return ['success' => false, 'message' => 'Hồ sơ không tồn tại'];
        }

        // Generate facility code
        $facility_code = generate_facility_code($link, $data['application_id']);

        // Insert facility
        $sql = "INSERT INTO facilities
                (application_id, facility_code, facility_type, product_id, amount,
                 currency, status, collateral_required, created_by_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            $currency = $data['currency'] ?? 'VND';
            $status = 'Pending';
            $collateral_required = $data['collateral_required'] ?? false;
            $created_by_id = $_SESSION['id'] ?? 1;

            mysqli_stmt_bind_param($stmt, "issidssii",
                $data['application_id'],
                $facility_code,
                $data['facility_type'],
                $data['product_id'],
                $data['amount'],
                $currency,
                $status,
                $collateral_required,
                $created_by_id
            );

            if (mysqli_stmt_execute($stmt)) {
                $facility_id = mysqli_insert_id($link);

                // Log activity
                log_facility_activity($link, $facility_id, $created_by_id, 'Tạo hạn mức mới');

                return [
                    'success' => true,
                    'message' => 'Tạo hạn mức thành công',
                    'facility_id' => $facility_id,
                    'facility_code' => $facility_code
                ];
            } else {
                return ['success' => false, 'message' => 'Lỗi tạo hạn mức: ' . mysqli_error($link)];
            }
        }
    }

    return ['success' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Generate unique facility code
 */
function generate_facility_code($link, $application_id) {
    // Get application code
    $app_sql = "SELECT hstd_code FROM credit_applications WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $app_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $app = mysqli_fetch_assoc($result);

        if ($app) {
            // Count existing facilities for this application
            $count_sql = "SELECT COUNT(*) as total FROM facilities WHERE application_id = ?";
            if ($count_stmt = mysqli_prepare($link, $count_sql)) {
                mysqli_stmt_bind_param($count_stmt, "i", $application_id);
                mysqli_stmt_execute($count_stmt);
                $count_result = mysqli_stmt_get_result($count_stmt);
                $count = mysqli_fetch_assoc($count_result);

                $seq = str_pad($count['total'] + 1, 2, '0', STR_PAD_LEFT);
                return "FAC-" . date('Y') . "-" . $application_id . "-" . $seq;
            }
        }
    }

    return "FAC-" . date('Y') . "-" . uniqid();
}

/**
 * Update facility
 */
function update_facility($link, $facility_id, $data) {
    $allowed_fields = ['facility_type', 'amount', 'start_date', 'end_date',
                       'interest_rate', 'status'];

    $updates = [];
    $params = [];
    $types = '';

    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
            $types .= is_numeric($data[$field]) ? 'd' : 's';
        }
    }

    if (empty($updates)) {
        return ['success' => false, 'message' => 'Không có dữ liệu để cập nhật'];
    }

    $sql = "UPDATE facilities SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = $facility_id;
    $types .= 'i';

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            $user_id = $_SESSION['id'] ?? 1;
            log_facility_activity($link, $facility_id, $user_id, 'Cập nhật thông tin hạn mức');

            return ['success' => true, 'message' => 'Cập nhật thành công'];
        } else {
            return ['success' => false, 'message' => 'Lỗi cập nhật: ' . mysqli_error($link)];
        }
    }

    return ['success' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Activate facility (after collateral is in warehouse)
 *
 * @return array ['success' => bool, 'message' => string]
 */
function activate_facility($link, $facility_id, $user_id) {
    // Get facility
    $facility = get_facility_by_id($link, $facility_id);

    if (!$facility) {
        return ['success' => false, 'message' => 'Hạn mức không tồn tại'];
    }

    // Check if collateral is required
    if ($facility['collateral_required']) {
        // Check if collateral is in warehouse and activated
        $coll_sql = "SELECT COUNT(*) as total
                     FROM application_collaterals
                     WHERE application_id = ?
                       AND warehouse_status = 'In Warehouse'
                       AND facility_activated = 1";

        if ($stmt = mysqli_prepare($link, $coll_sql)) {
            mysqli_stmt_bind_param($stmt, "i", $facility['application_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $coll = mysqli_fetch_assoc($result);

            if ($coll['total'] == 0) {
                return [
                    'success' => false,
                    'message' => 'TSBĐ chưa nhập kho hoặc chưa được kích hoạt. Vui lòng hoàn tất trước.'
                ];
            }
        }
    }

    // Activate facility
    $sql = "UPDATE facilities
            SET status = 'Active',
                collateral_activated = 1,
                activation_date = CURDATE(),
                approved_by_id = ?,
                start_date = CURDATE()
            WHERE id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $facility_id);

        if (mysqli_stmt_execute($stmt)) {
            log_facility_activity($link, $facility_id, $user_id, 'Kích hoạt hạn mức');

            return ['success' => true, 'message' => 'Kích hoạt hạn mức thành công'];
        } else {
            return ['success' => false, 'message' => 'Lỗi kích hoạt: ' . mysqli_error($link)];
        }
    }

    return ['success' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Check if facility has enough available amount for disbursement
 *
 * @return array ['available' => bool, 'message' => string, 'available_amount' => float]
 */
function check_facility_availability($link, $facility_id, $requested_amount) {
    $facility = get_facility_by_id($link, $facility_id);

    if (!$facility) {
        return ['available' => false, 'message' => 'Hạn mức không tồn tại', 'available_amount' => 0];
    }

    if ($facility['status'] !== 'Active') {
        return [
            'available' => false,
            'message' => 'Hạn mức chưa được kích hoạt (status: ' . $facility['status'] . ')',
            'available_amount' => $facility['available_amount']
        ];
    }

    if ($facility['available_amount'] < $requested_amount) {
        return [
            'available' => false,
            'message' => sprintf(
                'Số tiền yêu cầu (%s) vượt quá số dư khả dụng (%s)',
                number_format($requested_amount),
                number_format($facility['available_amount'])
            ),
            'available_amount' => $facility['available_amount']
        ];
    }

    return [
        'available' => true,
        'message' => 'Hạn mức đủ điều kiện giải ngân',
        'available_amount' => $facility['available_amount']
    ];
}

/**
 * Update disbursed amount when disbursement is completed
 *
 * @return array ['success' => bool, 'message' => string]
 */
function update_disbursed_amount($link, $facility_id, $amount) {
    mysqli_begin_transaction($link);

    try {
        // Check availability first
        $check = check_facility_availability($link, $facility_id, $amount);

        if (!$check['available']) {
            mysqli_rollback($link);
            return ['success' => false, 'message' => $check['message']];
        }

        // Update disbursed_amount
        $sql = "UPDATE facilities
                SET disbursed_amount = disbursed_amount + ?
                WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "di", $amount, $facility_id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_commit($link);

                $user_id = $_SESSION['id'] ?? 1;
                log_facility_activity($link, $facility_id, $user_id,
                    "Giải ngân " . number_format($amount) . " VND");

                return [
                    'success' => true,
                    'message' => 'Cập nhật số dư thành công',
                    'new_available' => $check['available_amount'] - $amount
                ];
            }
        }

        mysqli_rollback($link);
        return ['success' => false, 'message' => 'Lỗi cập nhật số dư'];

    } catch (Exception $e) {
        mysqli_rollback($link);
        error_log("Update disbursed amount error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
    }
}

/**
 * Get facility utilization percentage
 */
function get_facility_utilization($link, $facility_id) {
    $facility = get_facility_by_id($link, $facility_id);

    if (!$facility || $facility['amount'] == 0) {
        return 0;
    }

    return ($facility['disbursed_amount'] / $facility['amount']) * 100;
}

/**
 * Close facility
 */
function close_facility($link, $facility_id, $user_id, $reason = '') {
    // Check if there's outstanding disbursed amount
    $facility = get_facility_by_id($link, $facility_id);

    if (!$facility) {
        return ['success' => false, 'message' => 'Hạn mức không tồn tại'];
    }

    if ($facility['disbursed_amount'] > 0) {
        return [
            'success' => false,
            'message' => 'Không thể đóng hạn mức còn dư nợ giải ngân'
        ];
    }

    $sql = "UPDATE facilities SET status = 'Closed' WHERE id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $facility_id);

        if (mysqli_stmt_execute($stmt)) {
            log_facility_activity($link, $facility_id, $user_id, "Đóng hạn mức. Lý do: {$reason}");

            return ['success' => true, 'message' => 'Đóng hạn mức thành công'];
        }
    }

    return ['success' => false, 'message' => 'Lỗi đóng hạn mức'];
}

/**
 * Log facility activity (for audit trail)
 */
function log_facility_activity($link, $facility_id, $user_id, $action) {
    // This would insert into a facility_history table (not created yet)
    // For now, just log to PHP error log
    error_log("Facility #{$facility_id} - User #{$user_id}: {$action}");

    // Could also insert into application_history
    $app_sql = "SELECT application_id FROM facilities WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $app_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $facility_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $facility = mysqli_fetch_assoc($result);

        if ($facility) {
            $hist_sql = "INSERT INTO application_history
                        (application_id, user_id, action, comment, timestamp)
                        VALUES (?, ?, 'Facility', ?, NOW())";

            if ($hist_stmt = mysqli_prepare($link, $hist_sql)) {
                mysqli_stmt_bind_param($hist_stmt, "iis",
                    $facility['application_id'],
                    $user_id,
                    $action
                );
                mysqli_stmt_execute($hist_stmt);
            }
        }
    }
}

?>
