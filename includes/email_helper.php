<?php
/**
 * Email Helper - Phase 3
 * Complaint Management System
 * 
 * Handles sending email notifications for complaint events.
 * Uses PHP's built-in mail() function.
 * For production, integrate with PHPMailer or an SMTP service.
 */

/**
 * Send email notification
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body HTML email body
 * @return bool Success status
 */
function send_email_notification($to, $subject, $body) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: CMS Notifications <noreply@complaint-system.com>\r\n";
    $headers .= "Reply-To: noreply@complaint-system.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $html_body = get_email_template($subject, $body);
    
    // Attempt to send email (will fail silently if mail server not configured)
    $sent = @mail($to, $subject, $html_body, $headers);
    
    // Log email attempt
    error_log("Email " . ($sent ? "sent" : "failed") . " to: $to | Subject: $subject");
    
    return $sent;
}

/**
 * Get HTML email template
 */
function get_email_template($subject, $content) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
            .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .email-header { background: #0d6efd; color: #ffffff; padding: 20px 30px; }
            .email-header h2 { margin: 0; font-size: 20px; }
            .email-body { padding: 30px; color: #333; line-height: 1.6; }
            .email-footer { background: #f8f9fa; padding: 15px 30px; text-align: center; color: #6c757d; font-size: 12px; }
            .btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: #ffffff; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
            .status-pending { background: #fff3cd; color: #856404; }
            .status-progress { background: #cce5ff; color: #004085; }
            .status-resolved { background: #d4edda; color: #155724; }
            .status-rejected { background: #f8d7da; color: #721c24; }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h2>' . htmlspecialchars($subject) . '</h2>
            </div>
            <div class="email-body">
                ' . $content . '
            </div>
            <div class="email-footer">
                <p>This is an automated notification from the Complaint Management System.</p>
                <p>&copy; ' . date('Y') . ' CMS - Complaint Management System</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Send status change notification email
 */
function send_status_change_email($user_email, $user_name, $complaint_subject, $old_status, $new_status, $complaint_id) {
    $status_class = match($new_status) {
        'Pending' => 'status-pending',
        'In Progress' => 'status-progress',
        'Resolved' => 'status-resolved',
        'Rejected' => 'status-rejected',
        default => 'status-pending'
    };

    $body = '
        <p>Hello <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
        <p>The status of your complaint has been updated:</p>
        <table style="width:100%; border-collapse:collapse; margin: 15px 0;">
            <tr>
                <td style="padding:8px; border:1px solid #dee2e6; font-weight:600;">Complaint</td>
                <td style="padding:8px; border:1px solid #dee2e6;">' . htmlspecialchars($complaint_subject) . '</td>
            </tr>
            <tr>
                <td style="padding:8px; border:1px solid #dee2e6; font-weight:600;">Previous Status</td>
                <td style="padding:8px; border:1px solid #dee2e6;">' . htmlspecialchars($old_status) . '</td>
            </tr>
            <tr>
                <td style="padding:8px; border:1px solid #dee2e6; font-weight:600;">New Status</td>
                <td style="padding:8px; border:1px solid #dee2e6;"><span class="status-badge ' . $status_class . '">' . htmlspecialchars($new_status) . '</span></td>
            </tr>
        </table>
        <p>You can view the full details of your complaint by logging into the system.</p>
        <a href="#" class="btn">View Complaint #' . $complaint_id . '</a>
    ';

    $subject = "Complaint Status Updated: " . $complaint_subject;
    return send_email_notification($user_email, $subject, $body);
}

/**
 * Send new comment notification email
 */
function send_comment_email($user_email, $user_name, $complaint_subject, $commenter_name, $comment_text, $complaint_id) {
    $body = '
        <p>Hello <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
        <p>A new comment has been added to your complaint:</p>
        <table style="width:100%; border-collapse:collapse; margin: 15px 0;">
            <tr>
                <td style="padding:8px; border:1px solid #dee2e6; font-weight:600;">Complaint</td>
                <td style="padding:8px; border:1px solid #dee2e6;">' . htmlspecialchars($complaint_subject) . '</td>
            </tr>
            <tr>
                <td style="padding:8px; border:1px solid #dee2e6; font-weight:600;">Comment By</td>
                <td style="padding:8px; border:1px solid #dee2e6;">' . htmlspecialchars($commenter_name) . '</td>
            </tr>
            <tr>
                <td style="padding:8px; border:1px solid #dee2e6; font-weight:600;">Comment</td>
                <td style="padding:8px; border:1px solid #dee2e6;">' . nl2br(htmlspecialchars($comment_text)) . '</td>
            </tr>
        </table>
        <a href="#" class="btn">View Complaint #' . $complaint_id . '</a>
    ';

    $subject = "New Comment on: " . $complaint_subject;
    return send_email_notification($user_email, $subject, $body);
}

/**
 * Send complaint submission confirmation email
 */
function send_complaint_submitted_email($user_email, $user_name, $complaint_subject, $complaint_id) {
    $body = '
        <p>Hello <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
        <p>Your complaint has been successfully submitted and is now being reviewed.</p>
        <table style="width:100%; border-collapse:collapse; margin: 15px 0;">
            <tr>
                <td style="padding:8px; border:1px solid #dee2e6; font-weight:600;">Complaint ID</td>
                <td style="padding:8px; border:1px solid #dee2e6;">#' . $complaint_id . '</td>
            </tr>
            <tr>
                <td style="padding:8px; border:1px solid #dee2e6; font-weight:600;">Subject</td>
                <td style="padding:8px; border:1px solid #dee2e6;">' . htmlspecialchars($complaint_subject) . '</td>
            </tr>
            <tr>
                <td style="padding:8px; border:1px solid #dee2e6; font-weight:600;">Status</td>
                <td style="padding:8px; border:1px solid #dee2e6;"><span class="status-badge status-pending">Pending</span></td>
            </tr>
        </table>
        <p>We will notify you when there are updates to your complaint.</p>
        <a href="#" class="btn">View Complaint #' . $complaint_id . '</a>
    ';

    $subject = "Complaint Submitted: " . $complaint_subject;
    return send_email_notification($user_email, $subject, $body);
}
?>