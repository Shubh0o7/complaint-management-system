<?php

function add_timeline_entry(mysqli $conn, int $complaint_id, int $user_id, string $action, ?string $old_value, ?string $new_value, string $description): bool {
    $stmt = $conn->prepare('INSERT INTO complaint_timeline (complaint_id, user_id, action, old_value, new_value, description) VALUES (?, ?, ?, ?, ?, ?)');
    if (!$stmt) return false;
    $stmt->bind_param('iissss', $complaint_id, $user_id, $action, $old_value, $new_value, $description);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function get_complaint_timeline(mysqli $conn, int $complaint_id): array {
    $stmt = $conn->prepare('SELECT t.*, u.full_name, u.role FROM complaint_timeline t JOIN users u ON u.id = t.user_id WHERE t.complaint_id = ? ORDER BY t.created_at ASC, t.id ASC');
    if (!$stmt) return [];
    $stmt->bind_param('i', $complaint_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function get_timeline_style(string $action): array {
    return match ($action) {
        'created' => ['icon' => 'bi-plus-circle-fill', 'color' => 'primary'],
        'status_change', 'Status Updated' => ['icon' => 'bi-arrow-repeat', 'color' => 'info'],
        'comment' => ['icon' => 'bi-chat-dots-fill', 'color' => 'success'],
        'file_uploaded' => ['icon' => 'bi-paperclip', 'color' => 'secondary'],
        'Department Assigned', 'Officer Assigned' => ['icon' => 'bi-person-check-fill', 'color' => 'dark'],
        default => ['icon' => 'bi-clock-history', 'color' => 'secondary']
    };
}

function notify_new_comment(mysqli $conn, int $complaint_id, int $commenter_id, string $commenter_name, string $comment, bool $is_staff): void {
    $stmt = $conn->prepare('SELECT c.user_id, c.subject FROM complaints c WHERE c.id = ?');
    if (!$stmt) return;
    $stmt->bind_param('i', $complaint_id);
    $stmt->execute();
    $complaint = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$complaint) return;

    $recipients = [];
    if ((int)$complaint['user_id'] !== $commenter_id) $recipients[] = (int)$complaint['user_id'];
    if (!$is_staff) {
        $staff = $conn->prepare("SELECT id FROM users WHERE role IN ('admin','department','officer') AND id <> ?");
        if ($staff) {
            $staff->bind_param('i', $commenter_id);
            $staff->execute();
            foreach ($staff->get_result() as $row) $recipients[] = (int)$row['id'];
            $staff->close();
        }
    }
    $recipients = array_values(array_unique($recipients));
    $title = 'New Comment';
    $message = $commenter_name . ' added a comment to complaint #' . $complaint_id . '.';
    foreach ($recipients as $recipient_id) {
        create_notification($conn, $recipient_id, $complaint_id, $title, $message, 'comment');
    }
    add_timeline_entry($conn, $complaint_id, $commenter_id, 'comment', null, null, 'Comment added by ' . $commenter_name . '.');
}
?>
