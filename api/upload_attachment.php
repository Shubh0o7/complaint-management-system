<?php
/**
 * API Endpoint: Upload Attachment
 * Handles file uploads for complaints
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

require_csrf_json();

$complaint_id = intval($_POST['complaint_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded or file upload error.']);
    exit();
}

// Validate complaint ownership or assignment.
$role = $_SESSION['user_role'] ?? 'user';
if ($role === 'admin') {
    $verify_stmt = $conn->prepare('SELECT user_id FROM complaints WHERE id = ?');
    $verify_stmt->bind_param('i', $complaint_id);
} elseif ($role === 'department') {
    $verify_stmt = $conn->prepare('SELECT user_id FROM complaints WHERE id = ? AND department_id = ?');
    $verify_stmt->bind_param('ii', $complaint_id, $_SESSION['department_id']);
} elseif ($role === 'officer') {
    $verify_stmt = $conn->prepare('SELECT user_id FROM complaints WHERE id = ? AND officer_id = ?');
    $verify_stmt->bind_param('ii', $complaint_id, $user_id);
} else {
    $verify_stmt = $conn->prepare('SELECT user_id FROM complaints WHERE id = ? AND user_id = ?');
    $verify_stmt->bind_param('ii', $complaint_id, $user_id);
}
if (!$verify_stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit();
}

$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();
$verify_stmt->close();

if ($verify_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
    exit();
}

// File validation
$file = $_FILES['file'];
$max_size = 5 * 1024 * 1024; // 5MB

if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
    exit();
}

$allowed_types = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detected_type = $finfo->file($file['tmp_name']);
if (!isset($allowed_types[$detected_type])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File type not allowed.']);
    exit();
}
$detected_extension = $allowed_types[$detected_type];

$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$file_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $detected_extension;
$file_path = $upload_dir . $file_name;

if (move_uploaded_file($file['tmp_name'], $file_path)) {
    // Insert attachment record
    $stmt = $conn->prepare("INSERT INTO complaint_attachments (complaint_id, user_id, file_name, original_name, file_type, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $original_name = basename($file['name']);
        $file_type = $detected_type;
        $file_size = $file['size'];
        
        $stmt->bind_param('iisssi', $complaint_id, $user_id, $file_name, $original_name, $file_type, $file_size);
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'attachment_id' => $stmt->insert_id,
                'file_name' => $original_name,
                'file_size' => $file_size
            ]);
        } else {
            unlink($file_path);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save file record.']);
        }
        $stmt->close();
    } else {
        unlink($file_path);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
}

$conn->close();
?>