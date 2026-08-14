<?php
/**
 * Notification Helper - Phase 3
 * Complaint Management System
 * 
 * Handles in-app notifications and timeline entries.
 */

/**
 * Create an in-app notification
 * @param mysqli $conn Database connection
 * @param int $user_id Recipient user ID
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $type Type: status_change, comment, assignment, system
 * @param int|null $complaint_id Related complaint ID
 * @return bool Success status
 */
function create_notification($conn, $user_id, $title, $message, $type = 'system', $complaint_id = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, complaint_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('iisss', $user_id, $complaint_id, $title, $message, $type);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Add a timeline entry for a complaint
 * @param mysqli $conn Database connection
 * @param int $complaint_id Complaint ID
 * @param int $user_id User who performed the action
 * @param string $action Action type (e.g., 'status_change', 'comment_added', 'file_uploaded', 'created')
 * @param string|null $old_value Previous value
 * @param string|null $new_value New value
 * @param string|null $description Additional description
 * @return bool Success status
 */
function add_timeline_entry($conn, $complaint_id, $user_id, $action, $old_value = null, $new_value = null, $description = null) {
    $stmt = $conn->prepare("INSERT INTO complaint_timeline (complaint_id, user_id, action, old_value, new_value, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iissss', $complaint_id, $user_id, $action, $old_value, $new_value, $description);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get unread notification count for a user
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return int Unread count
 */
function get_unread_notification_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$result['cnt'];
}

/**
 * Get notifications for a user
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $limit Number of notifications to fetch
 * @param int $offset Offset for pagination
 * @return array Notifications
 */
function get_notifications($conn, $user_id, $limit = 20, $offset = 0) {
    $stmt = $conn->prepare("SELECT n.*, c.subject as complaint_subject FROM notifications n LEFT JOIN complaints c ON n.complaint_id = c.id WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('iii', $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    return $notifications;
}

/**
 * Mark a notification as read
 * @param mysqli $conn Database connection
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for security)
 * @return bool Success status
 */
function mark_notification_read($conn, $notification_id, $user_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $notification_id, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Mark all notifications as read for a user
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return bool Success status
 */
function mark_all_notifications_read($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param('i', $user_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get timeline entries for a complaint
 * @param mysqli $conn Database connection
 * @param int $complaint_id Complaint ID
 * @return array Timeline entries
 */
function get_complaint_timeline($conn, $complaint_id) {
    $stmt = $conn->prepare("SELECT t.*, u.full_name, u.role FROM complaint_timeline t JOIN users u ON t.user_id = u.id WHERE t.complaint_id = ? ORDER BY t.created_at ASC");
    $stmt->bind_param('i', $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $timeline = [];
    while ($row = $result->fetch_assoc()) {
        $timeline[] = $row;
    }
    $stmt->close();
    return $timeline;
}

/**
 * Get timeline icon and color based on action type
 * @param string $action Action type
 * @return array ['icon' => '...', 'color' => '...']
 */
function get_timeline_style($action) {
    return match($action) {
        'created' => ['icon' => 'bi-plus-circle-fill', 'color' => 'primary'],
        'status_change' => ['icon' => 'bi-arrow-repeat', 'color' => 'info'],
        'comment_added' => ['icon' => 'bi-chat-dots-fill', 'color' => 'success'],
        'file_uploaded' => ['icon' => 'bi-paperclip', 'color' => 'warning'],
        'assigned' => ['icon' => 'bi-person-check-fill', 'color' => 'purple'],
        'resolved' => ['icon' => 'bi-check-circle-fill', 'color' => 'success'],
        'rejected' => ['icon' => 'bi-x-circle-fill', 'color' => 'danger'],
        default => ['icon' => 'bi-circle-fill', 'color' => 'secondary']
    };
}

/**
 * Notify complaint owner about status change
 */
function notify_status_change($conn, $complaint_id, $user_id, $old_status, $new_status, $admin_name) {
    // Get complaint details
    $stmt = $conn->prepare("SELECT c.subject, c.user_id, u.full_name, u.email FROM complaints c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->bind_param('i', $complaint_id);
    $stmt->execute();
    $complaint = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$complaint) return false;

    // Add timeline entry
    add_timeline_entry($conn, $complaint_id, $user_id, 'status_change', $old_status, $new_status, "Status changed by $admin_name");

    // Create in-app notification for complaint owner
    $title = "Status Updated: " . $complaint['subject'];
    $message = "Your complaint status has been changed from '$old_status' to '$new_status' by $admin_name.";
    create_notification($conn, $complaint['user_id'], $title, $message, 'status_change', $complaint_id);

    // Send email notification
    require_once __DIR__ . '/email_helper.php';
    send_status_change_email(
        $complaint['email'],
        $complaint['full_name'],
        $complaint['subject'],
        $old_status,
        $new_status,
        $complaint_id
    );

    return true;
}

/**
 * Notify about new comment
 */
function notify_new_comment($conn, $complaint_id, $commenter_id, $commenter_name, $comment_text, $is_admin) {
    // Get complaint details
    $stmt = $conn->prepare("SELECT c.subject, c.user_id, u.full_name, u.email FROM complaints c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->bind_param('i', $complaint_id);
    $stmt->execute();
    $complaint = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$complaint) return false;

    // Add timeline entry
    add_timeline_entry($conn, $complaint_id, $commenter_id, 'comment_added', null, null, "Comment by $commenter_name");

    // Notify the other party (if admin commented, notify user; if user commented, notify admins)
    if ($is_admin) {
        // Notify complaint owner
        $title = "New Reply: " . $complaint['subject'];
        $message = "$commenter_name replied to your complaint: \"" . substr($comment_text, 0, 100) . "...\"";
        create_notification($conn, $complaint['user_id'], $title, $message, 'comment', $complaint_id);

        // Send email
        require_once __DIR__ . '/email_helper.php';
        send_comment_email($complaint['email'], $complaint['full_name'], $complaint['subject'], $commenter_name, $comment_text, $complaint_id);
    } else {
        // Notify all admins
        $admins = $conn->query("SELECT id FROM users WHERE role = 'admin'");
        while ($admin = $admins->fetch_assoc()) {
            $title = "New Comment: " . $complaint['subject'];
            $message = "$commenter_name commented on complaint #$complaint_id: \"" . substr($comment_text, 0, 100) . "...\"";
            create_notification($conn, $admin['id'], $title, $message, 'comment', $complaint_id);
        }
    }

    return true;
}
?>