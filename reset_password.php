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
        if ($newPassword !== $confirmPassword) throw new RuntimeException('Passwords do not match.');

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
        if (session_status() === PHP_SESSION_ACTIVE) { session_unset(); session_destroy(); }
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{--navy:#2F4156;--teal:#567C8D;--beige:#F5F2EB;--sky:#C8D9E6;--white:#F5F5F5;--ink:#26394d;--muted:#687787;--line:#dce5ea}
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:var(--beige);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.page{min-height:100vh;display:grid;grid-template-columns:minmax(280px,.84fr) minmax(430px,1.16fr);overflow:hidden}.brand-panel{position:relative;display:flex;flex-direction:column;justify-content:space-between;padding:48px clamp(32px,6vw,90px);background:linear-gradient(145deg,var(--navy),#38556d 56%,var(--teal));color:var(--white);overflow:hidden}.brand-panel:after,.brand-panel:before{content:"";position:absolute;border:1px solid rgba(200,217,230,.28);border-radius:50%;pointer-events:none}.brand-panel:before{width:390px;height:390px;right:-150px;top:-110px}.brand-panel:after{width:520px;height:520px;left:-280px;bottom:-250px}.brand{position:relative;z-index:1;display:flex;align-items:center;gap:12px;font-weight:800;letter-spacing:.02em}.brand-mark{width:42px;height:42px;display:grid;place-items:center;border-radius:14px;background:rgba(245,245,245,.14);border:1px solid rgba(245,245,245,.26);font-size:20px}.brand small{display:block;margin-top:2px;color:rgba(245,245,245,.7);font-size:11px;font-weight:600;letter-spacing:.13em;text-transform:uppercase}.brand-copy{position:relative;z-index:1;max-width:520px}.eyebrow{display:inline-flex;align-items:center;gap:8px;margin-bottom:20px;color:var(--sky);font-size:11px;font-weight:800;letter-spacing:.15em;text-transform:uppercase}.brand-copy h1{max-width:500px;margin:0;font-size:clamp(36px,5vw,68px);line-height:.98;letter-spacing:-.06em}.brand-copy p{max-width:440px;margin:24px 0 0;color:rgba(245,245,245,.78);font-size:16px;line-height:1.65}.trust-row{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:10px;color:rgba(245,245,245,.78);font-size:12px}.trust-row span{display:inline-flex;align-items:center;gap:7px;padding:10px 12px;border:1px solid rgba(245,245,245,.18);border-radius:999px;background:rgba(245,245,245,.08)}.trust-row i{color:var(--sky)}.form-panel{display:grid;place-items:center;padding:42px clamp(24px,7vw,110px);background:linear-gradient(145deg,var(--beige),var(--white));}.form-wrap{width:min(100%,500px)}.top-link{display:inline-flex;align-items:center;gap:7px;color:var(--teal);font-size:13px;font-weight:800;text-decoration:none}.top-link:hover{text-decoration:underline}.form-card{margin-top:28px;padding:clamp(28px,4vw,44px);border:1px solid rgba(86,124,141,.18);border-radius:28px;background:rgba(245,245,245,.88);box-shadow:0 24px 70px rgba(47,65,86,.13);backdrop-filter:blur(12px)}.form-card:before{content:"";display:block;width:58px;height:5px;margin-bottom:26px;border-radius:10px;background:linear-gradient(90deg,var(--teal),var(--sky))}.form-card h2{margin:0;color:var(--navy);font-size:34px;letter-spacing:-.04em}.form-card .intro{margin:11px 0 28px;color:var(--muted);line-height:1.6}.field-label{display:block;margin:0 0 8px;color:var(--navy);font-size:12px;font-weight:850;letter-spacing:.1em;text-transform:uppercase}.field{width:100%;height:54px;padding:0 16px;border:1px solid var(--line);border-radius:14px;background:var(--white);color:var(--ink);font:inherit;outline:none}.field:focus{border-color:var(--teal);box-shadow:0 0 0 4px rgba(86,124,141,.16)}.password-field{position:relative}.password-field .field{padding-right:54px}.password-toggle{position:absolute;right:9px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:var(--teal);padding:8px;line-height:1;cursor:pointer}.password-toggle:hover,.password-toggle:focus-visible{color:var(--navy)}.password-toggle:focus-visible{outline:2px solid var(--teal);outline-offset:2px;border-radius:6px}.primary-btn{width:100%;height:54px;margin-top:20px;border:0;border-radius:14px;background:var(--navy);color:var(--white);font:inherit;font-weight:800;cursor:pointer;box-shadow:0 12px 22px rgba(47,65,86,.2)}.primary-btn:hover{background:var(--teal)}.alert{display:flex;gap:10px;align-items:flex-start;margin:0 0 22px;padding:14px 15px;border-radius:14px;font-size:13px;line-height:1.5}.alert.success{border:1px solid #a9c9bc;background:#edf7f1;color:#245c48}.alert.error{border:1px solid #e4b7b0;background:#fff1ef;color:#8c3f35}.alert.warning{border:1px solid #d9c494;background:#fff8e7;color:#725a1a}.requirements{display:grid;gap:9px;margin:18px 0 2px;padding:14px;border-radius:14px;background:rgba(200,217,230,.3);color:var(--muted);font-size:12px}.requirements span{display:flex;align-items:center;gap:8px}.requirements i{color:var(--teal)}.back-link{display:flex;justify-content:center;align-items:center;gap:7px;margin-top:24px;color:var(--teal);font-size:13px;font-weight:800;text-decoration:none}.back-link:hover{text-decoration:underline}
@media(max-width:850px){.page{grid-template-columns:1fr}.brand-panel{min-height:360px;padding:28px 26px 34px}.brand-copy{margin-top:54px}.brand-copy h1{font-size:44px}.trust-row{display:none}.form-panel{padding:30px 20px 46px}.form-card{margin-top:20px}}
</style>
</head>
<body>
<div class="page">
<section class="brand-panel"><div class="brand"><span class="brand-mark"><i class="bi bi-shield-check"></i></span><span>CampusResolve<small>Student Grievance Portal</small></span></div><div class="brand-copy"><div class="eyebrow"><i class="bi bi-key"></i> Secure credential update</div><h1>A stronger password. A clearer next step.</h1><p>Choose a new password and return to the CampusResolve workflow with your account protected.</p></div><div class="trust-row"><span><i class="bi bi-shield-lock"></i> Encrypted password storage</span><span><i class="bi bi-clock-history"></i> Single-use reset link</span></div></section>
<section class="form-panel"><div class="form-wrap"><a class="top-link" href="login.php"><i class="bi bi-arrow-left"></i> Back to sign in</a><div class="form-card"><h2>Set a new password</h2><p class="intro">Create a strong password using at least 8 characters, one letter, and one number.</p><?php if ($message): ?><div class="alert success"><i class="bi bi-check-circle-fill"></i><span><?= e($message) ?></span></div><a class="primary-btn" style="display:flex;align-items:center;justify-content:center;text-decoration:none" href="login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Go to login</a><?php elseif ($error): ?><div class="alert error"><i class="bi bi-exclamation-triangle-fill"></i><span><?= e($error) ?></span></div><a class="primary-btn" style="display:flex;align-items:center;justify-content:center;text-decoration:none" href="forgot_password.php"><i class="bi bi-envelope me-2"></i>Request a new link</a><?php elseif ($canShowForm): ?><form method="post" novalidate><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><label class="field-label" for="new_password">New password</label><div class="password-field"><input class="field" id="new_password" type="password" name="new_password" autocomplete="new-password" minlength="8" required><button type="button" class="password-toggle" data-password-toggle="new_password" aria-label="Show password" aria-pressed="false" title="Show password"><i class="bi bi-eye"></i></button></div><label class="field-label" for="confirm_password" style="margin-top:18px">Confirm new password</label><div class="password-field"><input class="field" id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" minlength="8" required><button type="button" class="password-toggle" data-password-toggle="confirm_password" aria-label="Show password" aria-pressed="false" title="Show password"><i class="bi bi-eye"></i></button></div><div class="requirements"><span><i class="bi bi-check2-circle"></i> At least 8 characters</span><span><i class="bi bi-check2-circle"></i> Includes a letter and a number</span></div><button class="primary-btn" type="submit"><i class="bi bi-check2-circle me-2"></i>Reset password</button></form><?php else: ?><div class="alert warning"><i class="bi bi-info-circle-fill"></i><span>Open the reset link from your email to continue.</span></div><a class="primary-btn" style="display:flex;align-items:center;justify-content:center;text-decoration:none" href="forgot_password.php"><i class="bi bi-envelope me-2"></i>Request a reset link</a><?php endif; ?><a class="back-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Return to login</a></div></div></section>
</div>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(function(toggle) {
    toggle.addEventListener('click', function() {
        const input = document.getElementById(toggle.dataset.passwordToggle);
        const isVisible = input.type === 'text';
        input.type = isVisible ? 'password' : 'text';
        toggle.setAttribute('aria-pressed', String(!isVisible));
        toggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        toggle.title = isVisible ? 'Show password' : 'Hide password';
        toggle.innerHTML = `<i class="bi bi-eye${isVisible ? '' : '-slash'}"></i>`;
    });
});
</script>
</body>
</html>
