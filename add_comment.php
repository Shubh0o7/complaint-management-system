<?php
require_once 'config.php';
require_once 'includes/role_check.php';
require_once 'includes/notification_helper.php';
require_once 'includes/workflow_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . role_home($_SESSION['user_role'] ?? 'user'));
    exit();
}

$complaint_id = (int)($_POST['complaint_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($complaint_id <= 0 || empty($comment)) {
    $_SESSION['error'] = 'Invalid comment submission.';
    header('Location: ' . role_home($_SESSION['user_role'] ?? 'user'));
    exit();
}

// Verify the current role can access this complaint.
$role = $_SESSION['user_role'] ?? 'user';
$is_staff = in_array($role, ['admin', 'department', 'officer'], true);
if ($role === 'admin') {
    $stmt = $conn->prepare('SELECT id, user_id, subject FROM complaints WHERE id = ?');
    $stmt->bind_param('i', $complaint_id);
} elseif ($role === 'department') {
    $stmt = $conn->prepare('SELECT id, user_id, subject FROM complaints WHERE id = ? AND department_id = ?');
    $stmt->bind_param('ii', $complaint_id, $_SESSION['department_id']);
} elseif ($role === 'officer') {
    $stmt = $conn->prepare('SELECT id, user_id, subject FROM complaints WHERE id = ? AND officer_id = ?');
    $stmt->bind_param('ii', $complaint_id, $_SESSION['user_id']);
} else {
    $stmt = $conn->prepare('SELECT id, user_id, subject FROM complaints WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $complaint_id, $_SESSION['user_id']);
}
$stmt->execute();
$complaint = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$complaint) {
    $_SESSION['error'] = 'Complaint not found or access denied.';
    header('Location: ' . role_home($_SESSION['user_role'] ?? 'user'));
    exit();
}

// Insert comment
$is_admin_flag = $is_staff ? 1 : 0;
$stmt = $conn->prepare("INSERT INTO complaint_comments (complaint_id, user_id, comment, is_admin) VALUES (?, ?, ?, ?)");
$stmt->bind_param('iisi', $complaint_id, $_SESSION['user_id'], $comment, $is_admin_flag);

if ($stmt->execute()) {
    // Handle file attachment if uploaded with comment
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'application/zip'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $file = $_FILES['attachment'];
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'comment_' . $complaint_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $upload_path = __DIR__ . '/uploads/' . $new_name;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $stmt2 = $conn->prepare("INSERT INTO complaint_attachments (complaint_id, user_id, file_name, original_name, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param('iisssi', $complaint_id, $_SESSION['user_id'], $new_name, $file['name'], $file['type'], $file['size']);
                $stmt2->execute();
                $stmt2->close();

                // Add timeline entry for file upload
                add_timeline_entry($conn, $complaint_id, (int)$_SESSION['user_id'], 'file_uploaded', null, $file['name'], 'File attached with comment');
            }
        }
    }

    // Send notifications
    notify_new_comment($conn, $complaint_id, (int)$_SESSION['user_id'], $_SESSION['user_name'], $comment, $is_staff);

    $_SESSION['success'] = 'Comment posted successfully!';
} else {
    $_SESSION['error'] = 'Failed to post comment. Please try again.';
}
$stmt->close();

header('Location: view_complaint.php?id=' . $complaint_id);
exit();
?>