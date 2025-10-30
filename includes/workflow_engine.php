<?php
/**
 * Workflow Engine - Core workflow management
 *
 * Handles workflow transitions, validation, and state management for:
 * - Credit Approval workflow
 * - Disbursement workflow
 *
 * @author Claude AI
 * @version 3.0
 * @date 2024-10-30
 */

// Prevent direct access
if (!defined('WORKFLOW_ENGINE_LOADED')) {
    define('WORKFLOW_ENGINE_LOADED', true);
}

/**
 * Get workflow step by code
 */
function get_workflow_step($link, $workflow_type, $step_code) {
    $sql = "SELECT * FROM workflow_steps
            WHERE workflow_type = ? AND step_code = ? AND is_active = 1
            LIMIT 1";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $workflow_type, $step_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    return null;
}

/**
 * Get current workflow step for application
 */
function get_current_step($link, $application_id) {
    $sql = "SELECT ws.* FROM workflow_steps ws
            JOIN credit_applications ca ON ca.current_step_id = ws.id
            WHERE ca.id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    return null;
}

/**
 * Check if user can perform action on workflow step
 *
 * @param mysqli $link Database connection
 * @param int $user_id User ID
 * @param string $user_role User role (CVQHKH, CVTĐ, CPD, GDK, Admin)
 * @param array $step Workflow step data
 * @param string $action Action to perform (Save, Next, Approve, Reject, etc.)
 * @return array ['allowed' => bool, 'message' => string]
 */
function can_perform_action($link, $user_id, $user_role, $step, $action) {
    // Check if user role matches step requirement
    if ($step['role_required'] !== $user_role && $user_role !== 'Admin') {
        return [
            'allowed' => false,
            'message' => "Bạn không có quyền thực hiện thao tác này. Yêu cầu role: {$step['role_required']}"
        ];
    }

    // Parse allowed_actions JSON
    $allowed_actions = json_decode($step['allowed_actions'], true);

    if (!in_array($action, $allowed_actions)) {
        return [
            'allowed' => false,
            'message' => "Action '{$action}' không được phép ở bước này."
        ];
    }

    return ['allowed' => true, 'message' => ''];
}

/**
 * Validate workflow transition
 *
 * @param mysqli $link Database connection
 * @param int $application_id Application ID
 * @param string $action Action (Next, Approve, Reject, Return)
 * @param int $user_id User performing action
 * @return array ['valid' => bool, 'message' => string, 'next_step' => array|null]
 */
function validate_transition($link, $application_id, $action, $user_id) {
    // Get application and current step
    $app_sql = "SELECT ca.*, ws.*
                FROM credit_applications ca
                LEFT JOIN workflow_steps ws ON ca.current_step_id = ws.id
                WHERE ca.id = ?";

    if ($stmt = mysqli_prepare($link, $app_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $app = mysqli_fetch_assoc($result);

        if (!$app) {
            return ['valid' => false, 'message' => 'Hồ sơ không tồn tại'];
        }

        // Get user role
        $user_sql = "SELECT role FROM users WHERE id = ?";
        if ($user_stmt = mysqli_prepare($link, $user_sql)) {
            mysqli_stmt_bind_param($user_stmt, "i", $user_id);
            mysqli_stmt_execute($user_stmt);
            $user_result = mysqli_stmt_get_result($user_stmt);
            $user = mysqli_fetch_assoc($user_result);

            if (!$user) {
                return ['valid' => false, 'message' => 'User không tồn tại'];
            }

            // Check permission
            $can_perform = can_perform_action($link, $user_id, $user['role'], $app, $action);

            if (!$can_perform['allowed']) {
                return ['valid' => false, 'message' => $can_perform['message']];
            }

            // Determine next step based on action
            $next_step = null;

            switch ($action) {
                case 'Next':
                case 'Approve':
                    if ($app['next_step_on_approve']) {
                        $next_step = get_step_by_id($link, $app['next_step_on_approve']);
                    }
                    break;

                case 'Reject':
                    if ($app['next_step_on_reject']) {
                        $next_step = get_step_by_id($link, $app['next_step_on_reject']);
                    }
                    // Reject usually ends workflow
                    break;

                case 'Return':
                    if ($app['return_to_step']) {
                        $next_step = get_step_by_id($link, $app['return_to_step']);
                    }
                    break;
            }

            return [
                'valid' => true,
                'message' => '',
                'next_step' => $next_step,
                'current_step' => $app
            ];
        }
    }

    return ['valid' => false, 'message' => 'Lỗi validate transition'];
}

/**
 * Get step by ID
 */
function get_step_by_id($link, $step_id) {
    $sql = "SELECT * FROM workflow_steps WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $step_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/**
 * Execute workflow transition
 *
 * @param mysqli $link Database connection
 * @param int $application_id Application ID
 * @param string $action Action to perform
 * @param int $user_id User ID
 * @param string $comment Optional comment
 * @return array ['success' => bool, 'message' => string]
 */
function execute_transition($link, $application_id, $action, $user_id, $comment = '') {
    // Validate transition
    $validation = validate_transition($link, $application_id, $action, $user_id);

    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }

    $current_step = $validation['current_step'];
    $next_step = $validation['next_step'];

    // Begin transaction
    mysqli_begin_transaction($link);

    try {
        // Update application status and stage
        $new_status = $current_step['status'];
        $new_stage = $current_step['stage'];
        $new_step_id = $current_step['current_step_id'];

        switch ($action) {
            case 'Save':
                // Just save, no state change
                break;

            case 'Next':
                if ($next_step) {
                    $new_stage = $next_step['step_name'];
                    $new_step_id = $next_step['id'];
                    $new_status = 'Đang xử lý';
                }
                break;

            case 'Approve':
                if ($next_step) {
                    $new_stage = $next_step['step_name'];
                    $new_step_id = $next_step['id'];
                } else {
                    // Final approval
                    $new_status = 'Đã phê duyệt';
                    $new_stage = 'Đã phê duyệt';
                }
                break;

            case 'Reject':
                $new_status = 'Đã từ chối';
                $new_stage = 'Đã từ chối';
                $new_step_id = null;
                break;

            case 'Return':
                if ($next_step) {
                    $new_stage = $next_step['step_name'];
                    $new_step_id = $next_step['id'];
                    $new_status = 'Yêu cầu bổ sung';
                }
                break;
        }

        // Update application
        $update_sql = "UPDATE credit_applications
                      SET status = ?,
                          stage = ?,
                          current_step_id = ?,
                          previous_stage = ?,
                          assigned_to_id = ?,
                          updated_at = NOW()
                      WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $update_sql)) {
            $assigned_to = null; // Will be set based on next step role
            mysqli_stmt_bind_param($stmt, "ssissi",
                $new_status,
                $new_stage,
                $new_step_id,
                $current_step['stage'],
                $assigned_to,
                $application_id
            );
            mysqli_stmt_execute($stmt);
        }

        // Record in history
        $history_sql = "INSERT INTO application_history
                       (application_id, user_id, action, comment, timestamp)
                       VALUES (?, ?, ?, ?, NOW())";

        if ($stmt = mysqli_prepare($link, $history_sql)) {
            mysqli_stmt_bind_param($stmt, "iiss",
                $application_id,
                $user_id,
                $action,
                $comment
            );
            mysqli_stmt_execute($stmt);
        }

        // Update SLA if moved to new step
        if ($next_step && $next_step['sla_hours']) {
            update_sla($link, $application_id, $next_step['sla_hours']);
        }

        mysqli_commit($link);

        return [
            'success' => true,
            'message' => 'Thao tác thành công',
            'new_status' => $new_status,
            'new_stage' => $new_stage
        ];

    } catch (Exception $e) {
        mysqli_rollback($link);
        error_log("Workflow transition error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
    }
}

/**
 * Update SLA for application
 */
function update_sla($link, $application_id, $sla_hours) {
    $sql = "UPDATE credit_applications
            SET sla_due_date = DATE_ADD(NOW(), INTERVAL ? HOUR),
                sla_status = 'On Track'
            WHERE id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $sla_hours, $application_id);
        mysqli_stmt_execute($stmt);
    }
}

/**
 * Check SLA status for application
 *
 * @return string 'On Track' | 'Warning' | 'Overdue'
 */
function check_sla_status($link, $application_id) {
    $sql = "SELECT sla_due_date, sla_status FROM credit_applications WHERE id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $app = mysqli_fetch_assoc($result);

        if ($app && $app['sla_due_date']) {
            $due_date = strtotime($app['sla_due_date']);
            $now = time();
            $hours_left = ($due_date - $now) / 3600;

            if ($hours_left < 0) {
                return 'Overdue';
            } elseif ($hours_left < 4) {
                return 'Warning';
            } else {
                return 'On Track';
            }
        }
    }

    return 'On Track';
}

/**
 * Get workflow history for application
 */
function get_workflow_history($link, $application_id) {
    $sql = "SELECT ah.*, u.full_name as user_name
            FROM application_history ah
            JOIN users u ON ah.user_id = u.id
            WHERE ah.application_id = ?
            ORDER BY ah.timestamp DESC";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    return [];
}

/**
 * Get available actions for user on application
 *
 * @return array List of available actions
 */
function get_available_actions($link, $application_id, $user_id) {
    $current_step = get_current_step($link, $application_id);

    if (!$current_step) {
        return [];
    }

    // Get user role
    $user_sql = "SELECT role FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $user_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            return [];
        }

        // Check if user can perform actions on this step
        if ($current_step['role_required'] !== $user['role'] && $user['role'] !== 'Admin') {
            return [];
        }

        // Parse allowed actions
        return json_decode($current_step['allowed_actions'], true) ?: [];
    }

    return [];
}

?>
