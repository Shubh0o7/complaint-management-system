<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($subject) || empty($category) || empty($description)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("INSERT INTO complaints (user_id, subject, category, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isss', $_SESSION['user_id'], $subject, $category, $description);

        if ($stmt->execute()) {
            $success = 'Complaint submitted successfully!';
            $subject = $category = $description = '';
        } else {
            $error = 'Failed to submit complaint. Please try again.';
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
    <title>Submit Complaint - Complaint Management System</title>
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
                    <h4 class="fw-bold text-primary">Submit New Complaint</h4>
                    <a href="complaints.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list-ul me-1"></i> View All Complaints
                    </a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="complaintForm" novalidate>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" 
                                       value="<?= htmlspecialchars($subject ?? '') ?>" 
                                       placeholder="Brief title for your complaint" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select a category</option>
                                    <option value="Service" <?= (($category ?? '') === 'Service') ? 'selected' : '' ?>>Service</option>
                                    <option value="Product" <?= (($category ?? '') === 'Product') ? 'selected' : '' ?>>Product</option>
                                    <option value="Billing" <?= (($category ?? '') === 'Billing') ? 'selected' : '' ?>>Billing</option>
                                    <option value="Technical" <?= (($category ?? '') === 'Technical') ? 'selected' : '' ?>>Technical</option>
                                    <option value="Other" <?= (($category ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="5" placeholder="Describe your complaint in detail" required><?= htmlspecialchars($description ?? '') ?></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-1"></i> Submit Complaint
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>