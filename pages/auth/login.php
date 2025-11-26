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
                <div class="logo">BSU</div>
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

                    <div class="input-group">
                        <label for="loginPassword">Password</label>
                        <input type="password" id="loginPassword" name="password" placeholder="Enter your password" autocomplete="current-password" required>
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
    <script src="../../js/auth_js/login.js"></script>
</body>

</html>