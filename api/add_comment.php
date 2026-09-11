<?php
/**
 * API Endpoint: Add Comment
 * Accepts POST requests with comment data
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/notification_helper.php';
require_once __DIR__ . '/../includes/workflow_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

require_csrf_json();

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
$role = $_SESSION['user_role'] ?? 'user';
if ($role === 'admin') {
    $complaint_stmt = $conn->prepare('SELECT user_id FROM complaints WHERE id = ?');
    $complaint_stmt->bind_param('i', $complaint_id);
} elseif ($role === 'department') {
    $complaint_stmt = $conn->prepare('SELECT user_id FROM complaints WHERE id = ? AND department_id = ?');
    $complaint_stmt->bind_param('ii', $complaint_id, $_SESSION['department_id']);
} elseif ($role === 'officer') {
    $complaint_stmt = $conn->prepare('SELECT user_id FROM complaints WHERE id = ? AND officer_id = ?');
    $complaint_stmt->bind_param('ii', $complaint_id, $user_id);
} else {
    $complaint_stmt = $conn->prepare('SELECT user_id FROM complaints WHERE id = ? AND user_id = ?');
    $complaint_stmt->bind_param('ii', $complaint_id, $user_id);
}
if (!$complaint_stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit();
}

$complaint_stmt->execute();
$complaint_result = $complaint_stmt->get_result();

if ($complaint_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
    $complaint_stmt->close();
    exit();
}

$complaint_result->fetch_assoc();
$complaint_stmt->close();

// Insert comment
$is_admin = in_array($role, ['admin', 'department', 'officer'], true) ? 1 : 0;
$stmt = $conn->prepare("INSERT INTO complaint_comments (complaint_id, user_id, comment, is_admin, created_at) VALUES (?, ?, ?, ?, NOW())");

if ($stmt) {
    $stmt->bind_param('iisi', $complaint_id, $user_id, $comment, $is_admin);
    
    if ($stmt->execute()) {
        $is_staff = in_array($role, ['admin', 'department', 'officer'], true);
        notify_new_comment($conn, $complaint_id, $user_id, $_SESSION['user_name'] ?? 'User', $comment, $is_staff);
        
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