<?php
/**
 * Exception & Escalation Functions
 *
 * Handles:
 * - Request exceptions for approval conditions
 * - Approve/Reject exceptions
 * - Create escalations when rejected
 * - Resolve escalations
 *
 * @author Claude AI
 * @version 3.0
 * @date 2024-10-30
 */

// Prevent direct access
if (!defined('EXCEPTION_ESCALATION_LOADED')) {
    define('EXCEPTION_ESCALATION_LOADED', true);
}

/**
 * Request exception for an approval condition
 *
 * @param int $condition_id Approval condition ID
 * @param int $user_id User requesting exception (usually RM)
 * @param string $reason Reason for exception request
 * @return array ['success' => bool, 'message' => string]
 */
function request_exception($link, $condition_id, $user_id, $reason) {
    // Get condition
    $cond_sql = "SELECT * FROM approval_conditions WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $cond_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $condition_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $condition = mysqli_fetch_assoc($result);

        if (!$condition) {
            return ['success' => false, 'message' => 'Điều kiện không tồn tại'];
        }

        // Check if exception is allowed
        if (!$condition['allow_exception']) {
            return [
                'success' => false,
                'message' => 'Điều kiện này không cho phép xin ngoại lệ'
            ];
        }

        // Check if already requested
        if ($condition['is_exception_requested']) {
            return [
                'success' => false,
                'message' => 'Ngoại lệ đã được yêu cầu trước đó'
            ];
        }

        // Update condition with exception request
        $update_sql = "UPDATE approval_conditions
                      SET is_exception_requested = 1,
                          exception_reason = ?,
                          exception_requested_by_id = ?,
                          exception_requested_date = CURDATE()
                      WHERE id = ?";

        if ($upd_stmt = mysqli_prepare($link, $update_sql)) {
            mysqli_stmt_bind_param($upd_stmt, "sii", $reason, $user_id, $condition_id);

            if (mysqli_stmt_execute($upd_stmt)) {
                // Log in application history
                $hist_sql = "INSERT INTO application_history
                            (application_id, user_id, action, comment, timestamp)
                            VALUES (?, ?, 'Request Exception', ?, NOW())";

                if ($hist_stmt = mysqli_prepare($link, $hist_sql)) {
                    $comment = "Xin ngoại lệ: " . $condition['condition_text'] . " - Lý do: " . $reason;
                    mysqli_stmt_bind_param($hist_stmt, "iis",
                        $condition['application_id'],
                        $user_id,
                        $comment
                    );
                    mysqli_stmt_execute($hist_stmt);
                }

                return [
                    'success' => true,
                    'message' => 'Yêu cầu ngoại lệ đã được gửi. Chờ phê duyệt.'
                ];
            } else {
                return ['success' => false, 'message' => 'Lỗi cập nhật: ' . mysqli_error($link)];
            }
        }
    }

    return ['success' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Approve exception request
 *
 * @param int $condition_id Approval condition ID
 * @param int $user_id User approving (CPD/GDK)
 * @param string $notes Optional notes
 * @return array ['success' => bool, 'message' => string]
 */
function approve_exception($link, $condition_id, $user_id, $notes = '') {
    // Get condition
    $cond_sql = "SELECT * FROM approval_conditions WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $cond_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $condition_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $condition = mysqli_fetch_assoc($result);

        if (!$condition) {
            return ['success' => false, 'message' => 'Điều kiện không tồn tại'];
        }

        if (!$condition['is_exception_requested']) {
            return ['success' => false, 'message' => 'Chưa có yêu cầu ngoại lệ'];
        }

        // Approve exception
        $update_sql = "UPDATE approval_conditions
                      SET exception_approved = 1,
                          exception_approved_by_id = ?,
                          exception_approved_date = CURDATE()
                      WHERE id = ?";

        if ($upd_stmt = mysqli_prepare($link, $update_sql)) {
            mysqli_stmt_bind_param($upd_stmt, "ii", $user_id, $condition_id);

            if (mysqli_stmt_execute($upd_stmt)) {
                // Log in history
                $hist_sql = "INSERT INTO application_history
                            (application_id, user_id, action, comment, timestamp)
                            VALUES (?, ?, 'Approve Exception', ?, NOW())";

                if ($hist_stmt = mysqli_prepare($link, $hist_sql)) {
                    $comment = "Chấp thuận ngoại lệ: " . $condition['condition_text'];
                    if ($notes) {
                        $comment .= " - Ghi chú: " . $notes;
                    }
                    mysqli_stmt_bind_param($hist_stmt, "iis",
                        $condition['application_id'],
                        $user_id,
                        $comment
                    );
                    mysqli_stmt_execute($hist_stmt);
                }

                return [
                    'success' => true,
                    'message' => 'Ngoại lệ đã được chấp thuận'
                ];
            }
        }
    }

    return ['success' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Reject exception request
 *
 * @param int $condition_id Approval condition ID
 * @param int $user_id User rejecting
 * @param string $reason Reason for rejection
 * @return array ['success' => bool, 'message' => string]
 */
function reject_exception($link, $condition_id, $user_id, $reason) {
    $cond_sql = "SELECT * FROM approval_conditions WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $cond_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $condition_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $condition = mysqli_fetch_assoc($result);

        if (!$condition) {
            return ['success' => false, 'message' => 'Điều kiện không tồn tại'];
        }

        // Update with rejection
        $update_sql = "UPDATE approval_conditions
                      SET exception_approved = 0,
                          exception_approved_by_id = ?,
                          exception_approved_date = CURDATE(),
                          exception_rejection_reason = ?
                      WHERE id = ?";

        if ($upd_stmt = mysqli_prepare($link, $update_sql)) {
            mysqli_stmt_bind_param($upd_stmt, "isi", $user_id, $reason, $condition_id);

            if (mysqli_stmt_execute($upd_stmt)) {
                // Log
                $hist_sql = "INSERT INTO application_history
                            (application_id, user_id, action, comment, timestamp)
                            VALUES (?, ?, 'Reject Exception', ?, NOW())";

                if ($hist_stmt = mysqli_prepare($link, $hist_sql)) {
                    $comment = "Từ chối ngoại lệ: " . $condition['condition_text'] . " - Lý do: " . $reason;
                    mysqli_stmt_bind_param($hist_stmt, "iis",
                        $condition['application_id'],
                        $user_id,
                        $comment
                    );
                    mysqli_stmt_execute($hist_stmt);
                }

                return [
                    'success' => true,
                    'message' => 'Ngoại lệ đã bị từ chối'
                ];
            }
        }
    }

    return ['success' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Get pending exception requests for user
 */
function get_pending_exceptions_for_approver($link, $user_id) {
    // Get user role and approval limit
    $user_sql = "SELECT role, approval_limit FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $user_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            return [];
        }

        // Get exceptions for applications this user can approve
        $sql = "SELECT ac.*, ca.hstd_code, ca.amount, c.full_name as customer_name,
                       u.full_name as requested_by_name
                FROM approval_conditions ac
                JOIN credit_applications ca ON ac.application_id = ca.id
                JOIN customers c ON ca.customer_id = c.id
                JOIN users u ON ac.exception_requested_by_id = u.id
                WHERE ac.is_exception_requested = 1
                  AND ac.exception_approved IS NULL
                  AND ca.status = 'Đang xử lý'
                ORDER BY ac.exception_requested_date DESC";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
    }

    return [];
}

/**
 * Create escalation when application/disbursement is rejected
 *
 * @param array $data Escalation data
 * @return array ['success' => bool, 'message' => string, 'escalation_id' => int]
 */
function create_escalation($link, $data) {
    // Validate required fields
    $required = ['escalation_type', 'reason', 'escalated_by_id', 'escalated_to_id'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return ['success' => false, 'message' => "Thiếu field: {$field}"];
        }
    }

    // Must have either application_id or disbursement_id
    if (empty($data['application_id']) && empty($data['disbursement_id'])) {
        return ['success' => false, 'message' => 'Phải có application_id hoặc disbursement_id'];
    }

    // Insert escalation
    $sql = "INSERT INTO escalations
            (application_id, disbursement_id, escalation_type, reason,
             escalated_by_id, escalated_to_id, original_rejector_id,
             urgency_level, status, escalated_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

    if ($stmt = mysqli_prepare($link, $sql)) {
        $app_id = $data['application_id'] ?? null;
        $disb_id = $data['disbursement_id'] ?? null;
        $urgency = $data['urgency_level'] ?? 'Normal';
        $rejector_id = $data['original_rejector_id'] ?? null;

        mysqli_stmt_bind_param($stmt, "iissiiis",
            $app_id,
            $disb_id,
            $data['escalation_type'],
            $data['reason'],
            $data['escalated_by_id'],
            $data['escalated_to_id'],
            $rejector_id,
            $urgency
        );

        if (mysqli_stmt_execute($stmt)) {
            $escalation_id = mysqli_insert_id($link);

            // Log in appropriate history
            if ($app_id) {
                $hist_sql = "INSERT INTO application_history
                            (application_id, user_id, action, comment, timestamp)
                            VALUES (?, ?, 'Escalate', ?, NOW())";

                if ($hist_stmt = mysqli_prepare($link, $hist_sql)) {
                    mysqli_stmt_bind_param($hist_stmt, "iis",
                        $app_id,
                        $data['escalated_by_id'],
                        $data['reason']
                    );
                    mysqli_stmt_execute($hist_stmt);
                }
            }

            return [
                'success' => true,
                'message' => 'Khiếu nại đã được gửi lên cấp cao hơn',
                'escalation_id' => $escalation_id
            ];
        } else {
            return ['success' => false, 'message' => 'Lỗi tạo escalation: ' . mysqli_error($link)];
        }
    }

    return ['success' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Resolve escalation
 *
 * @param string $resolution_type 'Resolved - Approved' | 'Resolved - Rejected'
 * @return array ['success' => bool, 'message' => string]
 */
function resolve_escalation($link, $escalation_id, $user_id, $resolution_type, $resolution) {
    // Get escalation
    $esc_sql = "SELECT * FROM escalations WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $esc_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $escalation_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $escalation = mysqli_fetch_assoc($result);

        if (!$escalation) {
            return ['success' => false, 'message' => 'Escalation không tồn tại'];
        }

        // Update escalation
        $update_sql = "UPDATE escalations
                      SET status = ?,
                          resolution = ?,
                          resolved_by_id = ?,
                          resolved_date = NOW()
                      WHERE id = ?";

        if ($upd_stmt = mysqli_prepare($link, $update_sql)) {
            mysqli_stmt_bind_param($upd_stmt, "ssii",
                $resolution_type,
                $resolution,
                $user_id,
                $escalation_id
            );

            if (mysqli_stmt_execute($upd_stmt)) {
                // Log in history
                if ($escalation['application_id']) {
                    $hist_sql = "INSERT INTO application_history
                                (application_id, user_id, action, comment, timestamp)
                                VALUES (?, ?, 'Resolve Escalation', ?, NOW())";

                    if ($hist_stmt = mysqli_prepare($link, $hist_sql)) {
                        $comment = "Giải quyết khiếu nại: " . $resolution_type . " - " . $resolution;
                        mysqli_stmt_bind_param($hist_stmt, "iis",
                            $escalation['application_id'],
                            $user_id,
                            $comment
                        );
                        mysqli_stmt_execute($hist_stmt);
                    }
                }

                return [
                    'success' => true,
                    'message' => 'Escalation đã được giải quyết'
                ];
            }
        }
    }

    return ['success' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Get escalations for user (as escalated_to)
 */
function get_escalations_for_user($link, $user_id) {
    $sql = "SELECT e.*, ca.hstd_code, c.full_name as customer_name,
                   u1.full_name as escalated_by_name, u2.full_name as resolved_by_name
            FROM escalations e
            LEFT JOIN credit_applications ca ON e.application_id = ca.id
            LEFT JOIN customers c ON ca.customer_id = c.id
            JOIN users u1 ON e.escalated_by_id = u1.id
            LEFT JOIN users u2 ON e.resolved_by_id = u2.id
            WHERE e.escalated_to_id = ?
            ORDER BY e.escalated_date DESC";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    return [];
}

/**
 * Get pending escalations count for user
 */
function get_pending_escalations_count($link, $user_id) {
    $sql = "SELECT COUNT(*) as total
            FROM escalations
            WHERE escalated_to_id = ?
              AND status = 'Pending'";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    return 0;
}

?>
