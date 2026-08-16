<?php
require_once __DIR__ . '/auth_check.php';

function require_role(array $roles): void {
    $role = $_SESSION['user_role'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        $home = ['admin' => 'admin_dashboard.php', 'department' => 'department_dashboard.php', 'officer' => 'officer_dashboard.php'][$role] ?? 'dashboard.php';
        header('Location: ' . $home . '?error=unauthorized');
        exit;
    }
}

function role_home(string $role): string {
    return ['admin' => 'admin_dashboard.php', 'department' => 'department_dashboard.php', 'officer' => 'officer_dashboard.php', 'user' => 'dashboard.php'][$role] ?? 'dashboard.php';
}

function role_label(string $role): string {
    return ['user' => 'Complainant', 'admin' => 'Administrator', 'department' => 'Department Manager', 'officer' => 'Complaint Officer'][$role] ?? ucfirst($role);
}

function status_badge_class(string $status): string {
    return ['Pending' => 'warning', 'In Progress' => 'info', 'Resolved' => 'success', 'Rejected' => 'danger'][$status] ?? 'secondary';
}

function priority_badge_class(string $priority): string {
    return ['Low' => 'secondary', 'Medium' => 'primary', 'High' => 'warning', 'Critical' => 'danger'][$priority] ?? 'secondary';
}
?>
