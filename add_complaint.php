<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/notification_helper.php';
require_once 'includes/workflow_helper.php';

// Get categories from database
$categories = [];
$result = $conn->query("SELECT name FROM categories ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['name'];
    }
}
// Fallback if categories table is empty or doesn't exist yet
if (empty($categories)) {
    $categories = ['Academic', 'Infrastructure', 'Faculty', 'Hostel', 'Transport', 'Fees', 'Other'];
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = trim($_POST['priority'] ?? 'Medium');
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($subject)) $errors[] = 'Subject is required.';
    if (empty($category)) $errors[] = 'Category is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if (strlen($subject) > 200) $errors[] = 'Subject must be under 200 characters.';
    if (!in_array($priority, ['Low', 'Medium', 'High', 'Critical'])) $errors[] = 'Invalid priority level.';

    // File attachment validation
    $uploaded_files = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
        $max_size = 5 * 1024 * 1024; // 5MB per file
        $max_files = 3;

        $file_count = count($_FILES['attachments']['name']);
        if ($file_count > $max_files) {
            $errors[] = "Maximum $max_files files allowed.";
        } else {
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['attachments']['type'][$i];
                    $file_size = $_FILES['attachments']['size'][$i];

                    if (!in_array($file_type, $allowed_types)) {
                        $errors[] = 'File "' . htmlspecialchars($_FILES['attachments']['name'][$i]) . '" has an unsupported type.';
                    }
                    if ($file_size > $max_size) {
                        $errors[] = 'File "' . htmlspecialchars($_FILES['attachments']['name'][$i]) . '" exceeds 5MB limit.';
                    }
                } elseif ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = 'Error uploading file: ' . htmlspecialchars($_FILES['attachments']['name'][$i]);
                }
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO complaints (user_id, subject, category, priority, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issss', $_SESSION['user_id'], $subject, $category, $priority, $description);
        if ($stmt->execute()) {
            $complaint_id = $conn->insert_id;

            // Handle file uploads
            if (!empty($_FILES['attachments']['name'][0])) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                    if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                        $original_name = $_FILES['attachments']['name'][$i];
                        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
                        $stored_name = uniqid('attach_') . '_' . time() . '.' . $extension;
                        $file_path = $upload_dir . $stored_name;

                        if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $file_path)) {
                            $file_size = $_FILES['attachments']['size'][$i];
                            $file_type = $_FILES['attachments']['type'][$i];
                            $ins = $conn->prepare("INSERT INTO complaint_attachments (complaint_id, user_id, file_name, original_name, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?)");
                            $ins->bind_param('iisssi', $complaint_id, $_SESSION['user_id'], $stored_name, $original_name, $file_type, $file_size);
                            $ins->execute();
                            $ins->close();
                        }
                    }
                }
            }

            add_timeline_entry($conn, $complaint_id, (int)$_SESSION['user_id'], 'created', null, null, 'Complaint submitted');

            // Notify admins
            $admin_result = $conn->query("SELECT id FROM users WHERE role = 'admin'");
            while ($admin = $admin_result->fetch_assoc()) {
                create_notification($conn, (int)$admin['id'], $complaint_id, 'New Complaint', 'A new complaint has been submitted: ' . $subject, 'system');
            }

            $success = 'Complaint submitted successfully!';
            $subject = $category = $priority = $description = '';
        } else {
            $errors[] = 'Failed to submit complaint. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Complaint - Complaint Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="flex-grow-1">
            <?php include 'includes/topbar.php'; ?>
            <main class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0"><i class="bi bi-plus-circle me-2"></i>Submit New Complaint</h4>
                    <a href="complaints.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list-ul me-1"></i>View My Complaints
                    </a>
                </div>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" id="complaintForm" enctype="multipart/form-data" novalidate>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="subject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="subject" name="subject" maxlength="200" placeholder="Brief title for your complaint" value="<?= htmlspecialchars($subject ?? '') ?>" required>
                                    <div class="form-text">Max 200 characters</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>" <?= (isset($category) && $category === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label fw-semibold">Priority Level <span class="text-danger">*</span></label>
                                    <select class="form-select" id="priority" name="priority" required>
                                        <option value="Low" <?= (isset($priority) && $priority === 'Low') ? 'selected' : '' ?>>Low - Minor issue</option>
                                        <option value="Medium" <?= (!isset($priority) || $priority === 'Medium') ? 'selected' : '' ?>>Medium - Needs attention</option>
                                        <option value="High" <?= (isset($priority) && $priority === 'High') ? 'selected' : '' ?>>High - Urgent matter</option>
                                        <option value="Critical" <?= (isset($priority) && $priority === 'Critical') ? 'selected' : '' ?>>Critical - Immediate action needed</option>
                                    </select>
                                    <div class="form-text">
                                        <span class="badge bg-secondary">Low</span>
                                        <span class="badge bg-info">Medium</span>
                                        <span class="badge bg-warning text-dark">High</span>
                                        <span class="badge bg-danger">Critical</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" placeholder="Describe your complaint in detail..." required><?= htmlspecialchars($description ?? '') ?></textarea>
                            </div>

                            <!-- File Attachments -->
                            <div class="mb-4">
                                <label for="attachments" class="form-label fw-semibold"><i class="bi bi-paperclip me-1"></i>Attachments</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt">
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>Max 3 files, 5MB each. Supported: Images (JPG, PNG, GIF, WebP), PDF, Word, Text
                                </div>
                                <div id="filePreview" class="mt-2 d-flex flex-wrap gap-2"></div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-1"></i>Submit Complaint
                                </button>
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
    // File preview
    document.getElementById('attachments').addEventListener('change', function() {
        const preview = document.getElementById('filePreview');
        preview.innerHTML = '';
        const files = this.files;
        if (files.length > 3) {
            preview.innerHTML = '<span class="text-danger small">Maximum 3 files allowed!</span>';
            this.value = '';
            return;
        }
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const size = (file.size / 1024 / 1024).toFixed(2);
            const badge = document.createElement('span');
            badge.className = 'badge bg-light text-dark border';
            badge.innerHTML = '<i class="bi bi-file-earmark me-1"></i>' + file.name + ' (' + size + ' MB)';
            preview.appendChild(badge);
        }
    });
    </script>
</body>
</html>