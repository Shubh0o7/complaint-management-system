<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

// Get complaint count for logged-in user
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM complaints WHERE user_id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$count_result = $stmt->get_result()->fetch_assoc();
$complaint_count = $count_result['total'];
$stmt->close();

// Get counts by status
$stmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM complaints WHERE user_id = ? GROUP BY status");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$status_result = $stmt->get_result();
$status_counts = ['Pending' => 0, 'In Progress' => 0, 'Resolved' => 0];
while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['cnt'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Complaint Management System</title>
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
                <!-- Welcome Banner -->
                <div class="card border-0 bg-primary text-white mb-4">
                    <div class="card-body p-4">
                        <h3 class="fw-bold">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h3>
                        <p class="mb-0 opacity-75">Manage your complaints from this dashboard.</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="display-6 text-primary fw-bold"><?= $complaint_count ?></div>
                                <p class="text-muted mb-0">Total Complaints</p>
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
                                <div class="display-6 text-info fw-bold"><?= $status_counts['In Progress'] ?></div>
                                <p class="text-muted mb-0">In Progress</p>
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

                <!-- Profile Card -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0">
                                <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Profile</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;font-size:1.5rem;">
                                        <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0"><?= htmlspecialchars($_SESSION['user_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($_SESSION['user_email']) ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0">
                                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <a href="add_complaint.php" class="btn btn-primary me-2 mb-2">
                                    <i class="bi bi-plus-circle me-1"></i> New Complaint
                                </a>
                                <a href="complaints.php" class="btn btn-outline-primary mb-2">
                                    <i class="bi bi-list-ul me-1"></i> View All
                                </a>
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