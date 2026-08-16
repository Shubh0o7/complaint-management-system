<?php
/**
 * Shared security and workflow primitives for the college-project build.
 * This file intentionally contains deterministic helpers only: no external
 * service credentials are required for local or Docker demonstrations.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_meta(): string
{
    return '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function require_csrf(): void
{
    if (!verify_csrf()) {
        http_response_code(419);
        exit('Security token expired. Please refresh the page and try again.');
    }
}

function require_csrf_json(): void
{
    if (!verify_csrf()) {
        http_response_code(419);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh the page and try again.']);
        exit;
    }
}

function audit_log(mysqli $conn, string $action, string $entityType, ?int $entityId, string $description, ?int $userId = null): bool
{
    $userId = $userId ?? (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null);
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? 'cli', 0, 45);
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'cli', 0, 255);
    $stmt = $conn->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) return false;
    $stmt->bind_param('ississs', $userId, $action, $entityType, $entityId, $description, $ip, $agent);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function generate_reference_no(mysqli $conn, int $complaintId, ?string $year = null): string
{
    $year = $year ?: date('Y');
    $reference = sprintf('GRV-%s-%05d', $year, $complaintId);
    $stmt = $conn->prepare('UPDATE complaints SET reference_no = ? WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('si', $reference, $complaintId);
        $stmt->execute();
        $stmt->close();
    }
    return $reference;
}

function sla_hours_for(mysqli $conn, string $priority): int
{
    $stmt = $conn->prepare('SELECT resolution_hours FROM sla_policies WHERE priority = ? AND is_active = 1 LIMIT 1');
    if (!$stmt) return 120;
    $stmt->bind_param('s', $priority);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['resolution_hours'] ?? 120);
}

function calculate_sla_due_at(mysqli $conn, string $priority, ?string $createdAt = null): string
{
    $base = $createdAt ? strtotime($createdAt) : time();
    return date('Y-m-d H:i:s', $base + (sla_hours_for($conn, $priority) * 3600));
}

function is_complaint_overdue(?string $dueAt, string $status): bool
{
    return !empty($dueAt) && !in_array($status, ['Resolved', 'Rejected'], true) && strtotime($dueAt) < time();
}

function role_home(?string $role = null): string
{
    $role = $role ?? ($_SESSION['user_role'] ?? 'user');
    return match ($role) {
        'admin' => 'admin_dashboard.php',
        'department' => 'department_dashboard.php',
        'officer' => 'officer_dashboard.php',
        default => 'dashboard.php',
    };
}

function redirect_role_home(): never
{
    header('Location: ' . role_home());
    exit;
}

function safe_upload(array $file, string $targetDir, array $allowedMimes, int $maxBytes = 5242880): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The upload could not be completed.');
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxBytes) {
        throw new RuntimeException('The file is empty or exceeds the 5MB limit.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException('The uploaded file type is not permitted.');
    }
    if ($mime === 'image/jpeg' && @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('The uploaded JPEG is not a valid image.');
    }
    if ($mime === 'image/png' && @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('The uploaded PNG is not a valid image.');
    }
    if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true)) {
        throw new RuntimeException('The secure upload directory could not be created.');
    }
    $stored = bin2hex(random_bytes(20));
    $path = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $stored;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('The uploaded file could not be stored securely.');
    }
    chmod($path, 0640);
    return ['stored_name' => $stored, 'mime' => $mime, 'size' => (int)$file['size'], 'path' => $path];
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
