<?php
/**
 * Email notification adapter.
 * Local/Docker mode logs messages and records them as `logged`; production can
 * enable PHP mail() with MAIL_ENABLED=1 or replace this adapter with SMTP.
 */
function send_email_notification(mysqli $conn, ?int $userId, ?int $complaintId, string $to, string $subject, string $body): bool
{
    $status = 'logged'; $error = null; $sentAt = null;
    $notificationsEnabled = true;
    $settingStmt = $conn->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1');
    if ($settingStmt) {
        $settingKey = 'email_notifications_enabled';
        $settingStmt->bind_param('s', $settingKey);
        $settingStmt->execute();
        $settingRow = $settingStmt->get_result()->fetch_assoc();
        $settingStmt->close();
        if ($settingRow !== null) $notificationsEnabled = filter_var($settingRow['setting_value'], FILTER_VALIDATE_BOOLEAN);
    }
    if (!$notificationsEnabled) {
        $status = 'disabled';
        $error = 'Disabled by administrator settings';
    }
    $mailEnabled = filter_var(getenv('MAIL_ENABLED') ?: '0', FILTER_VALIDATE_BOOLEAN);
    if ($notificationsEnabled && $mailEnabled) {
        $from = getenv('MAIL_FROM') ?: 'noreply@grievance-portal.local';
        $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        $sent = @mail($to, $subject, $body, $headers);
        $status = $sent ? 'sent' : 'failed'; $sentAt = $sent ? date('Y-m-d H:i:s') : null;
        if (!$sent) $error = 'mail() returned false';
    }
    $stmt = $conn->prepare('INSERT INTO email_notifications (user_id, complaint_id, recipient, subject, body, status, error_message, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    if ($stmt) { $stmt->bind_param('iissssss', $userId, $complaintId, $to, $subject, $body, $status, $error, $sentAt); $stmt->execute(); $stmt->close(); }
    $logFile = __DIR__ . '/../logs/emails.log'; if (!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0750, true);
    @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] {$status}\nTo: {$to}\nSubject: {$subject}\n{$body}\n---\n", FILE_APPEND);
    return $status !== 'failed';
}

function send_email(string $to_email, string $subject, string $message): bool
{
    $logFile = __DIR__ . '/../logs/emails.log'; if (!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0750, true);
    return @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "]\nTo: {$to_email}\nSubject: {$subject}\nMessage: {$message}\n---\n", FILE_APPEND) !== false;
}

function send_status_change_email($to_email, $user_name, $complaint_id, $subject, $old_status, $new_status) {
    return send_email($to_email, 'Complaint Status Update: ' . $subject, "Hello {$user_name},\n\nComplaint #{$complaint_id} changed from {$old_status} to {$new_status}.\n\nPlease sign in to view the latest details.");
}
function send_comment_email($to_email, $user_name, $complaint_id, $subject) {
    return send_email($to_email, 'New Comment on Your Complaint: ' . $subject, "Hello {$user_name},\n\nA new comment was added to complaint #{$complaint_id}.\n\nPlease sign in to view it.");
}
function send_new_complaint_email($to_email, $admin_name, $complaint_id, $subject, $priority) {
    return send_email($to_email, 'New Complaint Submitted [' . $priority . ']: ' . $subject, "Hello {$admin_name},\n\nA new complaint (#{$complaint_id}) was submitted with {$priority} priority.");
}
?>
