<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$message = '';
$error = '';
$userId = (int)$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();
        $name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '' || strlen($name) > 100) throw new RuntimeException('Please enter a valid name under 100 characters.');
        $stmt = $conn->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
        $stmt->bind_param('ssi', $name, $phone, $userId);
        $stmt->execute();
        $stmt->close();
        $_SESSION['user_name'] = $name;
        audit_log($conn, 'update_profile', 'user', $userId, 'Profile details updated');
        $message = 'Profile updated successfully.';
    } catch (Throwable $e) { $error = $e->getMessage(); }
}
$stmt = $conn->prepare('SELECT full_name, email, phone, role, created_at, last_login_at FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc(); $stmt->close();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Profile | Grievance Portal</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"></head><body class="bg-light"><div class="d-flex min-vh-100"><?php include 'includes/sidebar.php'; ?><main class="flex-grow-1"><header class="bg-white border-bottom p-3"><h4 class="mb-0">Profile Management</h4><small class="text-muted">Keep your contact information current.</small></header><div class="container-fluid p-4"><div class="row g-4"><div class="col-lg-7"><div class="card border-0 shadow-sm"><div class="card-body p-4"><?php if ($message): ?><div class="alert alert-success"><?=e($message)?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?><form method="post"><?=csrf_field()?><div class="mb-3"><label class="form-label">Full name</label><input class="form-control" name="full_name" value="<?=e($user['full_name'])?>" required maxlength="100"></div><div class="mb-3"><label class="form-label">Email</label><input class="form-control" value="<?=e($user['email'])?>" disabled></div><div class="mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?=e($user['phone'] ?? '')?>" maxlength="30"></div><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save profile</button></form></div></div></div><div class="col-lg-5"><div class="card border-0 shadow-sm"><div class="card-body p-4"><h5>Account summary</h5><p class="mb-2"><strong>Role:</strong> <?=e(ucfirst($user['role']))?></p><p class="mb-2"><strong>Created:</strong> <?=e($user['created_at'])?></p><p class="mb-3"><strong>Last login:</strong> <?=e($user['last_login_at'] ?? 'First login')?></p><a class="btn btn-outline-dark" href="change_password.php"><i class="bi bi-key me-1"></i>Change password</a></div></div></div></div></div></main></div></body></html>
