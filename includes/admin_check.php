<?php
/**
 * Admin Authentication Check
 * Phase 2 - Redirects non-admin users
 */
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
?>