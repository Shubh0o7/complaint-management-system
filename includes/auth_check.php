<?php
/**
 * Authentication Check Guard
 * Redirects unauthenticated users to login
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>