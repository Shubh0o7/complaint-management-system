<?php
/**
 * Admin Check Guard
 * Ensures only admin users can access protected pages
 */

require_once 'auth_check.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo '<div class="alert alert-danger m-4">Access Denied. Admin privileges required.</div>';
    exit();
}
?>