<!-- Sidebar Navigation -->
<nav class="sidebar bg-white shadow-sm d-flex flex-column" style="width:250px;min-height:100vh;">
    <div class="p-3 border-bottom">
        <h5 class="text-primary fw-bold mb-0">
            <i class="bi bi-shield-check me-2"></i>CMS
        </h5>
        <small class="text-muted">Complaint Management</small>
    </div>
    <ul class="nav flex-column p-3">
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active bg-primary text-white' : 'text-dark' ?>" href="dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'add_complaint.php' ? 'active bg-primary text-white' : 'text-dark' ?>" href="add_complaint.php">
                <i class="bi bi-plus-circle me-2"></i> New Complaint
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link rounded <?= basename($_SERVER['PHP_SELF']) === 'complaints.php' ? 'active bg-primary text-white' : 'text-dark' ?>" href="complaints.php">
                <i class="bi bi-list-ul me-2"></i> My Complaints
            </a>
        </li>
    </ul>
    <div class="mt-auto p-3 border-top">
        <a href="logout.php" class="btn btn-outline-danger btn-sm w-100">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
    </div>
</nav>