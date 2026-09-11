<?php
/**
 * Admin Check Guard
 * Ensures only admin users can access protected pages
 */

require_once __DIR__ . '/role_check.php';
require_role(['admin']);
?>