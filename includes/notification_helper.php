<?php
require_once __DIR__ . '/notification_queue.php';

/**
 * Notification Helper Functions
 * Handles creation and management of in-app notifications
 */

/**
 * Create a notification for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id Target user ID
 * @param int $complaint_id Related complaint ID (optional)
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $type Notification type (status_change, comment, assignment, system)
 */
function create_notification($conn, $user_id, $complaint_id, $title, $message, $type = 'system') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, complaint_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
    
    if ($stmt) {
        $stmt->bind_param('iisss', $user_id, $complaint_id, $title, $message, $type);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

/**
 * Get unread notifications for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $limit Number of notifications to retrieve
 * @return array Array of notifications
 */
function notify_complaint_status_change(mysqli $conn, int $complaintId, string $oldStatus, string $newStatus, string $remarks = ''): array
{
    $stmt = $conn->prepare('SELECT c.reference_no, c.subject, c.user_id, u.full_name, u.email FROM complaints c JOIN users u ON u.id = c.user_id WHERE c.id = ? LIMIT 1');
    if (!$stmt) return ['in_app' => false, 'email' => false, 'push' => 0, 'queued' => 0];
    $stmt->bind_param('i', $complaintId);
    $stmt->execute();
    $complaint = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$complaint) return ['in_app' => false, 'email' => false, 'push' => 0, 'queued' => 0];

    $userId = (int)$complaint['user_id'];
    $message = 'Your complaint ' . ($complaint['reference_no'] ?: ('#' . $complaintId)) . ' status changed from ' . $oldStatus . ' to ' . $newStatus . '.';
    if ($remarks !== '') $message .= ' ' . $remarks;
    $inApp = create_notification($conn, $userId, $complaintId, 'Complaint Status Updated', $message, 'status_change');

    $emailEnabled = true; $pushEnabled = true; $digest = 'instant';
    $pref = $conn->prepare('SELECT email_notifications, push_notifications, notification_digest FROM user_preferences WHERE user_id = ? LIMIT 1');
    if ($pref) {
        $pref->bind_param('i', $userId); $pref->execute(); $row = $pref->get_result()->fetch_assoc(); $pref->close();
        if ($row) { $emailEnabled = (bool)$row['email_notifications']; $pushEnabled = (bool)$row['push_notifications']; $digest = (string)$row['notification_digest']; }
    }

    $queued = 0;
    if ($emailEnabled && $digest === 'instant' && !empty($complaint['email'])) {
        $queued += queue_notification($conn, 'email', $userId, $complaintId, $complaint['email'], 'Complaint Status Update: ' . $complaint['subject'], "Hello {$complaint['full_name']},\n\n{$message}\n\nPlease sign in to view the latest details.") ? 1 : 0;
    }
    if ($pushEnabled) {
        $queued += queue_notification($conn, 'push', $userId, $complaintId, '', 'Complaint status updated', $message, ['url' => 'view_complaint.php?id=' . $complaintId]) ? 1 : 0;
    }
    return ['in_app' => $inApp, 'email' => false, 'push' => 0, 'queued' => $queued];
}

function get_unread_notifications($conn, $user_id, $limit = 10) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
    
    if ($stmt) {
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $notifications;
    }
    return [];
}

/**
 * Get unread notification count
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return int Count of unread notifications
 */
function get_unread_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['cnt'] ?? 0;
    }
    return 0;
}

/**
 * Get all notifications for a user (paginated)
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $page Page number
 * @param int $per_page Items per page
 * @return array Array containing notifications and total count
 */
function get_all_notifications($conn, $user_id, $page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    
    if ($stmt) {
        $stmt->bind_param('iii', $user_id, $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get total count
        $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ?");
        $count_stmt->bind_param('i', $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $count_stmt->close();
        
        return [
            'notifications' => $notifications,
            'total' => $count_row['cnt'] ?? 0,
            'pages' => ceil(($count_row['cnt'] ?? 0) / $per_page)
        ];
    }
    return ['notifications' => [], 'total' => 0, 'pages' => 0];
}

/**
 * Mark all notifications as read for a user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return bool Success status
 */
function mark_all_as_read($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}
?>
<?php
function mark_notification_read(mysqli $conn, int $notification_id, int $user_id): bool {
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    if (!$stmt) return false;
    $stmt->bind_param('ii', $notification_id, $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function mark_all_notifications_read(mysqli $conn, int $user_id): bool {
    return mark_all_as_read($conn, $user_id);
}

function get_notifications(mysqli $conn, int $user_id, int $limit = 20, int $offset = 0): array {
    $stmt = $conn->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
    if (!$stmt) return [];
    $stmt->bind_param('iii', $user_id, $limit, $offset);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function get_unread_notification_count(mysqli $conn, int $user_id): int {
    return get_unread_count($conn, $user_id);
}
