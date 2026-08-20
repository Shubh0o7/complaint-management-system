<?php
require_once 'config.php';
require_once 'includes/security.php';

$message = '';
$error = '';
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));

function valid_reset_token(string $token): bool
{
    return preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        if (!valid_reset_token($token)) throw new RuntimeException('This reset link is invalid or expired.');
        if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
            throw new RuntimeException('Password must be at least 8 characters and include a letter and a number.');
        }
        if (!hash_equals($newPassword, $confirmPassword)) throw new RuntimeException('Passwords do not match.');

        $hash = hash('sha256', $token);
        $conn->begin_transaction();
        $stmt = $conn->prepare('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1 FOR UPDATE');
        if (!$stmt) throw new RuntimeException('Unable to validate the reset request.');
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $reset = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$reset) throw new RuntimeException('This reset link is invalid or expired.');

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        if (!$stmt) throw new RuntimeException('Unable to update the password.');
        $stmt->bind_param('si', $passwordHash, $reset['user_id']);
        if (!$stmt->execute()) throw new RuntimeException('Unable to update the password.');
        $stmt->close();

        $stmt = $conn->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
        if (!$stmt) throw new RuntimeException('Unable to close reset tokens.');
        $stmt->bind_param('i', $reset['user_id']);
        $stmt->execute();
        $stmt->close();
        $conn->commit();

        audit_log($conn, 'reset_password', 'user', (int)$reset['user_id'], 'Password reset completed');
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $message = 'Your password has been reset successfully. You can now sign in with the new password.';
        $token = '';
    } catch (Throwable $exception) {
        try { $conn->rollback(); } catch (Throwable $ignored) { }
        $error = $exception->getMessage();
    }
}

$canShowForm = valid_reset_token($token) && $message === '' && $error === '';
if ($token !== '' && !$canShowForm && $error === '' && $message === '') $error = 'This reset link is invalid or expired.';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password | CampusResolve</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5"><div class="card border-0 shadow-sm mx-auto" style="max-width:520px"><div class="card-body p-4 p-md-5">
<div class="text-center mb-4"><div class="rounded-circle bg-primary-subtle text-primary d-inline-flex p-3 mb-3"><i class="bi bi-key fs-3"></i></div><h3 class="mb-2">Set a new password</h3><p class="text-muted mb-0">Use a strong password with at least 8 characters, one letter, and one number.</p></div>
<?php if ($message): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= e($message) ?></div><a href="login.php" class="btn btn-primary btn-lg w-100">Go to login</a><?php elseif ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?></div><a href="forgot_password.php" class="btn btn-outline-primary w-100">Request a new link</a><?php elseif ($canShowForm): ?><form method="post" novalidate><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><div class="mb-3"><label class="form-label fw-semibold" for="new_password">New password</label><input class="form-control form-control-lg" id="new_password" type="password" name="new_password" autocomplete="new-password" minlength="8" required></div><div class="mb-4"><label class="form-label fw-semibold" for="confirm_password">Confirm new password</label><input class="form-control form-control-lg" id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" minlength="8" required></div><button class="btn btn-primary btn-lg w-100"><i class="bi bi-check2-circle me-2"></i>Reset password</button></form><?php else: ?><div class="alert alert-warning">Open the reset link from your email to continue.</div><a href="forgot_password.php" class="btn btn-primary w-100">Request a reset link</a><?php endif; ?>
<div class="text-center mt-4"><a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to login</a></div>
</div></div></main>
</body></html>
