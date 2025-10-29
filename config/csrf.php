<?php
// File: config/csrf.php
// CSRF Protection Implementation

/**
 * Generate CSRF token for form protection
 * @return string The CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from form submission
 * @param string $token The token to verify
 * @return void Dies if token is invalid
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        error_log("CSRF token validation failed. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        http_response_code(403);
        die('CSRF token validation failed. Possible CSRF attack detected.');
    }
}

/**
 * Regenerate CSRF token (call after sensitive operations)
 */
function regenerate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
