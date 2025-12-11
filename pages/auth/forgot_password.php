<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/booking-management/css/auth_css/auth.css">
    <title>Forgot Password - BatStateU Clinic</title>
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

                <h2 class="form-title">Forgot Password</h2>
                <p class="form-subtitle">Enter your email to receive an OTP</p>

                <form id="forgotForm">
                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" id="forgotEmail" name="email" required placeholder="Enter your email">
                    </div>

                    <button type="submit" class="btn btn-primary">Send OTP</button>
                </form>

                <div class="form-footer">
                    <p><a href="login.php">← Back to Login</a></p>
                </div>

            </div>

        </div>
    </div>

    <!-- WORKING JS FROM YOUR CODE -->
    <script>
        document.getElementById("forgotForm").addEventListener("submit", function(e) {
            e.preventDefault();

            const email = document.getElementById("forgotEmail").value;

            const formData = new FormData();
            formData.append("email", email);

            fetch("/booking-management/controllers/otp_controller.php", {
                    method: "POST",
                    body: formData
                })
                .then(r => r.json())
                .then(result => {
                    alert(result.msg);

                    if (result.success) {
                        window.location.href = "verify_otp.php?email=" + email;
                    }
                });
        });
    </script>

</body>

</html>