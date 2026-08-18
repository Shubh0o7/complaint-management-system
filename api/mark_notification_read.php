<?php
/**
 * API Endpoint: Mark Notification as Read
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

require_csrf_json();

$notification_id = intval($_POST['notification_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($notification_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
    exit();
}

// Verify ownership
$verify_stmt = $conn->prepare("SELECT user_id FROM notifications WHERE id = ?");
if (!$verify_stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit();
}

$verify_stmt->bind_param('i', $notification_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();
$verify_stmt->close();

if ($verify_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Notification not found.']);
    exit();
}

$notif_row = $verify_result->fetch_assoc();
if ($notif_row['user_id'] != $user_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

// Mark as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('i', $notification_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update notification.']);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}

$conn->close();
?>