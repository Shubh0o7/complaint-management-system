<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

// Get categories for filter dropdown
$categories = [];
$cat_result = $conn->query("SELECT name FROM categories ORDER BY name");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row['name'];
    }
}
if (empty($categories)) {
    $categories = ['Academic', 'Infrastructure', 'Faculty', 'Hostel', 'Transport', 'Fees', 'Other'];
}

// Filter parameters
$filter_status = trim($_GET['status'] ?? '');
$filter_category = trim($_GET['category'] ?? '');
$filter_priority = trim($_GET['priority'] ?? '');
$search_query = trim($_GET['search'] ?? '');

// Build dynamic query
$where_clauses = ["user_id = ?"];
$params = [$_SESSION['user_id']];
$types = 'i';

if ($filter_status && in_array($filter_status, ['Pending', 'In Progress', 'Resolved'])) {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
    $types .= 's';
}
if ($filter_category) {
    $where_clauses[] = "category = ?";
    $params[] = $filter_category;
    $types .= 's';
}
if ($filter_priority && in_array($filter_priority, ['Low', 'Medium', 'High', 'Critical'])) {
    $where_clauses[] = "priority = ?";
    $params[] = $filter_priority;
    $types .= 's';
}
if ($search_query) {
    $where_clauses[] = "(subject LIKE ? OR description LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$sql = "SELECT id, subject, category, priority, status, created_at FROM complaints WHERE " . implode(' AND ', $where_clauses) . " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$complaints = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints - Complaint Management System</title>
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
                    <h4 class="fw-bold text-primary">My Complaints</h4>
                    <a href="add_complaint.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> New Complaint
                    </a>
                </div>

                <!-- Search & Filter Bar -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Search</label>
                                <input type="text" class="form-control form-control-sm" name="search" placeholder="Search subject..." value="<?= htmlspecialchars($search_query) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Status</label>
                                <select class="form-select form-select-sm" name="status">
                                    <option value="">All Status</option>
                                    <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="In Progress" <?= $filter_status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Resolved" <?= $filter_status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Category</label>
                                <select class="form-select form-select-sm" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" <?= $filter_category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Priority</label>
                                <select class="form-select form-select-sm" name="priority">
                                    <option value="">All Priorities</option>
                                    <option value="Low" <?= $filter_priority === 'Low' ? 'selected' : '' ?>>Low</option>
                                    <option value="Medium" <?= $filter_priority === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                    <option value="High" <?= $filter_priority === 'High' ? 'selected' : '' ?>>High</option>
                                    <option value="Critical" <?= $filter_priority === 'Critical' ? 'selected' : '' ?>>Critical</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-search me-1"></i>Filter
                                </button>
                                <a href="complaints.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-circle me-1"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <?php if ($complaints->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Subject</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1; while ($row = $complaints->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($row['subject']) ?></td>
                                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($row['category']) ?></span></td>
                                            <td>
                                                <?php
                                                $priority_class = match($row['priority'] ?? 'Medium') {
                                                    'Low' => 'bg-secondary',
                                                    'Medium' => 'bg-info text-white',
                                                    'High' => 'bg-warning text-dark',
                                                    'Critical' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                                ?>
                                                <span class="badge <?= $priority_class ?>"><?= htmlspecialchars($row['priority'] ?? 'Medium') ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = match($row['status']) {
                                                    'Pending' => 'bg-warning text-dark',
                                                    'In Progress' => 'bg-info text-white',
                                                    'Resolved' => 'bg-success',
                                                    default => 'bg-secondary'
                                                };
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mt-2">No complaints found matching your criteria.</p>
                                <a href="add_complaint.php" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> Submit Your First Complaint
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>