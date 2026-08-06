<?php
/**
 * Database Configuration
 * Phase 1 - Complaint Management System
 * 
 * Edit the credentials below to match your local MySQL setup.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'complaint_system');

// Create MySQLi connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('<div class="alert alert-danger">Connection failed: ' . $conn->connect_error . '</div>');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>