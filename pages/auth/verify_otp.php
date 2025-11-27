<?php
$email = $_GET['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - BatStateU Clinic</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background-image: url('/booking-management/image/malvar-slider-3.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
            backdrop-filter: blur(8px);

            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 450px;
        }

        .form-wrapper {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            animation: fadeIn .5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* HEADER */
        .form-header {
            background: linear-gradient(135deg, #C8102E 0%, #2A8BC4 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-weight: bold;
            font-size: 1.5rem;
            color: #2563eb;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .form-container {
            padding: 40px;
        }

        .form-title {
            font-size: 1.5rem;
            text-align: center;
            font-weight: 600;
        }

        .form-subtitle {
            color: #6b7280;
            text-align: center;
            font-size: .95rem;
            margin: 10px 0 25px;
        }

        /* INPUT */
        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            font-size: .875rem;
            color: #374151;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }

        .input-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            outline: none;
            font-size: 10px;
            letter-spacing: 4px;
            text-align: center;
            font-weight: 600;
            transition: 0.25s;

        }

        .input-group input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .2);
        }

        /* BUTTON */
        .btn-primary {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            font-size: 1rem;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.25s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, .3);
        }

        /* FOOTER */
        .form-footer {
            margin-top: 20px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 15px;
        }

        .form-footer a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        .form-footer a:hover {
            color: #4f46e5;
        }

        /* MOBILE */
        @media (max-width: 767px) {
            .form-header {
                padding: 30px 25px;
            }

            .form-container {
                padding: 30px 25px;
            }

            .logo {
                width: 70px;
                height: 70px;
            }

            .form-title {
                font-size: 1.25rem;
            }
        }
    </style>

</head>

<body>

    <div class="container">
        <div class="form-wrapper">

            <!-- HEADER -->
            <div class="form-header">
                <div class="logo">BSU</div>
                <h1>BatStateU Clinic</h1>
                <p>JPLPC - Malvar Campus</p>
            </div>

            <!-- FORM -->
            <div class="form-container">
                <h2 class="form-title">Verify OTP</h2>
                <p class="form-subtitle">
                    We sent a 6-digit OTP to<br>
                    <strong><?php echo htmlspecialchars($email); ?></strong>
                </p>

                <form id="otpForm">
                    <input type="hidden" id="email" value="<?php echo htmlspecialchars($email); ?>">

                    <div class="input-group">
                        <label>Enter OTP</label>
                        <input type="text" id="otp" maxlength="6" required placeholder="Enter OTP">
                    </div>

                    <button type="submit" class="btn-primary">Verify OTP</button>
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