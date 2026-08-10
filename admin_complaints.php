<?php
require_once 'config.php';
require_once 'includes/admin_check.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $complaint_id = (int)$_POST['complaint_id'];
    $new_status = $_POST['new_status'];
    $admin_remarks = trim($_POST['admin_remarks'] ?? '');
    
    $resolved_at = ($new_status === 'Resolved') ? date('Y-m-d H:i:s') : null;
    
    $stmt = $conn->prepare("UPDATE complaints SET status = ?, admin_remarks = ?, resolved_at = ? WHERE id = ?");
    $stmt->bind_param('sssi', $new_status, $admin_remarks, $resolved_at, $complaint_id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['flash_msg'] = "Complaint #$complaint_id status updated to '$new_status'.";
    header('Location: admin_complaints.php?' . http_build_query($_GET));
    exit();
}

// Filters
$filter_status = $_GET['status'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_category = $_GET['category'] ?? '';
$search_query = trim($_GET['search'] ?? '');

// Build query
$where_clauses = [];
$params = [];
$types = '';

if ($filter_status) {
    $where_clauses[] = "c.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}
if ($filter_priority) {
    $where_clauses[] = "c.priority = ?";
    $params[] = $filter_priority;
    $types .= 's';
}
if ($filter_category) {
    $where_clauses[] = "c.category = ?";
    $params[] = $filter_category;
    $types .= 's';
}
if ($search_query) {
    $where_clauses[] = "(c.subject LIKE ? OR c.description LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$sql = "SELECT c.*, u.full_name, u.email FROM complaints c JOIN users u ON c.user_id = u.id $where_sql ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$complaints = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get categories for filter dropdown
$categories = [];
$result = $conn->query("SELECT name FROM categories ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - Admin</title>
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
                    <h4 class="fw-bold mb-0"><i class="bi bi-kanban me-2"></i>Manage Complaints</h4>
                    <span class="badge bg-primary fs-6"><?= count($complaints) ?> results</span>
                </div>

                <?php if (isset($_SESSION['flash_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['flash_msg'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_msg']); endif; ?>

                <!-- Search & Filters -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Subject, description, user..." value="<?= htmlspecialchars($search_query) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="In Progress" <?= $filter_status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Resolved" <?= $filter_status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                    <option value="Rejected" <?= $filter_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="">All Priority</option>
                                    <option value="Low" <?= $filter_priority === 'Low' ? 'selected' : '' ?>>Low</option>
                                    <option value="Medium" <?= $filter_priority === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                    <option value="High" <?= $filter_priority === 'High' ? 'selected' : '' ?>>High</option>
                                    <option value="Critical" <?= $filter_priority === 'Critical' ? 'selected' : '' ?>>Critical</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" <?= $filter_category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search me-1"></i>Filter</button>
                                <a href="admin_complaints.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Complaints Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Subject</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($complaints)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">No complaints found.</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td><?= $c['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($c['full_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($c['email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($c['subject']) ?></td>
                                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($c['category']) ?></span></td>
                                        <td>
                                            <?php
                                            $p_class = match($c['priority']) {
                                                'Low' => 'bg-secondary',
                                                'Medium' => 'bg-info',
                                                'High' => 'bg-warning text-dark',
                                                'Critical' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $p_class ?>"><?= $c['priority'] ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $s_class = match($c['status']) {
                                                'Pending' => 'bg-warning text-dark',
                                                'In Progress' => 'bg-info',
                                                'Resolved' => 'bg-success',
                                                'Rejected' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $s_class ?>"><?= $c['status'] ?></span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-<?= $c['id'] ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modals for each complaint -->
    <?php foreach ($complaints as $c): ?>
    <div class="modal fade" id="modal-<?= $c['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complaint #<?= $c['id'] ?> - <?= htmlspecialchars($c['subject']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>User:</strong> <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['email']) ?>)
                            </div>
                            <div class="col-md-3">
                                <strong>Category:</strong> <?= htmlspecialchars($c['category']) ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Priority:</strong> <?= $c['priority'] ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p class="border rounded p-3 bg-light mt-1"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Submitted:</strong> <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Resolved:</strong> <?= $c['resolved_at'] ? date('d M Y, h:i A', strtotime($c['resolved_at'])) : 'Not yet' ?>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Update Status</label>
                                <select name="new_status" class="form-select">
                                    <option value="Pending" <?= $c['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="In Progress" <?= $c['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Resolved" <?= $c['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                    <option value="Rejected" <?= $c['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Admin Remarks</label>
                                <textarea name="admin_remarks" class="form-control" rows="2" placeholder="Add remarks..."><?= htmlspecialchars($c['admin_remarks'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_status" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>