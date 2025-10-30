<?php
/**
 * Permission Functions - Hệ thống phân quyền nâng cao
 *
 * Handles:
 * - Check user permissions (Access/Input/Update/Delete/Approve)
 * - Branch-based access control
 * - Role-based filtering
 * - Permission caching
 *
 * @author Claude AI
 * @version 3.0
 * @date 2024-10-30
 */

// Prevent direct access
if (!defined('PERMISSION_FUNCTIONS_LOADED')) {
    define('PERMISSION_FUNCTIONS_LOADED', true);
}

// Cache permissions in session for performance
if (!isset($_SESSION['user_permissions_cache'])) {
    $_SESSION['user_permissions_cache'] = [];
}

/**
 * Check if user has specific permission
 *
 * @param int $user_id User ID
 * @param string $permission_code Permission code (e.g., 'credit.approve')
 * @return bool True if user has permission
 */
function has_permission($link, $user_id, $permission_code) {
    // Check cache first
    $cache_key = $user_id . '_' . $permission_code;
    if (isset($_SESSION['user_permissions_cache'][$cache_key])) {
        return $_SESSION['user_permissions_cache'][$cache_key];
    }

    // Get user role
    $user_sql = "SELECT role FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $user_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            return false;
        }

        // Admin has all permissions
        if ($user['role'] === 'Admin') {
            $_SESSION['user_permissions_cache'][$cache_key] = true;
            return true;
        }

        // Check permission via role
        $perm_sql = "SELECT COUNT(*) as has_perm
                    FROM role_permissions rp
                    JOIN permissions p ON rp.permission_id = p.id
                    JOIN roles r ON rp.role_id = r.id
                    WHERE r.role_code = ?
                      AND p.permission_code = ?";

        if ($perm_stmt = mysqli_prepare($link, $perm_sql)) {
            mysqli_stmt_bind_param($perm_stmt, "ss", $user['role'], $permission_code);
            mysqli_stmt_execute($perm_stmt);
            $perm_result = mysqli_stmt_get_result($perm_stmt);
            $perm = mysqli_fetch_assoc($perm_result);

            $has_perm = ($perm['has_perm'] > 0);
            $_SESSION['user_permissions_cache'][$cache_key] = $has_perm;

            return $has_perm;
        }
    }

    return false;
}

/**
 * Get all permissions for user
 *
 * @return array List of permission codes
 */
function get_user_permissions($link, $user_id) {
    $user_sql = "SELECT role FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $user_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            return [];
        }

        // Admin gets all permissions
        if ($user['role'] === 'Admin') {
            $all_perms_sql = "SELECT permission_code FROM permissions";
            $all_result = mysqli_query($link, $all_perms_sql);
            $perms = [];
            while ($row = mysqli_fetch_assoc($all_result)) {
                $perms[] = $row['permission_code'];
            }
            return $perms;
        }

        // Get permissions for role
        $perm_sql = "SELECT p.permission_code
                    FROM role_permissions rp
                    JOIN permissions p ON rp.permission_id = p.id
                    JOIN roles r ON rp.role_id = r.id
                    WHERE r.role_code = ?
                    ORDER BY p.module, p.permission_type";

        if ($perm_stmt = mysqli_prepare($link, $perm_sql)) {
            mysqli_stmt_bind_param($perm_stmt, "s", $user['role']);
            mysqli_stmt_execute($perm_stmt);
            $perm_result = mysqli_stmt_get_result($perm_stmt);

            $permissions = [];
            while ($row = mysqli_fetch_assoc($perm_result)) {
                $permissions[] = $row['permission_code'];
            }

            return $permissions;
        }
    }

    return [];
}

/**
 * Check if user can access specific branch
 *
 * @param int $user_id User ID
 * @param string $branch Branch name
 * @return bool True if user can access
 */
function can_access_branch($link, $user_id, $branch) {
    // Get user role and branch
    $user_sql = "SELECT role, branch FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $user_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            return false;
        }

        // Admin and GDK can access all branches
        if ($user['role'] === 'Admin' || $user['role'] === 'GDK') {
            return true;
        }

        // Check if user's home branch matches
        if ($user['branch'] === $branch) {
            return true;
        }

        // Check user_branch_access table for cross-branch access
        $access_sql = "SELECT COUNT(*) as has_access
                      FROM user_branch_access
                      WHERE user_id = ?
                        AND branch = ?";

        if ($access_stmt = mysqli_prepare($link, $access_sql)) {
            mysqli_stmt_bind_param($access_stmt, "is", $user_id, $branch);
            mysqli_stmt_execute($access_stmt);
            $access_result = mysqli_stmt_get_result($access_stmt);
            $access = mysqli_fetch_assoc($access_result);

            return ($access['has_access'] > 0);
        }
    }

    return false;
}

/**
 * Filter applications by user's branch access
 *
 * @param array $applications List of applications
 * @return array Filtered applications
 */
function filter_by_branch_access($link, $user_id, $applications) {
    // Get user
    $user_sql = "SELECT role, branch FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $user_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            return [];
        }

        // Admin and GDK see all
        if ($user['role'] === 'Admin' || $user['role'] === 'GDK') {
            return $applications;
        }

        // Get accessible branches
        $branches_sql = "SELECT branch FROM user_branch_access WHERE user_id = ?
                        UNION
                        SELECT branch FROM users WHERE id = ?";

        if ($br_stmt = mysqli_prepare($link, $branches_sql)) {
            mysqli_stmt_bind_param($br_stmt, "ii", $user_id, $user_id);
            mysqli_stmt_execute($br_stmt);
            $br_result = mysqli_stmt_get_result($br_stmt);

            $accessible_branches = [];
            while ($row = mysqli_fetch_assoc($br_result)) {
                $accessible_branches[] = $row['branch'];
            }

            // Filter applications
            // Note: This assumes applications have a 'branch' field or can be joined with users
            // For actual implementation, you'd need to adjust based on your schema

            return array_filter($applications, function($app) use ($accessible_branches) {
                // Simplified - in reality you'd check app's branch
                return true; // Placeholder
            });
        }
    }

    return $applications;
}

/**
 * Check if user can perform action on application
 *
 * @param int $user_id User ID
 * @param int $application_id Application ID
 * @param string $action Action (view/edit/approve/delete)
 * @return array ['allowed' => bool, 'message' => string]
 */
function can_perform_action_on_application($link, $user_id, $application_id, $action) {
    // Get application
    $app_sql = "SELECT ca.*, u.branch as creator_branch
                FROM credit_applications ca
                JOIN users u ON ca.created_by_id = u.id
                WHERE ca.id = ?";

    if ($stmt = mysqli_prepare($link, $app_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $app = mysqli_fetch_assoc($result);

        if (!$app) {
            return ['allowed' => false, 'message' => 'Hồ sơ không tồn tại'];
        }

        // Get user
        $user_sql = "SELECT role, branch FROM users WHERE id = ?";
        if ($user_stmt = mysqli_prepare($link, $user_sql)) {
            mysqli_stmt_bind_param($user_stmt, "i", $user_id);
            mysqli_stmt_execute($user_stmt);
            $user_result = mysqli_stmt_get_result($user_stmt);
            $user = mysqli_fetch_assoc($user_result);

            if (!$user) {
                return ['allowed' => false, 'message' => 'User không tồn tại'];
            }

            // Admin can do everything
            if ($user['role'] === 'Admin') {
                return ['allowed' => true, 'message' => ''];
            }

            // Check branch access
            if (!can_access_branch($link, $user_id, $app['creator_branch'])) {
                return ['allowed' => false, 'message' => 'Bạn không có quyền truy cập chi nhánh này'];
            }

            // Check specific action permissions
            $permission_map = [
                'view' => 'credit.access',
                'edit' => 'credit.update',
                'approve' => 'credit.approve',
                'delete' => 'credit.delete'
            ];

            if (isset($permission_map[$action])) {
                if (!has_permission($link, $user_id, $permission_map[$action])) {
                    return ['allowed' => false, 'message' => 'Bạn không có quyền ' . $action];
                }
            }

            return ['allowed' => true, 'message' => ''];
        }
    }

    return ['allowed' => false, 'message' => 'Lỗi hệ thống'];
}

/**
 * Clear permission cache for user
 */
function clear_permission_cache($user_id = null) {
    if ($user_id === null) {
        // Clear all
        $_SESSION['user_permissions_cache'] = [];
    } else {
        // Clear specific user
        foreach ($_SESSION['user_permissions_cache'] as $key => $value) {
            if (strpos($key, $user_id . '_') === 0) {
                unset($_SESSION['user_permissions_cache'][$key]);
            }
        }
    }
}

/**
 * Check if user can approve based on approval limit
 *
 * @param int $user_id User ID
 * @param float $amount Amount to approve
 * @return array ['can_approve' => bool, 'message' => string, 'approval_limit' => float]
 */
function check_approval_limit($link, $user_id, $amount) {
    $user_sql = "SELECT role, approval_limit FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $user_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            return ['can_approve' => false, 'message' => 'User không tồn tại', 'approval_limit' => 0];
        }

        // Admin has no limit
        if ($user['role'] === 'Admin') {
            return ['can_approve' => true, 'message' => '', 'approval_limit' => null];
        }

        // Check approval limit
        if ($user['approval_limit'] === null) {
            return [
                'can_approve' => false,
                'message' => 'User không có quyền phê duyệt',
                'approval_limit' => 0
            ];
        }

        if ($amount > $user['approval_limit']) {
            return [
                'can_approve' => false,
                'message' => sprintf(
                    'Số tiền %s vượt hạn mức phê duyệt của bạn (%s). Cần phê duyệt cấp cao hơn.',
                    number_format($amount),
                    number_format($user['approval_limit'])
                ),
                'approval_limit' => $user['approval_limit']
            ];
        }

        return [
            'can_approve' => true,
            'message' => '',
            'approval_limit' => $user['approval_limit']
        ];
    }

    return ['can_approve' => false, 'message' => 'Lỗi hệ thống', 'approval_limit' => 0];
}

/**
 * Get users who can approve given amount
 *
 * @param float $amount Amount
 * @return array List of users
 */
function get_approvers_for_amount($link, $amount) {
    $sql = "SELECT id, full_name, role, approval_limit
            FROM users
            WHERE (approval_limit >= ? OR role = 'Admin')
              AND role IN ('CPD', 'GDK', 'Admin')
            ORDER BY approval_limit ASC";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "d", $amount);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    return [];
}

?>
