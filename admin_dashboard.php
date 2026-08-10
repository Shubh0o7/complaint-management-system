<?php
require_once 'config.php';
require_once 'includes/admin_check.php';

// Total complaints
$result = $conn->query("SELECT COUNT(*) as total FROM complaints");
$total_complaints = $result->fetch_assoc()['total'];

// Total users
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$total_users = $result->fetch_assoc()['total'];

// Complaints by status
$status_counts = ['Pending' => 0, 'In Progress' => 0, 'Resolved' => 0, 'Rejected' => 0];
$result = $conn->query("SELECT status, COUNT(*) as cnt FROM complaints GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['cnt'];
}

// Complaints by priority
$priority_counts = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];
$result = $conn->query("SELECT priority, COUNT(*) as cnt FROM complaints GROUP BY priority");
while ($row = $result->fetch_assoc()) {
    $priority_counts[$row['priority']] = $row['cnt'];
}

// Recent complaints (last 5)
$result = $conn->query("SELECT c.*, u.full_name FROM complaints c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC LIMIT 5");
$recent_complaints = [];
while ($row = $result->fetch_assoc()) {
    $recent_complaints[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Complaint Management System</title>
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
                <!-- Admin Welcome Banner -->
                <div class="card border-0 bg-dark text-white mb-4">
                    <div class="card-body p-4">
                        <h3 class="fw-bold"><i class="bi bi-shield-lock me-2"></i>Admin Dashboard</h3>
                        <p class="mb-0 opacity-75">Manage all complaints, users, and system settings.</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="display-6 text-primary fw-bold"><?= $total_complaints ?></div>
                                <p class="text-muted mb-0">Total Complaints</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="display-6 text-info fw-bold"><?= $total_users ?></div>
                                <p class="text-muted mb-0">Registered Users</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="display-6 text-warning fw-bold"><?= $status_counts['Pending'] ?></div>
                                <p class="text-muted mb-0">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="display-6 text-success fw-bold"><?= $status_counts['Resolved'] ?></div>
                                <p class="text-muted mb-0">Resolved</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Priority Overview -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm border-start border-4 border-secondary">
                            <div class="card-body">
                                <h6 class="text-muted">Low Priority</h6>
                                <h4 class="fw-bold"><?= $priority_counts['Low'] ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm border-start border-4 border-info">
                            <div class="card-body">
                                <h6 class="text-muted">Medium Priority</h6>
                                <h4 class="fw-bold"><?= $priority_counts['Medium'] ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm border-start border-4 border-warning">
                            <div class="card-body">
                                <h6 class="text-muted">High Priority</h6>
                                <h4 class="fw-bold"><?= $priority_counts['High'] ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm border-start border-4 border-danger">
                            <div class="card-body">
                                <h6 class="text-muted">Critical</h6>
                                <h4 class="fw-bold"><?= $priority_counts['Critical'] ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Complaints -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Complaints</h5>
                        <a href="admin_complaints.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_complaints)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No complaints yet.</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($recent_complaints as $c): ?>
                                    <tr>
                                        <td><?= $c['id'] ?></td>
                                        <td><?= htmlspecialchars($c['full_name']) ?></td>
                                        <td><?= htmlspecialchars($c['subject']) ?></td>
                                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($c['category']) ?></span></td>
                                        <td>
                                            <?php
                                            $priority_class = match($c['priority']) {
                                                'Low' => 'bg-secondary',
                                                'Medium' => 'bg-info',
                                                'High' => 'bg-warning text-dark',
                                                'Critical' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $priority_class ?>"><?= $c['priority'] ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = match($c['status']) {
                                                'Pending' => 'bg-warning text-dark',
                                                'In Progress' => 'bg-info',
                                                'Resolved' => 'bg-success',
                                                'Rejected' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $status_class ?>"><?= $c['status'] ?></span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>