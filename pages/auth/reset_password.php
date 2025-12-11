<?php
$email = $_GET['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BatStateU Clinic</title>
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

                <h2 class="form-title">Reset Password</h2>
                <p class="form-subtitle">Create a new password for your account</p>

                <form id="resetForm">
                    <input type="hidden" id="email" value="<?php echo htmlspecialchars($email); ?>">

                    <div class="input-group">
                        <label>New Password</label>
                        <input type="password" id="password" minlength="7" required placeholder="Enter new password">
                    </div>

                    <div class="input-group">
                        <label>Confirm Password</label>
                        <input type="password" id="confirm" minlength="7" required placeholder="Confirm new password">
                    </div>

                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>

                <div class="form-footer">
                    <p><a href="login.php">← Back to Login</a></p>
                </div>

            </div>

        </div>
    </div>

    <!-- JS Logic (unchanged) -->
    <script>
        document.getElementById("resetForm").addEventListener("submit", function(e) {
            e.preventDefault();

            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;
            const confirm = document.getElementById("confirm").value;

            const formData = new FormData();
            formData.append("email", email);
            formData.append("password", password);
            formData.append("confirm", confirm);

            fetch("../../controllers/reset_password_controller.php", {
                    method: "POST",
                    body: formData
                })
                .then(r => r.text())
                .then(result => {
                    alert(result);

                    if (result === "Password reset successful.") {
                        window.location.href = "login.php";
                    }
                    if (password.length < 7) {
                        alert("Password must be at least 7 characters long.");
                        return;
                    }

                });
        });
    </script>

</body>

</html>