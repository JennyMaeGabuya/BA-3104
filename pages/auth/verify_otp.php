<?php
$email = $_GET['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - BatStateU Clinic</title>
    <link rel="stylesheet" href="/booking-management/css/auth_css/auth.css">
</head>

<body>

    <div class="container">
        <div class="form-wrapper">

            <!-- HEADER -->
            <div class="form-header">
                <div class="logo">
                    <div class="image">
                        <img src="/booking-management/image/bat.png" alt="BSU Logo">
                    </div>
                </div>
                <h1>BatStateU Clinic</h1>
                <p>JPLPC - Malvar Campus</p>
            </div>

            <!-- FORM -->
            <div class="form-container">
                <h2 class="form-title">Verify OTP</h2>
                <p class="form-subtitle">
                    We have sent a 6-digit OTP to<br>
                    <strong><?php echo htmlspecialchars($email); ?></strong>
                </p>

                <form id="otpForm">
                    <input type="hidden" id="email" value="<?php echo htmlspecialchars($email); ?>">

                    <div class="input-group">
                        <label>Enter OTP</label>
                        <input type="text" id="otp" maxlength="6" required placeholder="Enter OTP">
                    </div>

                    <button type="submit" class="btn btn-primary">Verify OTP</button>
                </form>

                <div class="form-footer">
                    <p><a href="forgot_password.php">← Back to Forgot Password</a></p>
                </div>
            </div>

        </div>
    </div>

    <!-- Your original JS preserved -->
    <script>
        document.getElementById("otpForm").addEventListener("submit", function(e) {
            e.preventDefault();

            const email = document.getElementById("email").value;
            const otp = document.getElementById("otp").value;

            const formData = new FormData();
            formData.append("email", email);
            formData.append("otp", otp);

            fetch("../../controllers/verify_otp_controller.php", {
                    method: "POST",
                    body: formData
                })
                .then(r => r.text())
                .then(result => {
                    if (result.trim() === "OK") {
                        window.location.href = "reset_password.php?email=" + email;
                    } else {
                        alert(result);
                    }
                });
        });
    </script>

</body>

</html>