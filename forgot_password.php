<?php
require_once 'config.php';
require_once 'includes/security.php';
require_once 'includes/email_helper.php';

$message = '';
$error = '';

function password_reset_base_url(): string
{
    $configured = trim((string)(getenv('APP_BASE_URL') ?: ''));
    if ($configured !== '') return rtrim($configured, '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) $scheme = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]);
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $directory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/.');
    return $scheme . '://' . $host . ($directory ? '/' . ltrim($directory, '/') : '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid account email address.');

        $stmt = $conn->prepare('SELECT id, full_name, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        if (!$stmt) throw new RuntimeException('Unable to prepare the recovery request.');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Keep the response generic, but avoid accumulating active reset links.
        if ($user) {
            $cleanup = $conn->prepare('DELETE FROM password_resets WHERE user_id = ? OR expires_at <= NOW()');
            if (!$cleanup) throw new RuntimeException('Unable to prepare token cleanup.');
            $cleanup->bind_param('i', $user['id']);
            if (!$cleanup->execute()) throw new RuntimeException('Unable to clean up previous reset requests.');
            $cleanup->close();

            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $stmt = $conn->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
            if (!$stmt) throw new RuntimeException('Unable to create the reset request.');
            $stmt->bind_param('iss', $user['id'], $hash, $expires);
            if (!$stmt->execute()) throw new RuntimeException('Unable to save the reset request.');
            $stmt->close();

            $link = password_reset_base_url() . '/reset_password.php?token=' . rawurlencode($token);
            $body = "Hello {$user['full_name']},\n\nUse this link within one hour to reset your CampusResolve password:\n{$link}\n\nIf you did not request this, ignore this message.";
            send_email_notification($conn, (int)$user['id'], null, $user['email'], 'CampusResolve password reset request', $body);
            audit_log($conn, 'request_password_reset', 'user', (int)$user['id'], 'Password reset requested');
        } else {
            // Remove expired tokens even for unknown addresses without revealing account existence.
            $conn->query('DELETE FROM password_resets WHERE expires_at <= NOW()');
        }
        $message = 'If an active account matches that email, a one-hour reset link has been sent. In local mode, check logs/emails.log.';
    } catch (Throwable $exception) {
        $error = 'We could not create a reset request right now. Please try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot Password | CampusResolve</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5"><div class="card border-0 shadow-sm mx-auto" style="max-width:520px"><div class="card-body p-4 p-md-5">
<div class="text-center mb-4"><div class="rounded-circle bg-primary-subtle text-primary d-inline-flex p-3 mb-3"><i class="bi bi-shield-lock fs-3"></i></div><h3 class="mb-2">Recover your password</h3><p class="text-muted mb-0">Enter your account email and we will send a secure, one-hour reset link.</p></div>
<?php if ($message): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?></div><?php endif; ?>
<form method="post" novalidate><?= csrf_field() ?><div class="mb-3"><label class="form-label fw-semibold" for="email">Account email</label><input class="form-control form-control-lg" id="email" type="email" name="email" autocomplete="email" required></div><button class="btn btn-primary btn-lg w-100"><i class="bi bi-envelope me-2"></i>Send reset link</button></form>
<div class="text-center mt-4"><a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to login</a></div>
</div></div></main>
</body></html>
