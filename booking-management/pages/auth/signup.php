<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - BatStateU Clinic</title>
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
                <h2 class="form-title">Create Account</h2>
                <p class="form-subtitle">Sign up to get started</p>

                <form id="signupForm" method="POST" action="../../controllers/signup_controllers.php">

                    <div class="input-group">
                        <label for="signupName">Full Name</label>
                        <input type="text" id="signupName" name="name" placeholder="Enter your full name" required>
                    </div>

                    <div class="input-group">
                        <label for="signupEmail">Email Address</label>
                        <input type="email" id="signupEmail" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="input-group">
                        <label for="signupPhone">Phone Number</label>
                        <input type="tel" id="signupPhone" name="phone" placeholder="+63 912 345 6789" required>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label for="signupPassword">Password</label>
                            <input type="password" id="signupPassword" name="password" placeholder="Create a password" required>
                        </div>

                        <div class="input-group">
                            <label for="signupConfirmPassword">Confirm Password</label>
                            <input type="password" id="signupConfirmPassword" name="confirm" placeholder="Confirm password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Sign Up</button>
                </form>

                <div class="form-footer">
                    <p>Already have an account? <a href="login.php">Login</a></p>
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

    <script src="../../js/auth_js/signup.js"></script>
</body>

</html>