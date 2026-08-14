<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/notification_helper.php';

// Handle mark as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    mark_notification_read($conn, (int)$_GET['mark_read'], $_SESSION['user_id']);
    header('Location: notifications.php');
    exit();
}

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    mark_all_notifications_read($conn, $_SESSION['user_id']);
    header('Location: notifications.php');
    exit();
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get notifications
$notifications = get_notifications($conn, $_SESSION['user_id'], $per_page, $offset);
$unread_count = get_unread_notification_count($conn, $_SESSION['user_id']);

// Get total count for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$total_pages = ceil($total / $per_page);

// Notification icon helper
function get_notification_icon($type) {
    return match($type) {
        'status_change' => '<i class="bi bi-arrow-repeat text-info"></i>',
        'comment' => '<i class="bi bi-chat-dots-fill text-success"></i>',
        'assignment' => '<i class="bi bi-person-check-fill text-purple"></i>',
        'system' => '<i class="bi bi-gear-fill text-secondary"></i>',
        default => '<i class="bi bi-bell-fill text-primary"></i>'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Complaint Management System</title>
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
                    <div>
                        <h4 class="fw-bold mb-0"><i class="bi bi-bell me-2"></i>Notifications</h4>
                        <?php if ($unread_count > 0): ?>
                        <small class="text-muted"><?= $unread_count ?> unread notification<?= $unread_count > 1 ? 's' : '' ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if ($unread_count > 0): ?>
                    <a href="notifications.php?mark_all_read=1" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-check-all me-1"></i>Mark All as Read
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($notifications)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-bell-slash display-4 text-muted"></i>
                        <p class="text-muted mt-3 mb-0">No notifications yet.</p>
                        <p class="text-muted small">You'll be notified when there are updates to your complaints.</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3 <?= !$notif['is_read'] ? 'bg-light-blue' : '' ?>">
                            <div class="notification-icon mt-1" style="font-size: 1.2rem;">
                                <?= get_notification_icon($notif['type']) ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong class="d-block <?= !$notif['is_read'] ? 'text-dark' : 'text-muted' ?>">
                                            <?= htmlspecialchars($notif['title']) ?>
                                        </strong>
                                        <p class="mb-1 text-muted small"><?= htmlspecialchars($notif['message']) ?></p>
                                        <?php if ($notif['complaint_id']): ?>
                                        <a href="view_complaint.php?id=<?= $notif['complaint_id'] ?>" class="small text-primary">
                                            View Complaint #<?= $notif['complaint_id'] ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end ms-3" style="min-width: 100px;">
                                        <small class="text-muted d-block"><?= date('M d', strtotime($notif['created_at'])) ?></small>
                                        <small class="text-muted"><?= date('h:i A', strtotime($notif['created_at'])) ?></small>
                                        <?php if (!$notif['is_read']): ?>
                                        <a href="notifications.php?mark_read=<?= $notif['id'] ?>" class="d-block mt-1" title="Mark as read">
                                            <span class="badge bg-primary rounded-pill" style="font-size:0.6rem;">NEW</span>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>