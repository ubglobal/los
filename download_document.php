<?php
/**
 * Secure Document Download - with Access Control
 *
 * Prevents direct file access and enforces permissions
 *
 * @author Claude AI
 * @version 1.0
 * @date 2025-10-30
 */

require_once "config/session.php";
init_secure_session();
require_once "config/db.php";
require_once "includes/functions.php";

// Must be logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    die("Access denied. Please login.");
}

// Check session timeout
check_session_timeout();

// Get document ID
$doc_id = (int)($_GET['id'] ?? 0);
if ($doc_id <= 0) {
    http_response_code(400);
    die("Invalid document ID.");
}

// Get document info with application details
$sql = "SELECT ad.*,
               ca.created_by_id,
               ca.assigned_to_id,
               ca.hstd_code
        FROM application_documents ad
        JOIN credit_applications ca ON ad.application_id = ca.id
        WHERE ad.id = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $doc_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $doc = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$doc) {
        http_response_code(404);
        die("Document not found.");
    }

    // ==== ACCESS CONTROL CHECK ====
    $user_id = $_SESSION['id'];
    $user_role = $_SESSION['role'];
    $has_access = false;

    // Admin can access all documents
    if ($user_role === 'Admin') {
        $has_access = true;
    }

    // Check if user is currently assigned to this application
    if ($doc['assigned_to_id'] == $user_id) {
        $has_access = true;
    }

    // Check if user created this application
    if ($doc['created_by_id'] == $user_id) {
        $has_access = true;
    }

    // Check if user has ever worked on this application (in history)
    if (!$has_access) {
        $history_sql = "SELECT COUNT(*) as cnt
                       FROM application_history
                       WHERE application_id = ? AND user_id = ?";
        if ($history_stmt = mysqli_prepare($link, $history_sql)) {
            mysqli_stmt_bind_param($history_stmt, "ii", $doc['application_id'], $user_id);
            mysqli_stmt_execute($history_stmt);
            $history_result = mysqli_stmt_get_result($history_stmt);
            $history_row = mysqli_fetch_assoc($history_result);
            if ($history_row['cnt'] > 0) {
                $has_access = true;
            }
            mysqli_stmt_close($history_stmt);
        }
    }

    // Deny access if user doesn't have rights
    if (!$has_access) {
        error_log("Unauthorized document access attempt: user_id={$user_id}, doc_id={$doc_id}, app={$doc['hstd_code']}");
        http_response_code(403);
        die("You do not have permission to access this document. (Error: 403 Forbidden)");
    }
    // ==== END ACCESS CONTROL ====

    // Build safe file path with security checks
    $safe_filename = basename($doc['file_path']); // Remove directory traversal
    $full_path = realpath('uploads/' . $safe_filename);
    $upload_dir = realpath('uploads/');

    // Verify file is within uploads directory (prevent path traversal)
    if (!$full_path || !$upload_dir || strpos($full_path, $upload_dir) !== 0) {
        error_log("Path traversal attempt detected: doc_id={$doc_id}, path={$doc['file_path']}");
        http_response_code(403);
        die("Invalid file path.");
    }

    // Check if file exists
    if (!file_exists($full_path)) {
        error_log("Document file not found on disk: doc_id={$doc_id}, path={$full_path}");
        http_response_code(404);
        die("File not found on server.");
    }

    // Determine MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $full_path);
    finfo_close($finfo);

    // Fallback MIME type
    if (!$mime_type) {
        $mime_type = 'application/octet-stream';
    }

    // Get file extension for safe filename
    $file_extension = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
    $safe_download_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $doc['document_name']);
    $safe_download_name .= '.' . $file_extension;

    // Log successful download
    error_log("Document downloaded: doc_id={$doc_id}, user_id={$user_id}, app={$doc['hstd_code']}, file={$safe_filename}");

    // Serve file with proper headers
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $safe_download_name . '"');
    header('Content-Length: ' . filesize($full_path));
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    // Clear output buffer
    ob_clean();
    flush();

    // Read and output file
    readfile($full_path);
    exit;

} else {
    http_response_code(500);
    die("Database error.");
}
?>
