<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="text-center mb-4">
                    <h1 class="text-primary fw-bold">Complaint Management System</h1>
                    <p class="text-muted">Submit and track your complaints efficiently</p>
                </div>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5 text-center">
                        <i class="bi bi-shield-check display-1 text-primary mb-3"></i>
                        <h3 class="mb-3">Welcome</h3>
                        <p class="text-muted mb-4">Please login or register to access the complaint portal.</p>
                        <div class="d-grid gap-2">
                            <a href="login.php" class="btn btn-primary btn-lg">Login</a>
                            <a href="register.php" class="btn btn-outline-primary btn-lg">Register</a>
                        </div>
                    </div>
                </div>
                <p class="text-center text-muted mt-3">&copy; <?= date('Y') ?> Complaint Management System</p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>