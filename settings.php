<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/security.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['user_role'] ?? 'user';
$isAdmin = $role === 'admin';
$message = '';
$error = '';

function setting_value(mysqli $conn, string $key, string $fallback = ''): string
{
    $stmt = $conn->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1');
    if (!$stmt) return $fallback;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (string)($row['setting_value'] ?? $fallback);
}

function save_setting(mysqli $conn, string $key, string $value, int $userId): void
{
    $stmt = $conn->prepare('INSERT INTO system_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP');
    if (!$stmt) throw new RuntimeException('Unable to prepare settings update.');
    $stmt->bind_param('ssi', $key, $value, $userId);
    if (!$stmt->execute()) throw new RuntimeException('Unable to save system settings.');
    $stmt->close();
}

try {
    $stmt = $conn->prepare('INSERT IGNORE INTO user_preferences (user_id) VALUES (?)');
    if ($stmt) { $stmt->bind_param('i', $userId); $stmt->execute(); $stmt->close(); }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_csrf();
        $emailNotifications = !empty($_POST['email_notifications']) ? 1 : 0;
        $digest = $_POST['notification_digest'] ?? 'instant';
        $theme = $_POST['theme'] ?? 'system';
        if (!in_array($digest, ['instant', 'daily', 'off'], true)) throw new RuntimeException('Choose a valid notification frequency.');
        if (!in_array($theme, ['system', 'light', 'dark'], true)) throw new RuntimeException('Choose a valid appearance preference.');

        $conn->begin_transaction();
        $stmt = $conn->prepare('UPDATE user_preferences SET email_notifications = ?, notification_digest = ?, theme = ? WHERE user_id = ?');
        $stmt->bind_param('issi', $emailNotifications, $digest, $theme, $userId);
        $stmt->execute();
        $stmt->close();

        if ($isAdmin) {
            $portalName = trim($_POST['portal_name'] ?? 'CampusResolve');
            $supportEmail = trim($_POST['support_email'] ?? '');
            $slaHours = (int)($_POST['default_sla_hours'] ?? 72);
            $emailEnabled = !empty($_POST['email_notifications_enabled']) ? '1' : '0';
            if ($portalName === '' || strlen($portalName) > 80) throw new RuntimeException('Portal name must be between 1 and 80 characters.');
            if (!filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid support email address.');
            if ($slaHours < 1 || $slaHours > 720) throw new RuntimeException('Default SLA must be between 1 and 720 hours.');
            save_setting($conn, 'portal_name', $portalName, $userId);
            save_setting($conn, 'support_email', $supportEmail, $userId);
            save_setting($conn, 'default_sla_hours', (string)$slaHours, $userId);
            save_setting($conn, 'email_notifications_enabled', $emailEnabled, $userId);
        }
        $conn->commit();
        audit_log($conn, 'update_settings', 'settings', null, $isAdmin ? 'User preferences and institution settings updated' : 'User notification and appearance preferences updated');
        $message = 'Settings saved successfully.';
    }
} catch (Throwable $e) {
    if ($conn->errno === 0) { /* no-op: keep response rendering safe after a validation error */ }
    try { $conn->rollback(); } catch (Throwable $ignored) { /* transaction may not have started */ }
    $error = $e->getMessage();
}

$stmt = $conn->prepare('SELECT email_notifications, notification_digest, theme FROM user_preferences WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$preferences = $stmt->get_result()->fetch_assoc() ?: ['email_notifications' => 1, 'notification_digest' => 'instant', 'theme' => 'system'];
$stmt->close();

$system = [
    'portal_name' => setting_value($conn, 'portal_name', 'CampusResolve'),
    'support_email' => setting_value($conn, 'support_email', 'support@campus.edu'),
    'default_sla_hours' => setting_value($conn, 'default_sla_hours', '72'),
    'email_notifications_enabled' => setting_value($conn, 'email_notifications_enabled', '1'),
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings | CampusResolve</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="d-flex min-vh-100">
<?php include 'includes/sidebar.php'; ?>
<main class="flex-grow-1">
<header class="bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
  <div><h4 class="mb-0"><i class="bi bi-sliders2-vertical text-primary me-2"></i>Settings</h4><small class="text-muted">Manage your preferences<?= $isAdmin ? ' and institution defaults' : '' ?>.</small></div>
  <a class="btn btn-outline-secondary btn-sm" href="profile.php"><i class="bi bi-person me-1"></i>Profile</a>
</header>
<div class="container-fluid p-4">
<?php if ($message): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?></div><?php endif; ?>
<form method="post">
<?= csrf_field() ?>
<div class="row g-4">
  <div class="col-xl-7">
    <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
      <div class="d-flex align-items-start gap-3 mb-4"><div class="rounded-3 bg-primary-subtle text-primary p-3"><i class="bi bi-bell fs-4"></i></div><div><h5 class="mb-1">Notification preferences</h5><p class="text-muted mb-0">Choose how updates about complaint activity should reach you.</p></div></div>
      <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" role="switch" id="email_notifications" name="email_notifications" value="1" <?= !empty($preferences['email_notifications']) ? 'checked' : '' ?>><label class="form-check-label" for="email_notifications"><strong>Email notifications</strong><small class="d-block text-muted">Receive status changes, assignments, comments, and escalation updates.</small></label></div>
      <div class="mb-3"><label class="form-label fw-semibold" for="notification_digest">Notification frequency</label><select class="form-select" id="notification_digest" name="notification_digest"><option value="instant" <?= ($preferences['notification_digest'] ?? '') === 'instant' ? 'selected' : '' ?>>Instant updates</option><option value="daily" <?= ($preferences['notification_digest'] ?? '') === 'daily' ? 'selected' : '' ?>>Daily digest</option><option value="off" <?= ($preferences['notification_digest'] ?? '') === 'off' ? 'selected' : '' ?>>In-app only</option></select></div>
      <div><label class="form-label fw-semibold" for="theme">Appearance</label><select class="form-select" id="theme" name="theme"><option value="system" <?= ($preferences['theme'] ?? '') === 'system' ? 'selected' : '' ?>>Use device preference</option><option value="light" <?= ($preferences['theme'] ?? '') === 'light' ? 'selected' : '' ?>>Light interface</option><option value="dark" <?= ($preferences['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Dark interface</option></select><div class="form-text">Theme preference is stored for future interface personalization.</div></div>
    </div></div>
    <div class="card border-0 shadow-sm"><div class="card-body p-4"><h5 class="mb-1">Security shortcuts</h5><p class="text-muted mb-3">Keep your account access current and protected.</p><a class="btn btn-outline-dark me-2" href="change_password.php"><i class="bi bi-key me-1"></i>Change password</a><a class="btn btn-outline-secondary" href="forgot_password.php"><i class="bi bi-life-preserver me-1"></i>Password recovery</a></div></div>
  </div>
  <div class="col-xl-5">
  <?php if ($isAdmin): ?>
    <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><div class="d-flex align-items-start gap-3 mb-4"><div class="rounded-3 bg-dark-subtle text-dark p-3"><i class="bi bi-building-gear fs-4"></i></div><div><h5 class="mb-1">Institution settings</h5><p class="text-muted mb-0">Defaults used by the grievance service.</p></div></div>
      <div class="mb-3"><label class="form-label fw-semibold" for="portal_name">Portal name</label><input class="form-control" id="portal_name" name="portal_name" value="<?= e($system['portal_name']) ?>" maxlength="80" required></div>
      <div class="mb-3"><label class="form-label fw-semibold" for="support_email">Support email</label><input class="form-control" id="support_email" name="support_email" type="email" value="<?= e($system['support_email']) ?>" required></div>
      <div class="mb-3"><label class="form-label fw-semibold" for="default_sla_hours">Default SLA hours</label><input class="form-control" id="default_sla_hours" name="default_sla_hours" type="number" min="1" max="720" value="<?= e($system['default_sla_hours']) ?>" required><div class="form-text">Used as the fallback resolution window for service policies.</div></div>
      <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="email_notifications_enabled" name="email_notifications_enabled" value="1" <?= $system['email_notifications_enabled'] === '1' ? 'checked' : '' ?>><label class="form-check-label" for="email_notifications_enabled"><strong>Enable system email notifications</strong><small class="d-block text-muted">The email helper still records delivery attempts for audit review.</small></label></div>
    </div></div>
  <?php else: ?>
    <div class="card border-0 shadow-sm"><div class="card-body p-4"><h5 class="mb-1">Your workspace</h5><p class="text-muted">Your role controls the cases, actions, and dashboards visible in the application.</p><div class="alert alert-primary mb-0"><i class="bi bi-shield-check me-2"></i><strong><?= e(ucfirst($role)) ?></strong><br><small>Contact an administrator if your role or department assignment needs to change.</small></div></div></div>
  <?php endif; ?>
  </div>
</div>
<div class="d-flex justify-content-end mt-4"><button class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>Save settings</button></div>
</form>
</div>
</main>
</div>
</body>
</html>
