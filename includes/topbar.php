<!-- Top Navigation Bar -->
<header class="bg-white shadow-sm border-bottom p-3 d-flex justify-content-between align-items-center">
    <div>
        <h5 class="mb-0 text-dark">
            <?php
            $page_titles = [
                'dashboard.php' => 'Dashboard',
                'add_complaint.php' => 'Submit Complaint',
                'complaints.php' => 'My Complaints'
            ];
            echo $page_titles[basename($_SERVER['PHP_SELF'])] ?? 'Complaint Management System';
            ?>
        </h5>
    </div>
    <div class="d-flex align-items-center">
        <span class="text-muted me-3">
            <i class="bi bi-person-circle me-1"></i>
            <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
        </span>
        <a href="logout.php" class="btn btn-sm btn-outline-secondary" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</header>