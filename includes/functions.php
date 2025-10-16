<?php
// File: includes/functions.php
// Version 4.2 - Complete Function Set

// --- User Functions ---

function get_all_users($link) {
    $sql = "SELECT id, username, full_name, role, branch, approval_limit FROM users ORDER BY full_name";
    $result = mysqli_query($link, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function get_user_by_id($link, $user_id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            return mysqli_fetch_array($result, MYSQLI_ASSOC);
        }
    }
    return null;
}

function get_user_by_role($link, $role) {
    $sql = "SELECT * FROM users WHERE role = ? LIMIT 1";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $role);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            return mysqli_fetch_array($result, MYSQLI_ASSOC);
        }
    }
    return null;
}

// --- Customer Functions ---

function get_all_customers($link) {
    $sql = "SELECT id, customer_code, full_name, customer_type, id_number, company_tax_code FROM customers ORDER BY full_name";
    $result = mysqli_query($link, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function get_customer_by_id($link, $customer_id) {
    $sql = "SELECT * FROM customers WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            return mysqli_fetch_array($result, MYSQLI_ASSOC);
        }
    }
    return null;
}

function get_credit_ratings_for_customer($link, $customer_id) {
    $sql = "SELECT * FROM customer_credit_ratings WHERE customer_id = ? ORDER BY rating_date DESC";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    return [];
}

function get_related_parties_for_customer($link, $customer_id) {
    $sql = "SELECT crp.relationship_type, c.full_name as related_customer_name, c.customer_code as related_customer_code
            FROM customer_related_parties crp
            JOIN customers c ON crp.related_customer_id = c.id
            WHERE crp.customer_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    return [];
}

function get_credit_history_for_customer($link, $customer_id, $current_application_id) {
    $sql = "SELECT ca.hstd_code, p.name as product_name, ca.amount, ca.status
            FROM credit_applications ca
            JOIN products p ON ca.product_id = p.id
            WHERE ca.customer_id = ? AND ca.id != ?
            ORDER BY ca.created_at DESC";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $customer_id, $current_application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    return [];
}

// --- Application Functions ---

function get_applications_for_user($link, $user_id) {
    $sql = "SELECT ca.*, p.name as product_name, c.full_name as customer_name
            FROM credit_applications ca
            JOIN products p ON ca.product_id = p.id
            JOIN customers c ON ca.customer_id = c.id
            WHERE ca.assigned_to_id = ?
            ORDER BY ca.updated_at DESC";
    
    $apps = [];
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $apps = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        mysqli_stmt_close($stmt);
    }
    return $apps;
}

function get_application_details($link, $app_id) {
    $sql = "SELECT ca.*, p.name as product_name 
            FROM credit_applications ca
            JOIN products p ON ca.product_id = p.id
            WHERE ca.id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $app_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            return mysqli_fetch_array($result, MYSQLI_ASSOC);
        }
    }
    return null;
}

function get_application_history($link, $app_id) {
    $sql = "SELECT ah.*, u.full_name, u.role 
            FROM application_history ah
            JOIN users u ON ah.user_id = u.id
            WHERE ah.application_id = ?
            ORDER BY ah.timestamp ASC";
    $history = [];
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $app_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $history = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        mysqli_stmt_close($stmt);
    }
    return $history;
}

function add_history($link, $app_id, $user_id, $action, $comment) {
    $sql = "INSERT INTO application_history (application_id, user_id, action, comment) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "iiss", $app_id, $user_id, $action, $comment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function update_application_status($link, $app_id, $stage, $assigned_to_id, $status = 'Đang xử lý') {
    $assigned_to_id = $assigned_to_id === null ? null : (int)$assigned_to_id;
    $sql = "UPDATE credit_applications SET stage = ?, assigned_to_id = ?, status = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "sisi", $stage, $assigned_to_id, $status, $app_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// --- Product & Collateral Type Functions ---

function get_all_products($link) {
    $sql = "SELECT * FROM products ORDER BY name";
    $result = mysqli_query($link, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function get_all_collateral_types($link) {
    $sql = "SELECT * FROM collateral_types ORDER BY name";
    $result = mysqli_query($link, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// --- Application Detail Functions (Collaterals, Repayments, Docs) ---

function get_collaterals_for_app($link, $app_id) {
    $sql = "SELECT ac.*, ct.name as type_name 
            FROM application_collaterals ac
            JOIN collateral_types ct ON ac.collateral_type_id = ct.id
            WHERE ac.application_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $app_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    return [];
}

function get_repayment_sources_for_app($link, $app_id) {
    $sql = "SELECT * FROM application_repayment_sources WHERE application_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $app_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    return [];
}

function get_documents_for_app($link, $app_id) {
    $sql = "SELECT ad.*, u.full_name as uploader_name
            FROM application_documents ad
            JOIN users u ON ad.uploaded_by_id = u.id
            WHERE ad.application_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $app_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    return [];
}
?>

