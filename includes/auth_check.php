<?php
/**
 * Authentication and security guard for authenticated application pages.
 */
require_once __DIR__ . '/security.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['user_active']) && !(bool)$_SESSION['user_active']) {
    session_unset();
    session_destroy();
    header('Location: login.php?error=inactive');
    exit();
}
?>
