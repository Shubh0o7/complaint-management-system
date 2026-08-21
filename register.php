<?php
require_once 'config.php';

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
    <title>Register - Complaint Management System</title>
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
                        <h5 class="text-center mb-3">Create your account</h5>
                        <div id="registerMessage" class="d-none"></div>
                        <form id="registerForm" novalidate>
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           placeholder="Enter your full name" required>
                                </div>
                                <div class="invalid-feedback">Please enter your full name.</div>
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
                                           placeholder="Enter password (min 6 chars)" required>
                                    <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false" title="Show password"><i class="bi bi-eye"></i></button>
                                </div>
                                <div class="invalid-feedback">Please enter a password.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-check"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm your password" required>
                                    <button type="button" class="password-toggle" data-password-toggle="confirm_password" aria-label="Show password" aria-pressed="false" title="Show password"><i class="bi bi-eye"></i></button>
                                </div>
                                <div class="invalid-feedback">Passwords must match.</div>
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
    <style>
        .password-toggle { border: 0; background: transparent; color: #6c757d; padding: 0 .65rem; line-height: 1; }
        .password-toggle:hover, .password-toggle:focus-visible { color: #0d6efd; }
        .password-toggle:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; border-radius: .25rem; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const messageDiv = document.getElementById('registerMessage');

        document.querySelectorAll('[data-password-toggle]').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const input = document.getElementById(toggle.dataset.passwordToggle);
                const isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                toggle.setAttribute('aria-pressed', String(!isVisible));
                toggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                toggle.title = isVisible ? 'Show password' : 'Hide password';
                toggle.innerHTML = `<i class="bi bi-eye${isVisible ? '' : '-slash'}"></i>`;
            });
        });

        function showMessage(type, text) {
            messageDiv.className = `alert alert-${type} alert-dismissible fade show`;
            messageDiv.innerHTML = `
                <i class="bi bi-${type === 'danger' ? 'exclamation-circle' : 'check-circle'} me-1"></i>${text}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            messageDiv.classList.remove('d-none');

            setTimeout(() => {
                if (messageDiv && !messageDiv.classList.contains('d-none')) {
                    messageDiv.classList.add('d-none');
                }
            }, 5000);
        }

        function validateForm() {
            const full_name = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;

            if (!full_name || !email || !password || !confirm_password) {
                showMessage('danger', 'Please fill in all fields.');
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

            if (password !== confirm_password) {
                showMessage('danger', 'Passwords do not match.');
                return false;
            }

            return true;
        }

        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (!validateForm()) {
                registerForm.classList.add('was-validated');
                return;
            }

            const formData = new FormData(registerForm);
            registerBtn.disabled = true;
            registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Registering...';
            messageDiv.classList.add('d-none');

            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('success', data.message);
                    setTimeout(() => {
                        window.location.href = data.redirect || 'login.php';
                    }, 1500);
                } else {
                    showMessage('danger', data.message || 'Registration failed. Please try again.');
                    registerBtn.disabled = false;
                    registerBtn.innerHTML = '<i class="bi bi-person-plus me-1"></i>Register';
                }
            } catch (error) {
                console.error('Register error:', error);
                showMessage('danger', 'Network error. Please check your connection.');
                registerBtn.disabled = false;
                registerBtn.innerHTML = '<i class="bi bi-person-plus me-1"></i>Register';
            }
        });
    });
    </script>
</body>
</html>