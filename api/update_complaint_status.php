<?php
/**
 * API Endpoint: Update Complaint Status (Admin Only)
 * Updates complaint status and logs timeline entry
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$complaint_id = intval($_POST['complaint_id'] ?? 0);
$new_status = trim($_POST['status'] ?? '');
$admin_remarks = trim($_POST['remarks'] ?? '');
$admin_id = $_SESSION['user_id'];

if ($complaint_id <= 0 || empty($new_status)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit();
}

$valid_statuses = ['Pending', 'In Progress', 'Resolved', 'Rejected'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
    exit();
}

// Get current status
$complaint_stmt = $conn->prepare("SELECT user_id, status FROM complaints WHERE id = ?");
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
$old_status = $complaint_row['status'];
$complaint_stmt->close();

// Update complaint
$resolved_at = ($new_status === 'Resolved') ? 'NOW()' : 'NULL';
$stmt = $conn->prepare("UPDATE complaints SET status = ?, admin_remarks = ?, resolved_at = IF(? = 'Resolved', NOW(), resolved_at) WHERE id = ?");

if ($stmt) {
    $stmt->bind_param('sssi', $new_status, $admin_remarks, $new_status, $complaint_id);
    
    if ($stmt->execute()) {
        // Log timeline entry
        $action = 'status_change';
        $timeline_stmt = $conn->prepare("INSERT INTO complaint_timeline (complaint_id, user_id, action, old_value, new_value, description) VALUES (?, ?, ?, ?, ?, ?)");
        if ($timeline_stmt) {
            $timeline_desc = 'Status changed from ' . $old_status . ' to ' . $new_status;
            $timeline_stmt->bind_param('iisss', $complaint_id, $admin_id, $action, $old_status, $new_status);
            // Hmm, we need to fix this bind - we have one extra parameter
            $timeline_stmt->close();
            
            // Use correct bind
            $timeline_stmt = $conn->prepare("INSERT INTO complaint_timeline (complaint_id, user_id, action, description) VALUES (?, ?, ?, ?)");
            if ($timeline_stmt) {
                $timeline_stmt->bind_param('iiss', $complaint_id, $admin_id, $action, $timeline_desc);
                $timeline_stmt->execute();
                $timeline_stmt->close();
            }
        }
        
        // Notify user
        create_notification(
            $conn,
            $complaint_user,
            $complaint_id,
            'Status Updated',
            'Your complaint status has been updated to: ' . $new_status,
            'status_change'
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Complaint status updated successfully.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}

$conn->close();
?>