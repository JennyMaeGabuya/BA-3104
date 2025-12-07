<?php
include('db.php');

function encrypt_data($data) {
    $key = 'mysecretkey12345';
    return openssl_encrypt($data, 'AES-128-ECB', $key);
}

function decrypt_data($data) {
    $key = 'mysecretkey12345';
    return openssl_decrypt($data, 'AES-128-ECB', $key);
}

$message = '';
$message_type = '';

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password_plain = $_POST['password'];
    $confirm_plain = $_POST['confirm_password'] ?? '';

    // Enforce minimum password length (10 characters)
    if (strlen($password_plain) < 10) {
        $message = "❌ Password must be at least 10 characters long!";
        $message_type = "error";
    } elseif ($password_plain !== $confirm_plain) {
        $message = "❌ Password and Confirm Password do not match!";
        $message_type = "error";
    } else {
        $password = password_hash($password_plain, PASSWORD_DEFAULT);

        $username_encrypted = encrypt_data($username);
        $email_encrypted = encrypt_data($email);

        $check = $conn->query("SELECT * FROM users");
        $exists = false;
        $duplicate_type = '';

        while ($row = $check->fetch_assoc()) {
            if (decrypt_data($row['username_encrypted']) === $username) {
                $exists = true;
                $duplicate_type = 'username';
                break;
            }
            if (decrypt_data($row['email_encrypted']) === $email) {
                $exists = true;
                $duplicate_type = 'email';
                break;
            }
        }   

        if ($exists) {
            $message = "❌ This $duplicate_type is already registered!";
            $message_type = "error";
        } else {
            $sql = "INSERT INTO users (username_encrypted, email_encrypted, password_hashed) 
                    VALUES ('$username_encrypted', '$email_encrypted', '$password')";
            if ($conn->query($sql) === TRUE) {
                $message = "✅ Account created successfully!";
                $message_type = "success";
            } else {
                $message = "❌ Error: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}

$prefill_username = ($message_type === 'success') ? '' : (isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '');
$prefill_email = ($message_type === 'success') ? '' : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BatStateU Complaint System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auth-common.css">
    <link rel="stylesheet" href="css/register-style.css">
    <style>
        :root {
            --primary-color: #b30000;
            --primary-dark: #8c0000;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f9f9f9;
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-container img {
            height: 80px;
            margin-bottom: 1rem;
        }

        .logo-container h1 {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .logo-container p {
            color: #666;
            font-size: 0.9rem;
        }

        .card {
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(179, 0, 0, 0.1);
            outline: none;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-links a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .card {
                padding: 1.5rem;
            }
            
            .logo-container h1 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="media/bsulogo.png" alt="BatStateU Logo">
            <h1>Student Complaint System</h1>
            <p>Batangas State University - JPLPC Malvar Campus</p>
        </div>
        
        <div class="card">
            <form method="POST" action="" class="auth-form" id="register-form">
                <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>" id="reg-message" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close-btn" aria-label="Dismiss message">&times;</button>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo $prefill_username; ?>" 
                           required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo $prefill_email; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           minlength="10"
                           onkeyup="checkPasswordStrength(this.value)" required>
                    <div id="password-strength" class="password-strength"></div>
                    <div id="password-hint" class="password-hint">Use 10 or more characters with a mix of letters, numbers & symbols</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="10" required>
                </div>
                
                <button type="submit" name="register" class="btn">Create Account</button>
                
                <div class="auth-links">
                    Already have an account? <a href="login.php">Sign in</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('password-strength');
        const hint = document.getElementById('password-hint');
        
        if (!strengthBar || !hint) return;
        
        strengthBar.className = 'password-strength';
        
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumbers = /\d/.test(password);
        const hasSpecialChars = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        
        let strength = 0;
        if (password.length >= 10) strength++;
        if (hasUpperCase) strength++;
        if (hasLowerCase) strength++;
        if (hasNumbers) strength++;
        if (hasSpecialChars) strength++;
        
        if (password.length === 0) {
            strengthBar.style.width = '0%';
            hint.textContent = 'Use 10 or more characters with a mix of letters, numbers & symbols';
            hint.style.color = '#666';
        } else if (strength <= 2) {
            strengthBar.className = 'password-strength weak';
            hint.textContent = 'Weak password';
            hint.style.color = '#ff4444';
        } else if (strength <= 4) {
            strengthBar.className = 'password-strength medium';
            hint.textContent = 'Medium strength password';
            hint.style.color = '#ffbb33';
        } else {
            strengthBar.className = 'password-strength strong';
            hint.textContent = 'Strong password!';
            hint.style.color = '#00C851';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('register-form');
        const message = document.getElementById('reg-message');
        
        if (message) {
            const dismiss = () => { message.style.display = 'none'; };
            const closeBtn = message.querySelector('.close-btn');
            if (closeBtn) closeBtn.addEventListener('click', dismiss);
            setTimeout(dismiss, 4000);
        }

        <?php if ($message_type === 'success'): ?>
        if (form) form.reset();
        setTimeout(() => { window.location.href = 'login.php'; }, 2000);
        <?php endif; ?>

        // Quick username availability check (standalone register page)
        const usernameInput = document.getElementById('username');
        if (usernameInput) {
            const feedback = document.createElement('div');
            feedback.id = 'username-feedback';
            feedback.style.marginTop = '4px';
            feedback.style.fontSize = '0.85rem';
            usernameInput.parentNode.appendChild(feedback);

            let usernameCheckTimeout = null;

            function checkUsernameAvailability() {
                const value = usernameInput.value.trim();
                feedback.textContent = '';
                usernameInput.setCustomValidity('');

                if (value.length < 3) {
                    return;
                }

                const params = new URLSearchParams();
                params.append('ajax', 'check_username');
                params.append('username', value);

                // Reuse login.php endpoint for username availability
                fetch('login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                })
                .then(r => r.json())
                .then(data => {
                    if (data.exists) {
                        feedback.textContent = 'This username is already taken. Please choose another.';
                        feedback.style.color = '#b30000';
                        usernameInput.setCustomValidity('Username already taken');
                    } else {
                        feedback.textContent = 'Username is available.';
                        feedback.style.color = '#0a7a27';
                        usernameInput.setCustomValidity('');
                    }
                })
                .catch(() => {
                    feedback.textContent = '';
                    usernameInput.setCustomValidity('');
                });
            }

            usernameInput.addEventListener('input', function () {
                clearTimeout(usernameCheckTimeout);
                usernameCheckTimeout = setTimeout(checkUsernameAvailability, 350);
            });
            usernameInput.addEventListener('blur', checkUsernameAvailability);
        }
    });

    document.querySelector('form')?.addEventListener('submit', function(e) {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        return true;
    });
    </script>
</body>
</html>
