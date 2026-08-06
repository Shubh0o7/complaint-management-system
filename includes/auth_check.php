<?php
/**
 * Authentication Guard
 * Include this file at the top of any page that requires login.
 * Redirects to login.php if the user is not authenticated.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>