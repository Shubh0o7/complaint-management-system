<?php
require_once 'config.php';
require_once 'includes/security.php';
// If user is already logged in, send them to the correct role workspace.
if (isset($_SESSION['user_id'])) {
    redirect_role_home();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Complaint Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="text-center mb-4">
                    <h2 class="text-primary fw-bold"><i class="bi bi-shield-check me-2"></i>CMS</h2>
                    <p class="text-muted">Complaint Management System</p>
                </div>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="text-center mb-3">Login to your account</h5>
                        <!-- Dynamic message container -->
                        <div id="loginMessage" class="d-none"></div>
                        <form id="loginForm" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Enter your email" required>
                                </div>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter your password" required>
                                </div>
                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="loginBtn">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <p class="text-center mt-3"><a href="forgot_password.php" class="text-primary fw-semibold">Forgot password?</a></p>
                <p class="text-center">Don't have an account? <a href="register.php" class="text-primary fw-semibold">Register here</a></p>
                <p class="text-center"><a href="index.php" class="text-muted"><i class="bi bi-arrow-left"></i> Back to Home</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const messageDiv = document.getElementById('loginMessage');

        /**
         * Display a message (success or error) dynamically
         */
        function showMessage(type, text) {
            messageDiv.className = `alert alert-${type} alert-dismissible fade show`;
            messageDiv.innerHTML = `
                <i class="bi bi-${type === 'danger' ? 'exclamation-circle' : 'check-circle'} me-1"></i>${text}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            messageDiv.classList.remove('d-none');

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (messageDiv && !messageDiv.classList.contains('d-none')) {
                    messageDiv.classList.add('d-none');
                }
            }, 5000);
        }

        /**
         * Client-side validation
         */
        function validateForm(email, password) {
            if (!email || !password) {
                showMessage('danger', 'Please fill in all fields.');
                return false;
            }
            // Basic email format check
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showMessage('danger', 'Please enter a valid email address.');
                return false;
            }
            return true;
        }

        /**
         * Handle form submission via fetch()
         */
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Get form values
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            // Client-side validation
            if (!validateForm(email, password)) {
                loginForm.classList.add('was-validated');
                return;
            }

            // Disable button and show loading state
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Logging in...';
            messageDiv.classList.add('d-none');

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('success', data.message);
                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = data.redirect || 'dashboard.php';
                    }, 1000);
                } else {
                    showMessage('danger', data.message || 'Login failed. Please try again.');
                    // Re-enable button
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i>Login';
                }
            } catch (error) {
                console.error('Login error:', error);
                showMessage('danger', 'Network error. Please check your connection and try again.');
                // Re-enable button
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i>Login';
            }
        });
    });
    </script>
</body>
</html>