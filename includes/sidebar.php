<?php
// Get unread notification count for badge
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $notif_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($notif_stmt) {
        $notif_stmt->bind_param('i', $_SESSION['user_id']);
        $notif_stmt->execute();
        $notif_result = $notif_stmt->get_result()->fetch_assoc();
        $unread_count = (int) ($notif_result['cnt'] ?? 0);
        $notif_stmt->close();
    }
}
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'] ?? 'user';
$role_label = ['admin' => 'Administrator', 'department' => 'Department team', 'officer' => 'Complaint officer', 'user' => 'Student'][$role] ?? ucfirst($role);
$initial = strtoupper(substr(trim($_SESSION['user_name'] ?? 'U'), 0, 1));
function sidebar_link(string $page, string $icon, string $label, string $current_page, ?int $badge = null): void {
    $active = $page === $current_page ? ' active' : '';
    echo '<li class="nav-item"><a class="nav-link' . $active . '" href="' . htmlspecialchars($page) . '"><i class="bi ' . htmlspecialchars($icon) . '"></i><span>' . htmlspecialchars($label) . '</span>';
    if ($badge !== null && $badge > 0) echo '<span class="nav-count">' . ($badge > 99 ? '99+' : $badge) . '</span>';
    echo '</a></li>';
}
?>
<nav id="primary-navigation" class="sidebar" aria-label="Primary navigation">
    <div class="sidebar-brand">
        <a href="<?= htmlspecialchars(role_home($role)) ?>" class="brand-lockup">
            <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
            <span><strong>CampusResolve</strong><small>Student grievance portal</small></span>
        </a>
    </div>
    <div class="sidebar-label">Workspace</div>
    <ul class="nav flex-column sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <?php sidebar_link('admin_dashboard.php', 'bi-grid-1x2', 'Overview', $current_page); ?>
            <?php sidebar_link('admin_complaints.php', 'bi-kanban', 'Manage complaints', $current_page); ?>
            <?php sidebar_link('admin_users.php', 'bi-people', 'Users & roles', $current_page); ?>
            <?php sidebar_link('admin_assignments.php', 'bi-diagram-3', 'Workflow & accounts', $current_page); ?>
            <?php sidebar_link('reports.php', 'bi-bar-chart-line', 'Reports & analytics', $current_page); ?>
            <?php sidebar_link('admin_audit.php', 'bi-clipboard2-data', 'Audit trail', $current_page); ?>
        <?php elseif ($role === 'department'): ?>
            <?php sidebar_link('department_dashboard.php', 'bi-building', 'Department queue', $current_page); ?>
            <?php sidebar_link('notifications.php', 'bi-bell', 'Notifications', $current_page, $unread_count); ?>
        <?php elseif ($role === 'officer'): ?>
            <?php sidebar_link('officer_dashboard.php', 'bi-person-badge', 'Assigned cases', $current_page); ?>
            <?php sidebar_link('notifications.php', 'bi-bell', 'Notifications', $current_page, $unread_count); ?>
        <?php else: ?>
            <?php sidebar_link('dashboard.php', 'bi-grid-1x2', 'My overview', $current_page); ?>
            <?php sidebar_link('add_complaint.php', 'bi-plus-circle', 'New complaint', $current_page); ?>
            <?php sidebar_link('complaints.php', 'bi-inbox', 'My complaints', $current_page); ?>
            <?php sidebar_link('notifications.php', 'bi-bell', 'Notifications', $current_page, $unread_count); ?>
        <?php endif; ?>
    </ul>
    <div class="sidebar-spacer"></div>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <span class="sidebar-avatar"><?= htmlspecialchars($initial) ?></span>
            <span><strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></strong><small><?= htmlspecialchars($role_label) ?></small></span>
        </div>
        <div class="sidebar-footer-links">
            <a href="profile.php"><i class="bi bi-person-gear"></i> Profile</a>
            <a href="settings.php"><i class="bi bi-sliders2"></i> Settings</a>
            <a href="logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i> Sign out</a>
        </div>
    </div>
</nav>
<div class="sidebar-backdrop" data-sidebar-close></div>
<?php unset($role_label, $initial, $current_page); ?>
<!-- role_home() is provided by includes/security.php through config.php -->
