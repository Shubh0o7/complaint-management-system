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
    <title>Welcome - CampusResolve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="campus-auth-page">
    <main class="campus-auth-shell">
        <header class="campus-auth-nav"><a class="campus-auth-brand" href="index.php"><span class="campus-auth-mark"><i class="bi bi-shield-check"></i></span><span><strong>CampusResolve</strong><small>Student Grievance Portal</small></span></a><nav><a href="index.php">Home</a><a href="register.php">Sign up</a><a href="forgot_password.php">Help</a></nav></header>
        <section class="campus-auth-card">
            <div class="campus-auth-form">
                    <div class="campus-auth-tabs"><a class="active" href="login.php">Login</a><a href="register.php">Sign up</a></div>
                    <p class="campus-eyebrow">SECURE SQL ACCOUNT ACCESS</p><h1>Welcome</h1><p class="campus-auth-subtitle">Sign in with the account assigned to your dashboard.</p>
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
                                    <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false" title="Show password"><i class="bi bi-eye"></i></button>
                                </div>
                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="loginBtn">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                                </button>
                            </div>
                        </form>
                        <div class="campus-auth-links"><a href="forgot_password.php">Forgot password?</a><span>Use the email assigned to your role.</span></div>
                        <p class="campus-auth-note"><i class="bi bi-database-check"></i> Accounts and Case records are stored directly in the MySQL/MariaDB database.</p>
                    </div>
            <aside class="campus-auth-visual"><div class="campus-orbit orbit-a"></div><div class="campus-orbit orbit-b"></div><div class="campus-orbit orbit-c"></div><div class="campus-laptop"><div class="campus-screen"><i class="bi bi-laptop"></i><span>Secure campus access</span></div></div><div class="campus-auth-caption"><strong>Submit. Track. Resolve.</strong><span>One clear place for every campus case.</span></div></aside>
        </section>
    </main>
    <style>
    .campus-auth-page{margin:0;min-height:100vh;background:linear-gradient(135deg,#eaf7f6,#f8fbfb 50%,#d8efed);color:#2f4156}.campus-auth-shell{width:min(1180px,calc(100% - 40px));margin:auto;padding:22px 0 44px}.campus-auth-nav{display:flex;align-items:center;min-height:58px}.campus-auth-brand{display:flex;align-items:center;gap:10px;color:#2f4156;text-decoration:none}.campus-auth-brand strong,.campus-auth-brand small{display:block}.campus-auth-brand strong{font-size:16px}.campus-auth-brand small{font-size:10px;color:#6f8790}.campus-auth-mark{width:42px;height:42px;border-radius:12px;background:#2f4156;color:#fff;display:grid;place-items:center;font-size:20px}.campus-auth-nav nav{display:flex;gap:25px;margin-left:42px}.campus-auth-nav nav a{font-size:12px;color:#567c8d;text-decoration:none}.campus-auth-card{display:grid;grid-template-columns:1fr 1fr;min-height:600px;margin-top:22px;background:#fff;box-shadow:0 25px 70px rgba(47,65,86,.15);border-radius:6px;overflow:hidden}.campus-auth-form{padding:58px clamp(30px,6vw,86px);display:flex;flex-direction:column;justify-content:center}.campus-auth-tabs{display:flex;gap:30px;border-bottom:1px solid #d9e8e8;margin-bottom:30px}.campus-auth-tabs a{color:#a4b5b9;text-decoration:none;font-weight:700;padding-bottom:12px;position:relative}.campus-auth-tabs a.active{color:#378e88}.campus-auth-tabs a.active:after{content:"";height:2px;background:#4eb7aa;position:absolute;left:0;right:0;bottom:-1px}.campus-eyebrow{font-size:10px;letter-spacing:2px;color:#5b9b98;font-weight:800;margin-bottom:13px}.campus-auth-form h1{font-size:42px;letter-spacing:-1.5px;margin:0 0 6px}.campus-auth-subtitle{color:#778b91;font-size:13px;margin-bottom:24px}.campus-auth-form label{font-size:12px;font-weight:700;color:#567c8d}.campus-auth-form .input-group{border-bottom:1px solid #d7e5e6;margin-bottom:18px}.campus-auth-form .input-group-text,.campus-auth-form .form-control{border:0;background:transparent;box-shadow:none}.campus-auth-form .input-group-text{color:#6da7a4}.campus-auth-form .form-control{padding-left:4px}.campus-auth-form .password-toggle{border:0;background:transparent;color:#6da7a4;padding:0 4px 0 10px;line-height:1}.campus-auth-form .password-toggle:hover,.campus-auth-form .password-toggle:focus-visible{color:#2f8f88}.campus-auth-form .password-toggle:focus-visible{outline:2px solid #4db5a8;outline-offset:3px;border-radius:4px}.campus-auth-form .btn-primary{background:#4db5a8;border:0;border-radius:999px;padding:11px 25px}.campus-auth-links{display:flex;justify-content:space-between;gap:15px;font-size:11px;margin-top:17px}.campus-auth-links a{color:#3c9991;font-weight:700}.campus-auth-links span{color:#84979c}.campus-auth-note{font-size:11px;color:#84979c;border-top:1px solid #e4eeee;padding-top:16px;margin-top:28px}.campus-auth-note i{color:#4db5a8;margin-right:5px}.campus-auth-visual{position:relative;overflow:hidden;background:linear-gradient(145deg,#d2efed,#8ad3cf 48%,#53bbae);display:flex;align-items:center;justify-content:center}.campus-orbit{position:absolute;border:34px solid rgba(255,255,255,.24);border-radius:50%}.orbit-a{width:550px;height:550px;right:-250px;bottom:-215px}.orbit-b{width:425px;height:425px;right:-180px;bottom:-150px;border-width:27px}.orbit-c{width:315px;height:315px;right:-110px;bottom:-92px;border-width:18px}.campus-laptop{width:265px;height:205px;background:rgba(255,255,255,.87);border-radius:10px 10px 24px 24px;padding:12px;transform:rotate(-8deg);box-shadow:0 25px 35px rgba(37,111,109,.22);z-index:1}.campus-screen{height:142px;border:4px solid #55417a;background:#e2f5f3;color:#55417a;display:flex;flex-direction:column;justify-content:center;align-items:center}.campus-screen i{font-size:58px}.campus-screen span{font-size:10px;font-weight:800}.campus-auth-caption{position:absolute;bottom:42px;z-index:2;text-align:center;color:#fff}.campus-auth-caption strong,.campus-auth-caption span{display:block}.campus-auth-caption strong{font-size:21px}.campus-auth-caption span{font-size:11px;margin-top:4px}@media(max-width:760px){.campus-auth-shell{width:calc(100% - 26px);padding-top:12px}.campus-auth-nav nav{display:none}.campus-auth-card{grid-template-columns:1fr}.campus-auth-visual{order:-1;min-height:240px}.campus-laptop{transform:scale(.68) rotate(-8deg);margin-top:-20px}.campus-auth-caption{bottom:18px}.campus-auth-form{padding:34px 24px 40px}}
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const messageDiv = document.getElementById('loginMessage');

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