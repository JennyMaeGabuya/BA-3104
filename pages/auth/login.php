<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BatStateU Clinic</title>
    <link rel="stylesheet" href="../../css/auth_css/auth.css">
</head>

<body>
    <div class="container">
        <div class="form-wrapper">
            <div class="form-header">

                <div class="logo">
                    <div class="image">
                        <img src="/booking-management/image/bat.png" alt="BSU Logo">
                    </div>
                </div>

                <h1>BatStateU Clinic</h1>
                <p>JPLPC - Malvar Campus</p>
            </div>

            <div class="form-container">
                <h2 class="form-title">Welcome Back</h2>
                <p class="form-subtitle">Login to your account</p>

                <form id="loginForm">
                    <div class="input-group">
                        <label for="loginEmail">Email Address</label>
                        <input type="email" id="loginEmail" name="email" placeholder="Enter your email" autocomplete="email" required>
                    </div>

                    <div class="input-group password-group">
                        <label for="loginPassword">Password</label>

                        <div class="password-wrapper">
                            <input type="password" id="loginPassword" name="password"
                                placeholder="Enter your password" autocomplete="current-password" required>

                            <span class="toggle-password" onclick="togglePassword()" id="togglePasswordIcon">
                                <!-- default eye icon -->
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </span>
                        </div>
                    </div>





                    <button type="submit" class="btn btn-primary">Login</button>
                    <div class="forgot">
                        <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                    </div>
                </form>

                <div class="form-footer">
                    <p>Don't have an account? <a href="../auth/signup.php">Sign up</a></p>
                </div>
            </div>
        </div>

        <div class="back-link">
            <a href="../landing_page.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Home
            </a>
        </div>
    </div>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById("loginPassword");
            const iconContainer = document.getElementById("togglePasswordIcon");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                iconContainer.innerHTML = `
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12C2.5 8 6.5 5 12 5c2.7 0 5.1.8 7 2.3" />
            <path d="M23 12c-1.5 4-5.5 7-11 7-2.7 0-5.1-.8-7-2.3" />
            <circle cx="12" cy="12" r="3" />
            <line x1="3" y1="3" x2="21" y2="21" />
        </svg>`;
            } else {
                passwordField.type = "password";
                iconContainer.innerHTML = `
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>`;
            }
        }
    </script>

    <script src="../../js/auth_js/login.js"></script>
</body>

<div id="toast"
    style="position: fixed; top: 20px; right: 20px;
            background: #28a745; color:white;
            padding: 12px 20px; border-radius: 6px; 
            display:none; z-index: 9999;">
</div>


</html>