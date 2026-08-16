<?php
/**
 * Database configuration.
 * Docker Compose supplies these values automatically; local PHP users can
 * override them with environment variables or use the same defaults.
 */
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_USER', getenv('DB_USER') ?: 'complaint_user');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'complaint_pass');
define('DB_NAME', getenv('DB_NAME') ?: 'complaint_system');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    http_response_code(503);
    die('Database is temporarily unavailable. Please try again in a moment.');
}
$conn->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
