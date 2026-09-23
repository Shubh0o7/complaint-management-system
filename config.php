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

// Seed a small, clearly labelled demo dataset once so each role workspace has
// useful content to demonstrate without duplicating cases on every request.
$seedCheck = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'demo_cases_seeded_v1' LIMIT 1");
if ($seedCheck && $seedCheck->num_rows === 0) {
    $demoPassword = '$2y$10$MKYI3XkThqoJujeEMhMJ6OcdT1f2HQzV2nfa3jiiEFVCajhb.p7J.';
    $demoUser = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_active) VALUES ('Demo Student', 'demo.student@campus.edu', ?, 'user', 1) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
    if ($demoUser) {
        $demoUser->bind_param('s', $demoPassword);
        $demoUser->execute();
        $demoUser->close();
        $demoUserId = (int) $conn->insert_id;
        if ($demoUserId <= 0) {
            $demoResult = $conn->query("SELECT id FROM users WHERE email = 'demo.student@campus.edu' LIMIT 1");
            $demoUserId = (int) ($demoResult?->fetch_assoc()['id'] ?? 0);
        }
        $officerResult = $conn->query("SELECT id FROM users WHERE email = 'officer@campus.edu' AND role = 'officer' LIMIT 1");
        $officerId = (int) ($officerResult?->fetch_assoc()['id'] ?? 0);
        $cases = [
            ['IT Support', 'Wi-Fi access is unstable in the library', 'High', 'The library connection drops several times during study hours.', 'Information Technology', 'Pending'],
            ['Infrastructure', 'Water dispenser requires maintenance', 'Medium', 'The dispenser near the north block is not cooling water.', 'Infrastructure', 'In Progress'],
            ['Academic', 'Request for examination timetable clarification', 'Medium', 'Please clarify the room allocation for the upcoming assessment.', 'Academic Affairs', 'Resolved'],
            ['Hostel', 'Hostel study room lighting issue', 'Low', 'Two lights in the common study room are not working.', 'Student Affairs', 'Pending'],
        ];
        $caseInsert = $conn->prepare("INSERT INTO complaints (user_id, department_id, officer_id, subject, category, priority, description, status, admin_remarks) SELECT ?, d.id, NULLIF(?, 0), ?, ?, ?, ?, ?, 'Demo case seeded for presentation' FROM departments d WHERE d.name = ? AND NOT EXISTS (SELECT 1 FROM complaints c WHERE c.subject = ? AND c.user_id = ?)");
        if ($caseInsert && $demoUserId > 0) {
            foreach ($cases as [$category, $subject, $priority, $description, $department, $status]) {
                $caseInsert->bind_param('iisssssssi', $demoUserId, $officerId, $subject, $category, $priority, $description, $status, $department, $subject, $demoUserId);
                $caseInsert->execute();
            }
            $caseInsert->close();
            $conn->query("INSERT INTO system_settings (setting_key, setting_value) VALUES ('demo_cases_seeded_v1', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
