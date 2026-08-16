<?php
/**
 * API Endpoint: Add Comment
 * Accepts POST requests with comment data
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$complaint_id = intval($_POST['complaint_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$user_id = $_SESSION['user_id'];

if ($complaint_id <= 0 || empty($comment)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit();
}

if (strlen($comment) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Comment is too long.']);
    exit();
}

// Get complaint
$complaint_stmt = $conn->prepare("SELECT user_id FROM complaints WHERE id = ?");
if (!$complaint_stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit();
}

$complaint_stmt->bind_param('i', $complaint_id);
$complaint_stmt->execute();
$complaint_result = $complaint_stmt->get_result();

if ($complaint_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
    $complaint_stmt->close();
    exit();
}

$complaint_row = $complaint_result->fetch_assoc();
$complaint_user = $complaint_row['user_id'];
$complaint_stmt->close();

// Insert comment
$is_admin = $_SESSION['user_role'] === 'admin' ? 1 : 0;
$stmt = $conn->prepare("INSERT INTO complaint_comments (complaint_id, user_id, comment, is_admin, created_at) VALUES (?, ?, ?, ?, NOW())");

if ($stmt) {
    $stmt->bind_param('iisi', $complaint_id, $user_id, $comment, $is_admin);
    
    if ($stmt->execute()) {
        // Log timeline entry
        $action = 'comment';
        $timeline_stmt = $conn->prepare("INSERT INTO complaint_timeline (complaint_id, user_id, action, description) VALUES (?, ?, ?, ?)");
        if ($timeline_stmt) {
            $timeline_desc = 'New comment added';
            $timeline_stmt->bind_param('iiss', $complaint_id, $user_id, $action, $timeline_desc);
            $timeline_stmt->execute();
            $timeline_stmt->close();
        }
        
        // Notify other party
        $notify_user = ($user_id === $complaint_user) ? $complaint_user : $complaint_user;
        if ($user_id !== $complaint_user) {
            create_notification(
                $conn,
                $complaint_user,
                $complaint_id,
                'New Comment',
                'A new comment has been added to your complaint.',
                'new_comment'
            );
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully.',
            'comment_id' => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add comment.']);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}

$conn->close();
?>