<?php
require_once 'config.php';
require_once 'includes/admin_check.php';

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    // Prevent admin from demoting themselves
    if ($user_id === (int)$_SESSION['user_id']) {
        $_SESSION['flash_msg'] = "You cannot change your own role.";
        $_SESSION['flash_type'] = 'warning';
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param('si', $new_role, $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_msg'] = "User role updated successfully.";
        $_SESSION['flash_type'] = 'success';
    }
    header('Location: admin_users.php');
    exit();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    if ($user_id === (int)$_SESSION['user_id']) {
        $_SESSION['flash_msg'] = "You cannot delete your own account.";
        $_SESSION['flash_type'] = 'warning';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_msg'] = "User deleted successfully.";
        $_SESSION['flash_type'] = 'success';
    }
    header('Location: admin_users.php');
    exit();
}

// Search
$search = trim($_GET['search'] ?? '');
$where_sql = '';
$params = [];
$types = '';

if ($search) {
    $where_sql = "WHERE full_name LIKE ? OR email LIKE ?";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types = 'ss';
}

$sql = "SELECT u.*, (SELECT COUNT(*) FROM complaints WHERE user_id = u.id) as complaint_count FROM users u $where_sql ORDER BY u.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="flex-grow-1">
            <?php include 'includes/topbar.php'; ?>
            <main class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Manage Users</h4>
                    <span class="badge bg-primary fs-6"><?= count($users) ?> users</span>
                </div>

                <?php if (isset($_SESSION['flash_msg'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'success' ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['flash_msg'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); endif; ?>

                <!-- Search -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Search Users</label>
                                <input type="text" name="search" class="form-control" placeholder="Name or email..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search me-1"></i>Search</button>
                                <a href="admin_users.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Complaints</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= $u['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:35px;height:35px;font-size:0.9rem;">
                                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                                </div>
                                                <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td>
                                            <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                                <?= ucfirst($u['role']) ?>
                                            </span>
                                        </td>
                                        <td><span class="badge bg-light text-dark"><?= $u['complaint_count'] ?></span></td>
                                        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                        <td>
                                            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="new_role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                                                    <button type="submit" name="update_role" class="btn btn-outline-warning" title="Toggle Role">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user? All their complaints will also be deleted.')">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-outline-danger" title="Delete User">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted"><small>Current</small></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>