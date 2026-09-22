<?php
/**
 * Database configuration.
 * The Docker image ships with its own MariaDB instance and uses these local
 * defaults. Every value can still be overridden with environment variables
 * when connecting the app to a managed/external database.
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

// Correct the original demo hash on existing installations without
// overwriting a password that has already been changed by an administrator.
$legacyInfrastructureHash = '$2y$10$kBM/CstmMuDqht4dmzD8n.Cu.6izq93oSz37uBmpgFFoWNLm02yk6';
$currentInfrastructureHash = '$2y$10$qXNAQkDQpIjJkEpRnMFnS.90FMivN4kZnUpMjFfylN3XAMdCRlF0y';
$migration = $conn->prepare("UPDATE users SET password = ? WHERE email = 'infrastructure.manager@campus.edu' AND role = 'department' AND password = ?");
if ($migration) {
    $migration->bind_param('ss', $currentInfrastructureHash, $legacyInfrastructureHash);
    $migration->execute();
    $migration->close();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
