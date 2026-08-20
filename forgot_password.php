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
<title>Recover Password | CampusResolve</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{--navy:#2F4156;--teal:#567C8D;--beige:#F5F2EB;--sky:#C8D9E6;--white:#F5F5F5;--ink:#26394d;--muted:#687787;--line:#dce5ea}
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:var(--beige);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.page{min-height:100vh;display:grid;grid-template-columns:minmax(280px,.84fr) minmax(430px,1.16fr);overflow:hidden}.brand-panel{position:relative;display:flex;flex-direction:column;justify-content:space-between;padding:48px clamp(32px,6vw,90px);background:linear-gradient(145deg,var(--navy),#38556d 56%,var(--teal));color:var(--white);overflow:hidden}.brand-panel:after,.brand-panel:before{content:"";position:absolute;border:1px solid rgba(200,217,230,.28);border-radius:50%;pointer-events:none}.brand-panel:before{width:390px;height:390px;right:-150px;top:-110px}.brand-panel:after{width:520px;height:520px;left:-280px;bottom:-250px}.brand{position:relative;z-index:1;display:flex;align-items:center;gap:12px;font-weight:800;letter-spacing:.02em}.brand-mark{width:42px;height:42px;display:grid;place-items:center;border-radius:14px;background:rgba(245,245,245,.14);border:1px solid rgba(245,245,245,.26);font-size:20px}.brand small{display:block;margin-top:2px;color:rgba(245,245,245,.7);font-size:11px;font-weight:600;letter-spacing:.13em;text-transform:uppercase}.brand-copy{position:relative;z-index:1;max-width:520px}.eyebrow{display:inline-flex;align-items:center;gap:8px;margin-bottom:20px;color:var(--sky);font-size:11px;font-weight:800;letter-spacing:.15em;text-transform:uppercase}.eyebrow i{font-size:14px}.brand-copy h1{max-width:500px;margin:0;font-size:clamp(36px,5vw,68px);line-height:.98;letter-spacing:-.06em}.brand-copy p{max-width:440px;margin:24px 0 0;color:rgba(245,245,245,.78);font-size:16px;line-height:1.65}.trust-row{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:10px;color:rgba(245,245,245,.78);font-size:12px}.trust-row span{display:inline-flex;align-items:center;gap:7px;padding:10px 12px;border:1px solid rgba(245,245,245,.18);border-radius:999px;background:rgba(245,245,245,.08)}.trust-row i{color:var(--sky)}.form-panel{display:grid;place-items:center;padding:42px clamp(24px,7vw,110px);background:linear-gradient(145deg,var(--beige),var(--white));}.form-wrap{width:min(100%,500px)}.top-link{display:inline-flex;align-items:center;gap:7px;color:var(--teal);font-size:13px;font-weight:800;text-decoration:none}.top-link:hover{text-decoration:underline}.form-card{margin-top:28px;padding:clamp(28px,4vw,44px);border:1px solid rgba(86,124,141,.18);border-radius:28px;background:rgba(245,245,245,.88);box-shadow:0 24px 70px rgba(47,65,86,.13);backdrop-filter:blur(12px)}.form-card:before{content:"";display:block;width:58px;height:5px;margin-bottom:26px;border-radius:10px;background:linear-gradient(90deg,var(--teal),var(--sky))}.form-card h2{margin:0;color:var(--navy);font-size:34px;letter-spacing:-.04em}.form-card .intro{margin:11px 0 28px;color:var(--muted);line-height:1.6}.field-label{display:block;margin:0 0 8px;color:var(--navy);font-size:12px;font-weight:850;letter-spacing:.1em;text-transform:uppercase}.field{width:100%;height:54px;padding:0 16px;border:1px solid var(--line);border-radius:14px;background:var(--white);color:var(--ink);font:inherit;outline:none}.field:focus{border-color:var(--teal);box-shadow:0 0 0 4px rgba(86,124,141,.16)}.primary-btn{width:100%;height:54px;margin-top:20px;border:0;border-radius:14px;background:var(--navy);color:var(--white);font:inherit;font-weight:800;cursor:pointer;box-shadow:0 12px 22px rgba(47,65,86,.2)}.primary-btn:hover{background:var(--teal)}.alert{display:flex;gap:10px;align-items:flex-start;margin:0 0 22px;padding:14px 15px;border-radius:14px;font-size:13px;line-height:1.5}.alert.success{border:1px solid #a9c9bc;background:#edf7f1;color:#245c48}.alert.error{border:1px solid #e4b7b0;background:#fff1ef;color:#8c3f35}.back-link{display:flex;justify-content:center;align-items:center;gap:7px;margin-top:24px;color:var(--teal);font-size:13px;font-weight:800;text-decoration:none}.back-link:hover{text-decoration:underline}.local-note{display:flex;gap:8px;margin-top:22px;color:var(--muted);font-size:12px;line-height:1.5}.local-note i{color:var(--teal)}
@media(max-width:850px){.page{grid-template-columns:1fr}.brand-panel{min-height:360px;padding:28px 26px 34px}.brand-copy{margin-top:54px}.brand-copy h1{font-size:44px}.trust-row{display:none}.form-panel{padding:30px 20px 46px}.form-card{margin-top:20px}}
@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important}}
</style>
</head>
<body>
<div class="page">
<section class="brand-panel"><div class="brand"><span class="brand-mark"><i class="bi bi-shield-check"></i></span><span>CampusResolve<small>Student Grievance Portal</small></span></div><div class="brand-copy"><div class="eyebrow"><i class="bi bi-lock"></i> Secure account recovery</div><h1>Get back to resolving what matters.</h1><p>Recover your account securely and return to a transparent complaint workflow built for students, departments, and campus teams.</p></div><div class="trust-row"><span><i class="bi bi-clock-history"></i> One-hour reset link</span><span><i class="bi bi-shield-lock"></i> Secure token flow</span></div></section>
<section class="form-panel"><div class="form-wrap"><a class="top-link" href="login.php"><i class="bi bi-arrow-left"></i> Back to sign in</a><div class="form-card"><h2>Recover password</h2><p class="intro">Enter the email linked to your account. We’ll send a secure reset link if an active account matches.</p><?php if ($message): ?><div class="alert success"><i class="bi bi-check-circle-fill"></i><span><?= e($message) ?></span></div><?php endif; ?><?php if ($error): ?><div class="alert error"><i class="bi bi-exclamation-triangle-fill"></i><span><?= e($error) ?></span></div><?php endif; ?><form method="post" novalidate><?= csrf_field() ?><label class="field-label" for="email">Account email</label><input class="field" id="email" type="email" name="email" autocomplete="email" placeholder="you@campus.edu" required><button class="primary-btn" type="submit"><i class="bi bi-envelope me-2"></i>Send reset link</button></form><p class="local-note"><i class="bi bi-info-circle"></i><span>In local development mode, the generated link is recorded in <strong>logs/emails.log</strong>.</span></p><a class="back-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Return to login</a></div></div></section>
</div>
</body>
</html>
