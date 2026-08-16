<?php
require_once 'config.php';
require_once 'includes/role_check.php';
require_once 'includes/notification_helper.php';
require_once 'includes/workflow_helper.php';

$complaint_id = (int)($_GET['id'] ?? 0);

if ($complaint_id <= 0) {
    header('Location: ' . role_home($_SESSION['user_role'] ?? 'user'));
    exit();
}

// Fetch complaint details
$role = $_SESSION['user_role'] ?? 'user';
$is_admin = $role === 'admin';
if ($role === 'admin') {
    $stmt = $conn->prepare("SELECT c.*, u.full_name as user_name, u.email as user_email, d.name as department_name, o.full_name as officer_name FROM complaints c JOIN users u ON c.user_id = u.id LEFT JOIN departments d ON d.id = c.department_id LEFT JOIN users o ON o.id = c.officer_id WHERE c.id = ?");
    $stmt->bind_param('i', $complaint_id);
} elseif ($role === 'department') {
    $stmt = $conn->prepare("SELECT c.*, u.full_name as user_name, u.email as user_email, d.name as department_name, o.full_name as officer_name FROM complaints c JOIN users u ON c.user_id = u.id LEFT JOIN departments d ON d.id = c.department_id LEFT JOIN users o ON o.id = c.officer_id WHERE c.id = ? AND c.department_id = ?");
    $stmt->bind_param('ii', $complaint_id, $_SESSION['department_id']);
} elseif ($role === 'officer') {
    $stmt = $conn->prepare("SELECT c.*, u.full_name as user_name, u.email as user_email, d.name as department_name, o.full_name as officer_name FROM complaints c JOIN users u ON c.user_id = u.id LEFT JOIN departments d ON d.id = c.department_id LEFT JOIN users o ON o.id = c.officer_id WHERE c.id = ? AND c.officer_id = ?");
    $stmt->bind_param('ii', $complaint_id, $_SESSION['user_id']);
} else {
    $stmt = $conn->prepare("SELECT c.*, u.full_name as user_name, u.email as user_email, d.name as department_name, o.full_name as officer_name FROM complaints c JOIN users u ON c.user_id = u.id LEFT JOIN departments d ON d.id = c.department_id LEFT JOIN users o ON o.id = c.officer_id WHERE c.id = ? AND c.user_id = ?");
    $stmt->bind_param('ii', $complaint_id, $_SESSION['user_id']);
}
$stmt->execute();
$complaint = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$complaint) {
    header('Location: ' . role_home($_SESSION['user_role'] ?? 'user'));
    exit();
}
$display_user_name = $is_admin ? ($complaint['user_name'] ?? '') : (((int)($complaint['is_anonymous'] ?? 0) === 1) ? 'Anonymous complainant' : ($complaint['user_name'] ?? ''));

// Fetch timeline
$timeline = get_complaint_timeline($conn, $complaint_id);

// Fetch attachments
$stmt = $conn->prepare("SELECT a.*, u.full_name FROM complaint_attachments a JOIN users u ON a.user_id = u.id WHERE a.complaint_id = ? ORDER BY a.uploaded_at DESC");
$stmt->bind_param('i', $complaint_id);
$stmt->execute();
$attachments_result = $stmt->get_result();
$attachments = [];
while ($row = $attachments_result->fetch_assoc()) {
    $attachments[] = $row;
}
$stmt->close();

// Fetch comments
$stmt = $conn->prepare("SELECT cc.*, u.full_name, u.role FROM complaint_comments cc JOIN users u ON cc.user_id = u.id WHERE cc.complaint_id = ? ORDER BY cc.created_at ASC");
$stmt->bind_param('i', $complaint_id);
$stmt->execute();
$comments_result = $stmt->get_result();
$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $comments[] = $row;
}
$stmt->close();

// Status badge helper
function get_status_badge($status) {
    return match($status) {
        'Pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'In Progress' => '<span class="badge bg-info text-white">In Progress</span>',
        'Resolved' => '<span class="badge bg-success">Resolved</span>',
        'Rejected' => '<span class="badge bg-danger">Rejected</span>',
        default => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>'
    };
}

function get_priority_badge($priority) {
    return match($priority) {
        'Low' => '<span class="badge bg-secondary">Low</span>',
        'Medium' => '<span class="badge bg-info">Medium</span>',
        'High' => '<span class="badge bg-warning text-dark">High</span>',
        'Critical' => '<span class="badge bg-danger">Critical</span>',
        default => '<span class="badge bg-secondary">' . htmlspecialchars($priority) . '</span>'
    };
}

function format_file_size($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint #<?= $complaint_id ?> - Complaint Management System</title>
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
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= $is_admin ? 'admin_dashboard.php' : 'dashboard.php' ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= $is_admin ? 'admin_complaints.php' : 'complaints.php' ?>">Complaints</a></li>
                        <li class="breadcrumb-item active">Complaint #<?= $complaint_id ?></li>
                    </ol>
                </nav>

                <!-- Complaint Header -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div>
                                <h4 class="fw-bold mb-2"><?= htmlspecialchars($complaint['subject']) ?></h4>
                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                    <?= get_status_badge($complaint['status']) ?>
                                    <?= get_priority_badge($complaint['priority']) ?>
                                    <span class="badge bg-light text-dark"><i class="bi bi-folder me-1"></i><?= htmlspecialchars($complaint['category']) ?></span>
                                    <small class="text-muted"><i class="bi bi-calendar me-1"></i><?= date('M d, Y \a\t h:i A', strtotime($complaint['created_at'])) ?></small>
                                    <span class="badge bg-light text-dark">SLA due: <?= htmlspecialchars($complaint['sla_due_at'] ?? 'Not calculated') ?></span>
                                    <?php if (is_complaint_overdue($complaint['sla_due_at'] ?? null, $complaint['status'])): ?><span class="badge bg-danger">Overdue</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">Reference: <strong><?= htmlspecialchars($complaint['reference_no'] ?? ('#' . $complaint_id)) ?></strong></small>
                                <a class="btn btn-sm btn-outline-primary mt-2" href="receipt.php?id=<?= $complaint_id ?>"><i class="bi bi-file-earmark-pdf me-1"></i>PDF receipt</a>
                                <?php if ($complaint['status'] === 'Resolved' && !$is_admin): ?><a class="btn btn-sm btn-outline-success mt-2" href="feedback.php?id=<?= $complaint_id ?>"><i class="bi bi-star me-1"></i><?= $complaint['feedback_rating'] ? 'Update feedback' : 'Rate resolution' ?></a><?php endif; ?>
                                <?php if ($is_admin): ?>
                                <small class="text-muted">By: <?= htmlspecialchars($display_user_name) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column: Description, Comments -->
                    <div class="col-lg-8">
                        <!-- Description -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-0 pt-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-text-paragraph me-2"></i>Description</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($complaint['description']) ?></p>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-0 pt-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-paperclip me-2"></i>Attachments (<?= count($attachments) ?>)</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($attachments)): ?>
                                    <p class="text-muted mb-0"><i class="bi bi-inbox me-1"></i>No attachments uploaded.</p>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($attachments as $file): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <div>
                                                <i class="bi bi-file-earmark me-2 text-primary"></i>
                                                <strong><?= htmlspecialchars($file['original_name']) ?></strong>
                                                <small class="text-muted ms-2">(<?= format_file_size($file['file_size']) ?>)</small>
                                                <br><small class="text-muted">Uploaded by <?= htmlspecialchars($file['full_name']) ?> on <?= date('M d, Y', strtotime($file['uploaded_at'])) ?></small>
                                            </div>
                                            <a href="download_attachment.php?id=<?= (int)$file['id'] ?>" class="btn btn-sm btn-outline-primary" download="<?= htmlspecialchars($file['original_name']) ?>">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Comments Section -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-0 pt-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-chat-dots me-2"></i>Comments (<?= count($comments) ?>)</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($comments)): ?>
                                    <p class="text-muted text-center py-3"><i class="bi bi-chat me-1"></i>No comments yet. Start the conversation!</p>
                                <?php else: ?>
                                    <div class="comments-list">
                                        <?php foreach ($comments as $comment): ?>
                                        <div class="comment-item mb-3 p-3 rounded <?= $comment['is_admin'] ? 'bg-light-blue' : 'bg-light' ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <strong><?= htmlspecialchars($comment['full_name']) ?></strong>
                                                    <?php if ($comment['role'] === 'admin'): ?>
                                                    <span class="badge bg-primary ms-1">Admin</span>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted"><?= date('M d, Y \a\t h:i A', strtotime($comment['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($comment['comment']) ?></p>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Add Comment Form -->
                                <hr class="my-3">
                                <form action="add_comment.php" method="POST" class="mt-3">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="complaint_id" value="<?= $complaint_id ?>">
                                    <div class="mb-3">
                                        <textarea class="form-control" name="comment" rows="3" placeholder="Write your comment..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-send me-1"></i>Post Comment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Timeline -->
                    <div class="col-lg-4">
                        <!-- Admin Remarks -->
                        <?php if (!empty($complaint['admin_remarks'])): ?>
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-0 pt-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-chat-square-text me-2"></i>Admin Remarks</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0"><?= htmlspecialchars($complaint['admin_remarks']) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Timeline -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-0 pt-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Activity Timeline</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($timeline)): ?>
                                    <div class="timeline-item">
                                        <div class="d-flex align-items-start">
                                            <div class="timeline-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;min-width:32px;">
                                                <i class="bi bi-plus-circle-fill" style="font-size:0.8rem;"></i>
                                            </div>
                                            <div class="ms-3">
                                                <strong class="d-block">Complaint Created</strong>
                                                <small class="text-muted"><?= date('M d, Y \a\t h:i A', strtotime($complaint['created_at'])) ?></small>
                                                <p class="mb-0 mt-1 text-muted small">By <?= htmlspecialchars($display_user_name) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($timeline as $entry): ?>
                                    <?php $style = get_timeline_style($entry['action']); ?>
                                    <div class="timeline-item mb-3 pb-3 border-bottom">
                                        <div class="d-flex align-items-start">
                                            <div class="timeline-icon bg-<?= $style['color'] ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;min-width:32px;">
                                                <i class="bi <?= $style['icon'] ?>" style="font-size:0.8rem;"></i>
                                            </div>
                                            <div class="ms-3">
                                                <strong class="d-block">
                                                    <?php
                                                    echo match($entry['action']) {
                                                        'created' => 'Complaint Created',
                                                        'status_change' => 'Status Changed',
                                                        'comment_added' => 'Comment Added',
                                                        'file_uploaded' => 'File Uploaded',
                                                        'assigned' => 'Assigned',
                                                        'resolved' => 'Resolved',
                                                        'rejected' => 'Rejected',
                                                        default => ucfirst(str_replace('_', ' ', $entry['action']))
                                                    };
                                                    ?>
                                                </strong>
                                                <?php if ($entry['old_value'] && $entry['new_value']): ?>
                                                <span class="small">
                                                    <span class="text-muted"><?= htmlspecialchars($entry['old_value']) ?></span>
                                                    <i class="bi bi-arrow-right mx-1"></i>
                                                    <strong><?= htmlspecialchars($entry['new_value']) ?></strong>
                                                </span>
                                                <?php endif; ?>
                                                <?php if ($entry['description']): ?>
                                                <p class="mb-0 mt-1 text-muted small"><?= htmlspecialchars($entry['description']) ?></p>
                                                <?php endif; ?>
                                                <small class="text-muted"><?= date('M d, Y \a\t h:i A', strtotime($entry['created_at'])) ?></small>
                                                <small class="text-muted d-block">By <?= htmlspecialchars($entry['full_name']) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Info -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 pt-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2"></i>Details</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted fw-semibold">ID</td>
                                        <td>#<?= $complaint_id ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Status</td>
                                        <td><?= get_status_badge($complaint['status']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Priority</td>
                                        <td><?= get_priority_badge($complaint['priority']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Category</td>
                                        <td><?= htmlspecialchars($complaint['category']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Submitted</td>
                                        <td><?= date('M d, Y', strtotime($complaint['created_at'])) ?></td>
                                    </tr>
                                    <?php if ($complaint['resolved_at']): ?>
                                    <tr>
                                        <td class="text-muted fw-semibold">Resolved</td>
                                        <td><?= date('M d, Y', strtotime($complaint['resolved_at'])) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="text-muted fw-semibold">Submitted By</td>
                                        <td><?= htmlspecialchars($display_user_name) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>