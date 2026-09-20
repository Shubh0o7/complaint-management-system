<?php
$page_titles = [
    'dashboard.php' => ['My overview', 'Your complaint activity at a glance'],
    'add_complaint.php' => ['New complaint', 'Tell us what needs attention'],
    'complaints.php' => ['My complaints', 'Track every request in one place'],
    'admin_dashboard.php' => ['Operations overview', 'Monitor the campus resolution pipeline'],
    'admin_complaints.php' => ['Manage complaints', 'Review, route, and resolve incoming cases'],
    'admin_users.php' => ['Users & roles', 'Manage access across the portal'],
    'admin_assignments.php' => ['Workflow & accounts', 'Keep ownership and escalation clear'],
    'reports.php' => ['Reports & analytics', 'Turn resolution activity into insight'],
    'admin_audit.php' => ['Audit trail', 'Review every important system action'],
    'department_dashboard.php' => ['Department queue', 'Prioritize the cases assigned to your team'],
    'officer_dashboard.php' => ['Assigned cases', 'Work through your active resolution queue'],
    'notifications.php' => ['Notifications', 'Stay current on complaint activity'],
    'profile.php' => ['My profile', 'Keep your account details up to date'],
    'settings.php' => ['Settings', 'Control your portal preferences']
];
[$page_title, $page_subtitle] = $page_titles[basename($_SERVER['PHP_SELF'])] ?? ['CampusResolve', 'Student grievance portal'];
$top_initial = strtoupper(substr(trim($_SESSION['user_name'] ?? 'U'), 0, 1));
?>
<header class="app-topbar">
    <div class="topbar-heading">
        <button class="mobile-menu" type="button" aria-label="Open navigation" aria-controls="primary-navigation" aria-expanded="false" data-sidebar-toggle><i class="bi bi-list"></i></button>
        <div>
            <p class="topbar-eyebrow">CampusResolve workspace</p>
            <h1><?= htmlspecialchars($page_title) ?></h1>
            <p class="topbar-subtitle"><?= htmlspecialchars($page_subtitle) ?></p>
        </div>
    </div>
    <div class="topbar-actions">
        <a href="notifications.php" class="topbar-notifications" aria-label="View notifications"><i class="bi bi-bell"></i><?php if (!empty($unread_count)): ?><span><?= $unread_count > 99 ? '99+' : $unread_count ?></span><?php endif; ?></a>
        <div class="topbar-user"><span class="topbar-avatar"><?= htmlspecialchars($top_initial) ?></span><span class="topbar-user-copy"><strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></strong><small><?= htmlspecialchars(ucfirst($_SESSION['user_role'] ?? 'user')) ?></small></span></div>
    </div>
</header>
<?php unset($page_title, $page_subtitle, $top_initial); ?>
