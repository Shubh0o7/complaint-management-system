<?php
/**
 * Email Helper Functions
 * Handles sending email notifications (mock implementation)
 * In production, replace with actual mail service (PHPMailer, SendGrid, etc.)
 */

/**
 * Send status change notification email
 * 
 * @param string $to_email Recipient email
 * @param string $user_name Recipient name
 * @param string $complaint_id Complaint ID
 * @param string $subject Complaint subject
 * @param string $old_status Previous status
 * @param string $new_status New status
 * @return bool Success status
 */
function send_status_change_email($to_email, $user_name, $complaint_id, $subject, $old_status, $new_status) {
    $email_subject = 'Complaint Status Update: ' . $subject;
    $message = "Hello $user_name,\n\n";
    $message .= "Your complaint (#$complaint_id) status has been updated.\n\n";
    $message .= "Subject: $subject\n";
    $message .= "Previous Status: $old_status\n";
    $message .= "New Status: $new_status\n\n";
    $message .= "Please log in to the system to view more details.\n\n";
    $message .= "Best regards,\nComplaint Management System";
    
    return send_email($to_email, $email_subject, $message);
}

/**
 * Send new comment notification email
 * 
 * @param string $to_email Recipient email
 * @param string $user_name Recipient name
 * @param string $complaint_id Complaint ID
 * @param string $subject Complaint subject
 * @return bool Success status
 */
function send_comment_email($to_email, $user_name, $complaint_id, $subject) {
    $email_subject = 'New Comment on Your Complaint: ' . $subject;
    $message = "Hello $user_name,\n\n";
    $message .= "A new comment has been added to your complaint (#$complaint_id).\n\n";
    $message .= "Subject: $subject\n\n";
    $message .= "Please log in to the system to view the comment.\n\n";
    $message .= "Best regards,\nComplaint Management System";
    
    return send_email($to_email, $email_subject, $message);
}

/**
 * Send new complaint notification email to admins
 * 
 * @param string $to_email Admin email
 * @param string $admin_name Admin name
 * @param string $complaint_id Complaint ID
 * @param string $subject Complaint subject
 * @param string $priority Complaint priority
 * @return bool Success status
 */
function send_new_complaint_email($to_email, $admin_name, $complaint_id, $subject, $priority) {
    $email_subject = 'New Complaint Submitted [' . $priority . ']: ' . $subject;
    $message = "Hello $admin_name,\n\n";
    $message .= "A new complaint has been submitted.\n\n";
    $message .= "Complaint ID: $complaint_id\n";
    $message .= "Subject: $subject\n";
    $message .= "Priority: $priority\n\n";
    $message .= "Please log in to the admin panel to review and manage this complaint.\n\n";
    $message .= "Best regards,\nComplaint Management System";
    
    return send_email($to_email, $email_subject, $message);
}

/**
 * Generic email sending function
 * NOTE: This is a mock implementation. For production, use:
 * - PHPMailer (recommended)
 * - SwiftMailer
 * - SendGrid API
 * - AWS SES
 * 
 * @param string $to_email Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @return bool Success status
 */
function send_email($to_email, $subject, $message) {
    // For production, implement actual email sending here
    // Example with PHPMailer:
    /*
    require 'vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com';
    $mail->Password = 'your-app-password';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->setFrom('noreply@cms.com', 'Complaint Management System');
    $mail->addAddress($to_email);
    $mail->Subject = $subject;
    $mail->Body = $message;
    return $mail->send();
    */
    
    // Mock implementation (logs to file)
    $log_file = __DIR__ . '/../logs/emails.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_entry = "[" . date('Y-m-d H:i:s') . "] \n";
    $log_entry .= "To: $to_email\n";
    $log_entry .= "Subject: $subject\n";
    $log_entry .= "Message: $message\n";
    $log_entry .= "---\n\n";
    
    return file_put_contents($log_file, $log_entry, FILE_APPEND) !== false;
}
?>