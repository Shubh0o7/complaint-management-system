<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

// Fetch complaints for the logged-in user
$stmt = $conn->prepare("SELECT id, subject, category, status, created_at FROM complaints WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $_SESSION['user_id']);
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
                                <p class="text-muted mt-2">No complaints submitted yet.</p>
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