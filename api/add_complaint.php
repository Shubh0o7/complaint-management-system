<?php
/**
 * API Endpoint: Add Complaint
 * Accepts POST requests with form data and file uploads
 * Returns JSON response with success/error status
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/notification_helper.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$subject = trim($_POST['subject'] ?? '');
$category = trim($_POST['category'] ?? '');
$priority = trim($_POST['priority'] ?? 'Medium');
$description = trim($_POST['description'] ?? '');

// Validate input
if (empty($subject) || empty($category) || empty($description)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

if (strlen($subject) > 200) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Subject must not exceed 200 characters.']);
    exit();
}

if (strlen($description) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Description must be at least 10 characters long.']);
    exit();
}

$valid_priorities = ['Low', 'Medium', 'High', 'Critical'];
if (!in_array($priority, $valid_priorities)) {
    $priority = 'Medium';
}

// Insert complaint
$stmt = $conn->prepare("INSERT INTO complaints (user_id, subject, category, priority, description, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
    exit();
}

$stmt->bind_param('issss', $user_id, $subject, $category, $priority, $description);

if ($stmt->execute()) {
    $complaint_id = $stmt->insert_id;
    
    // Log timeline entry
    $action = 'created';
    $timeline_stmt = $conn->prepare("INSERT INTO complaint_timeline (complaint_id, user_id, action, description) VALUES (?, ?, ?, ?)");
    if ($timeline_stmt) {
        $timeline_desc = 'Complaint created by user';
        $timeline_stmt->bind_param('iiss', $complaint_id, $user_id, $action, $timeline_desc);
        $timeline_stmt->execute();
        $timeline_stmt->close();
    }
    
    // Notify admins
    $admin_stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin'");
    if ($admin_stmt) {
        $admin_stmt->execute();
        $admin_result = $admin_stmt->get_result();
        while ($admin = $admin_result->fetch_assoc()) {
            create_notification(
                $conn,
                $admin['id'],
                $complaint_id,
                'New Complaint',
                'A new complaint has been submitted: ' . $subject,
                'system'
            );
        }
        $admin_stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Complaint submitted successfully!',
        'complaint_id' => $complaint_id,
        'redirect' => 'complaints.php'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit complaint. Please try again.']);
}

$stmt->close();
$conn->close();
?>