<?php
require_once 'config.php';
// Session is started in config.php
// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['user_role'] === 'admin') ? 'admin_dashboard.php' : 'dashboard.php';
    header('Location: ' . $redirect);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Complaint Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4">
                    <h2 class="text-primary fw-bold"><i class="bi bi-shield-check me-2"></i>CMS</h2>
                    <p class="text-muted">Register to submit and track complaints</p>
                </div>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="text-center mb-3">Create your account</h5>
                        <!-- Dynamic message container -->
                        <div id="registerMessage" class="d-none"></div>
                        <form id="registerForm" novalidate>
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           placeholder="Enter your full name" required minlength="2">
                                </div>
                                <div class="invalid-feedback">Please enter your full name (at least 2 characters).</div>
                            </div>
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
                                           placeholder="Enter your password" required minlength="6">
                                </div>
                                <div class="form-text">Minimum 6 characters</div>
                                <div class="invalid-feedback">Password must be at least 6 characters.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm your password" required>
                                </div>
                                <div class="invalid-feedback" id="confirmPasswordFeedback">Please confirm your password.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="registerBtn">
                                    <i class="bi bi-person-plus me-1"></i>Register
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <p class="text-center mt-3">Already have an account? <a href="login.php" class="text-primary fw-semibold">Login here</a></p>
                <p class="text-center"><a href="index.php" class="text-muted"><i class="bi bi-arrow-left"></i> Back to Home</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const messageDiv = document.getElementById('registerMessage');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const confirmPasswordFeedback = document.getElementById('confirmPasswordFeedback');

        /**
         * Display a message (success, danger, warning) dynamically
         */
        function showMessage(type, text) {
            const iconMap = {
                'danger': 'exclamation-circle',
                'success': 'check-circle',
                'warning': 'exclamation-triangle'
            };
            messageDiv.className = `alert alert-${type} alert-dismissible fade show`;
            messageDiv.innerHTML = `
                <i class="bi bi-${iconMap[type] || 'info-circle'} me-1"></i>${text}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            messageDiv.classList.remove('d-none');

            // Auto-dismiss after 6 seconds
            setTimeout(() => {
                if (messageDiv && !messageDiv.classList.contains('d-none')) {
                    messageDiv.classList.add('d-none');
                }
            }, 6000);
        }

        /**
         * Real-time password match validation
         */
        function validatePasswordMatch() {
            if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity('Passwords do not match');
                confirmPasswordFeedback.textContent = 'Passwords do not match.';
            } else {
                confirmPasswordInput.setCustomValidity('');
                confirmPasswordFeedback.textContent = 'Please confirm your password.';
            }
        }

        passwordInput.addEventListener('input', validatePasswordMatch);
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);

        /**
         * Client-side validation
         */
        function validateForm(fullName, email, password, confirmPassword) {
            if (!fullName || !email || !password || !confirmPassword) {
                showMessage('danger', 'Please fill in all fields.');
                return false;
            }
            if (fullName.length < 2) {
                showMessage('danger', 'Full name must be at least 2 characters.');
                return false;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showMessage('danger', 'Please enter a valid email address.');
                return false;
            }
            if (password.length < 6) {
                showMessage('danger', 'Password must be at least 6 characters long.');
                return false;
            }
            if (password !== confirmPassword) {
                showMessage('danger', 'Passwords do not match.');
                return false;
            }
            return true;
        }

        /**
         * Handle form submission via fetch()
         */
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Get form values
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            // Client-side validation
            if (!validateForm(fullName, email, password, confirmPassword)) {
                registerForm.classList.add('was-validated');
                return;
            }

            // Disable button and show loading state
            registerBtn.disabled = true;
            registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Registering...';
            messageDiv.classList.add('d-none');

            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        full_name: fullName,
                        email: email,
                        password: password,
                        confirm_password: confirmPassword
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('success', data.message);
                    // Clear form
                    registerForm.reset();
                    registerForm.classList.remove('was-validated');
                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = data.redirect || 'dashboard.php';
                    }, 1500);
                } else {
                    showMessage('danger', data.message || 'Registration failed. Please try again.');
                    // Re-enable button
                    registerBtn.disabled = false;
                    registerBtn.innerHTML = '<i class="bi bi-person-plus me-1"></i>Register';
                }
            } catch (error) {
                console.error('Registration error:', error);
                showMessage('danger', 'Network error. Please check your connection and try again.');
                // Re-enable button
                registerBtn.disabled = false;
                registerBtn.innerHTML = '<i class="bi bi-person-plus me-1"></i>Register';
            }
        });
    });
    </script>
</body>
</html>