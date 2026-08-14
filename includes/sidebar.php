<?php
// Get unread notification count for badge
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $notif_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($notif_stmt) {
        $notif_stmt->bind_param('i', $_SESSION['user_id']);
        $notif_stmt->execute();
        $notif_result = $notif_stmt->get_result()->fetch_assoc();
        $unread_count = $notif_result['cnt'] ?? 0;
        $notif_stmt->close();
    }
}
?>
<!-- Sidebar Navigation -->
<nav class="sidebar bg-white shadow-sm d-flex flex-column" style="width:250px;min-height:100vh;">
    <div class="p-3 border-bottom">
        <h5 class="text-primary fw-bold mb-0">
            <i class="bi bi-shield-check me-2"></i>CMS
        </h5>
        <small class="text-muted">Complaint Management</small>
    </div>
    <ul class="nav flex-column p-3">
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <!-- Admin Navigation -->
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active bg-dark text-white' : 'text-dark' ?>" href="admin_dashboard.php">
                <i class="bi bi-shield-lock me-2"></i> Admin Dashboard
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'admin_complaints.php' ? 'active bg-dark text-white' : 'text-dark' ?>" href="admin_complaints.php">
                <i class="bi bi-kanban me-2"></i> Manage Complaints
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'admin_users.php' ? 'active bg-dark text-white' : 'text-dark' ?>" href="admin_users.php">
                <i class="bi bi-people me-2"></i> Manage Users
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active bg-dark text-white' : 'text-dark' ?>" href="reports.php">
                <i class="bi bi-graph-up me-2"></i> Reports & Analytics
            </a>
        </li>
        <li class="nav-item mb-2"><hr class="my-1"></li>
        <?php endif; ?>

        <!-- User Navigation -->
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active bg-primary text-white' : 'text-dark' ?>" href="dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'add_complaint.php' ? 'active bg-primary text-white' : 'text-dark' ?>" href="add_complaint.php">
                <i class="bi bi-plus-circle me-2"></i> New Complaint
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'complaints.php' ? 'active bg-primary text-white' : 'text-dark' ?>" href="complaints.php">
                <i class="bi bi-list-ul me-2"></i> My Complaints
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active bg-primary text-white' : 'text-dark' ?>" href="notifications.php">
                <i class="bi bi-bell me-2"></i> Notifications
                <?php if ($unread_count > 0): ?>
                <span class="badge bg-danger rounded-pill ms-1"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>
    <div class="mt-auto p-3 border-top">
        <div class="mb-2">
            <small class="text-muted">Logged in as:</small><br>
            <strong class="text-dark"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <span class="badge bg-danger ms-1">Admin</span>
            <?php endif; ?>
        </div>
        <a href="logout.php" class="btn btn-outline-danger btn-sm w-100">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
    </div>
</nav>