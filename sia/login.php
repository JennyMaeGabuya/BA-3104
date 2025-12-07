<?php
include('db.php');
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

// Load flash messages (from previous POST redirects) and clear them
if (isset($_SESSION['reg_error'])) { $reg_error = $_SESSION['reg_error']; unset($_SESSION['reg_error']); }
if (isset($_SESSION['success'])) { $success = $_SESSION['success']; unset($_SESSION['success']); }
if (isset($_SESSION['open_register'])) { $openRegister = $_SESSION['open_register']; unset($_SESSION['open_register']); } else { $openRegister = false; }
if (isset($_SESSION['reset_error'])) { $reset_error = $_SESSION['reset_error']; unset($_SESSION['reset_error']); }
if (isset($_SESSION['reset_success'])) { $reset_success = $_SESSION['reset_success']; unset($_SESSION['reset_success']); }
if (isset($_SESSION['open_reset'])) { $openReset = $_SESSION['open_reset']; unset($_SESSION['open_reset']); } else { $openReset = false; }
if (isset($_SESSION['reset_step'])) { $resetStep = $_SESSION['reset_step']; unset($_SESSION['reset_step']); } else { $resetStep = 1; }


// Encryption key
define("ENC_KEY", "mysecretkey123");

// Encrypt data (username/email)
function encryptData($data) {
    return base64_encode(openssl_encrypt($data, 'AES-128-ECB', ENC_KEY));
}

// Decrypt data (username/email)
function decryptData($data) {
    return openssl_decrypt(base64_decode($data), 'AES-128-ECB', ENC_KEY);
}

// Pretty XML save
function saveXMLFormatted($xml, $filePath) {
    $dom = new DOMDocument("1.0");
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    $dom->save($filePath);
}


$xmlFile = "users.xml";

if (!file_exists($xmlFile)) {
    file_put_contents($xmlFile, "<?xml version=\"1.0\"?><users></users>");
}

$xml = simplexml_load_file($xmlFile);

// -----------------------------
// AJAX: Quick username availability check
// -----------------------------
if (isset($_POST['ajax']) && $_POST['ajax'] === 'check_username') {
    $usernameToCheck = trim($_POST['username'] ?? '');
    $exists = false;

    if ($usernameToCheck !== '') {
        foreach ($xml->user as $u) {
            $decUsername = decryptData((string)$u->username);
            if ($decUsername === $usernameToCheck) {
                $exists = true;
                break;
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists]);
    exit;
}

/* -----------------------------
   REGISTRATION PROCESS
------------------------------*/
if (isset($_POST['register'])) {

    $username = trim($_POST['reg_username']);
    $email = trim($_POST['reg_email']);
    $password = trim($_POST['reg_password']);
    $confirm  = trim($_POST['reg_confirm_password'] ?? '');

    // Enforce minimum password length (10 characters)
    if (strlen($password) < 10) {
        $_SESSION['reg_error'] = "Password must be at least 10 characters long.";
        $_SESSION['open_register'] = true;
        header('Location: login.php');
        exit;
    }

    // If passwords don't match, show error but KEEP the entered username/email
    // by not redirecting away (so $_POST values remain available to the form).
    if ($password !== $confirm) {
        $reg_error = "Password and Confirm Password do not match.";
        $openRegister = true;
    } else {

        $encUsername = encryptData($username);
        $encEmail = encryptData($email);

        // Auto-increment ID
        $lastId = 0;
        foreach ($xml->user as $u) {
            if (isset($u->id) && intval($u->id) > $lastId) {
                $lastId = intval($u->id);
            }
        }
        $newId = $lastId + 1;

        // Check duplicates (decrypt to compare)
        foreach ($xml->user as $u) {
            if (decryptData((string)$u->username) === $username) {
                // set flash and redirect back so refresh won't re-post
                $_SESSION['reg_error'] = "Username already exists!";
                $_SESSION['open_register'] = true;
                header('Location: login.php');
                exit;
            }
            if (decryptData((string)$u->email) === $email) {
                $_SESSION['reg_error'] = "Email already exists!";
                $_SESSION['open_register'] = true;
                header('Location: login.php');
                exit;
            }
        }

        // If no registration errors, register user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Save to XML
        $newUser = $xml->addChild("user");
        $newUser->addChild("id", $newId);
        $newUser->addChild("username", $encUsername);
        $newUser->addChild("email", $encEmail);
        $newUser->addChild("password", $hashedPassword);
        saveXMLFormatted($xml, $xmlFile);

        // Save to MySQL
        $stmt = $conn->prepare("INSERT INTO users (username_encrypted, password_hashed, email_encrypted) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $encUsername, $hashedPassword, $encEmail);
        $stmt->execute();

        // Set success flash and redirect to GET to avoid form resubmission
        $_SESSION['success'] = "Registration successful! You may now login.";
        header('Location: login.php?registered=1');
        exit;
    }

}

/* -----------------------------
   FORGOT PASSWORD PROCESS
------------------------------*/
// Step 1: Request reset code
if (isset($_POST['request_reset'])) {
    $email = trim($_POST['reset_email']);
    
    $found = false;
    $userId = null;
    
    foreach ($xml->user as $user) {
        $decEmail = decryptData((string)$user->email);
        if ($decEmail === $email) {
            $found = true;
            $userId = intval($user->id);
            break;
        }
    }
    
    if (!$found) {
        $_SESSION['reset_error'] = "Email not found in our system.";
        $_SESSION['open_reset'] = true;
        $_SESSION['reset_step'] = 1;
        header('Location: login.php');
        exit;
    }
    
    // Generate 6-digit code
    $resetCode = sprintf("%06d", mt_rand(0, 999999));
    $_SESSION['reset_code'] = $resetCode;
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_user_id'] = $userId;
    $_SESSION['reset_code_time'] = time();
    
    // Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '23-69926@g.batstate-u.edu.ph';
        $mail->Password = 'vcoi ufnq duxh pnyn';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        
        $mail->setFrom('23-69926@g.batstate-u.edu.ph', 'Student Complaint System');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = "Password Reset Code";
        $mail->Body = "Your password reset code is: <strong>$resetCode</strong><br><br>This code will expire in 10 minutes.";
        
        $mail->send();
        
        $_SESSION['reset_success'] = "Reset code sent to your email!";
        $_SESSION['open_reset'] = true;
        $_SESSION['reset_step'] = 2;
    } catch (Exception $e) {
        $_SESSION['reset_error'] = "Failed to send email. Error: {$mail->ErrorInfo}";
        $_SESSION['open_reset'] = true;
        $_SESSION['reset_step'] = 1;
    }
    
    header('Location: login.php');
    exit;
}

// Step 2: Verify code
if (isset($_POST['verify_code'])) {
    $enteredCode = trim($_POST['verification_code']);
    
    if (!isset($_SESSION['reset_code']) || !isset($_SESSION['reset_code_time'])) {
        $_SESSION['reset_error'] = "Session expired. Please request a new code.";
        $_SESSION['open_reset'] = true;
        $_SESSION['reset_step'] = 1;
        header('Location: login.php');
        exit;
    }
    
    // Check if code expired (10 minutes)
    if (time() - $_SESSION['reset_code_time'] > 600) {
        unset($_SESSION['reset_code'], $_SESSION['reset_code_time'], $_SESSION['reset_email'], $_SESSION['reset_user_id']);
        $_SESSION['reset_error'] = "Code expired. Please request a new one.";
        $_SESSION['open_reset'] = true;
        $_SESSION['reset_step'] = 1;
        header('Location: login.php');
        exit;
    }
    
    if ($enteredCode !== $_SESSION['reset_code']) {
        $_SESSION['reset_error'] = "Invalid code. Please try again.";
        $_SESSION['open_reset'] = true;
        $_SESSION['reset_step'] = 2;
        header('Location: login.php');
        exit;
    }
    
    $_SESSION['reset_success'] = "Code verified! Enter your new password.";
    $_SESSION['open_reset'] = true;
    $_SESSION['reset_step'] = 3;
    header('Location: login.php');
    exit;
}

// Step 3: Update password
if (isset($_POST['update_password'])) {
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);
    
    if (strlen($newPassword) < 10) {
        $_SESSION['reset_error'] = "Password must be at least 10 characters long.";
        $_SESSION['open_reset'] = true;
        $_SESSION['reset_step'] = 3;
        header('Location: login.php');
        exit;
    }
    
    if ($newPassword !== $confirmPassword) {
        $_SESSION['reset_error'] = "Passwords do not match.";
        $_SESSION['open_reset'] = true;
        $_SESSION['reset_step'] = 3;
        header('Location: login.php');
        exit;
    }
    
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_email'])) {
        $_SESSION['reset_error'] = "Session expired. Please start over.";
        $_SESSION['open_reset'] = true;
        $_SESSION['reset_step'] = 1;
        header('Location: login.php');
        exit;
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $email = $_SESSION['reset_email'];
    
    // Update XML
    foreach ($xml->user as $user) {
        $decEmail = decryptData((string)$user->email);
        if ($decEmail === $email) {
            $user->password = $hashedPassword;
            break;
        }
    }
    saveXMLFormatted($xml, $xmlFile);
    
    // Update MySQL
    $encEmail = encryptData($email);
    $stmt = $conn->prepare("UPDATE users SET password_hashed = ? WHERE email_encrypted = ?");
    $stmt->bind_param("ss", $hashedPassword, $encEmail);
    $stmt->execute();
    
    // Clear reset session data
    unset($_SESSION['reset_code'], $_SESSION['reset_code_time'], $_SESSION['reset_email'], $_SESSION['reset_user_id']);
    
    $_SESSION['success'] = "Password updated successfully! You can now login.";
    header('Location: login.php');
    exit;
}

/* -----------------------------
   LOGIN PROCESS
------------------------------*/
if (isset($_POST['login'])) {

    $username = $_POST['log_username'];
    $password = $_POST['log_password'];

    $found = false;

    foreach ($xml->user as $user) {

        $decUsername = decryptData((string)$user->username);

        if ($decUsername === $username) {

            if (password_verify($password, (string)$user->password)) {

                // ADMIN REDIRECT
                if ($username === "admin") {
                    // Map session user_id to MySQL users.id to avoid XML/MySQL mismatch
                    $encAdminUsername = encryptData($username);
                    $idLookup = $conn->prepare("SELECT id FROM users WHERE username_encrypted = ? LIMIT 1");
                    $idLookup->bind_param("s", $encAdminUsername);
                    $idLookup->execute();
                    $idRes = $idLookup->get_result();
                    if ($idRes && $idRes->num_rows > 0) {
                        $row = $idRes->fetch_assoc();
                        $_SESSION['user_id'] = (int)$row['id'];
                    } else {
                        // Fallback to XML id if not found (should not happen)
                        $_SESSION['user_id'] = (int)$user->id;
                    }
                    $idLookup->close();

                    $_SESSION['username'] = $username;
                    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, timestamp) VALUES (?, 'logged in', NOW())");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $stmt->close();
                    header("Location: admin_dashboard.php");
                    exit;
                }

                // Normal user: set session user_id from MySQL users.id
                $encUsername = encryptData($decUsername);
                $idLookup = $conn->prepare("SELECT id, email_encrypted FROM users WHERE username_encrypted = ? LIMIT 1");
                $idLookup->bind_param("s", $encUsername);
                $idLookup->execute();
                $idRes = $idLookup->get_result();
                if ($idRes && $idRes->num_rows > 0) {
                    $row = $idRes->fetch_assoc();
                    $_SESSION['user_id'] = (int)$row['id'];
                    $_SESSION['email'] = decryptData($row['email_encrypted']);
                } else {
                    // Fallback: use XML id + email
                    $_SESSION['user_id'] = (int)$user->id;
                    $_SESSION['email'] = decryptData((string)$user->email);
                }
                $idLookup->close();

                $_SESSION['username'] = $decUsername;
                // NORMAL USER LOGIN
                // Show welcome modal on next page load
                $_SESSION['show_welcome'] = true;
                $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, timestamp) VALUES (?, 'logged in', NOW())");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();

                header("Location: user_complaints.php");
                exit;
            }

            $login_error = "Incorrect password!";
            $found = true;
            break;
        }
    }

    if (!$found) {
        $login_error = "Username not found!";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - BatStateU Complaint System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auth-common.css">
    <style>
        /* Small adjustments specific to this page */
        .card form + hr { margin: 1.5rem 0; border: none; border-top: 1px solid rgba(0,0,0,0.06); }
        .small-note { font-size: 0.9rem; color: #666; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero-container">
            <div class="logo-container">
                <img src="media/bsulogo.png" alt="BatStateU Logo">
                <h1>Student Complaint System</h1>
                <p>Batangas State University - JPLPC Malvar Campus</p>
            </div>
        </div>

        <div class="card">
            <!-- Back to Home Arrow -->
            <a href="index.php" class="back-arrow" aria-label="Back to Home" title="Back to Home">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>
            
            <!-- Messages -->
            <?php if (isset($login_error)): ?>
                <div class="message error" id="msg" role="alert">
                    <?php echo htmlspecialchars($login_error); ?>
                    <button type="button" class="close-btn" aria-label="Dismiss">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="message success" id="msg-success" role="status">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="close-btn" aria-label="Dismiss">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="auth-form" id="login-form" style="margin-top:1rem;">
                <h3 style="margin-bottom:1rem;">Login</h3>
                <div class="form-group">
                    <label for="log_username">Username</label>
                    <input type="text" id="log_username" name="log_username" class="form-control" required value="<?php echo isset($_POST['log_username'])?htmlspecialchars($_POST['log_username']):''; ?>">
                </div>

                <div class="form-group">
                    <label for="log_password">Password</label>
                    <input type="password" id="log_password" name="log_password" class="form-control" required>
                </div>

                <button type="submit" name="login" class="btn">Login</button>

                <div class="auth-links" style="margin-top:0.75rem;">
                    <span>Forgot password? <a href="#" class="forgot-password">Reset</a></span>
                    <br>
                    Don't have an account? <a href="#" class="open-register">Register here</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="reset-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal">
            <button class="modal-close" aria-label="Close">&times;</button>
            
            <?php if (isset($reset_error)): ?>
                <div class="message error" id="reset-msg" role="alert">
                    <?php echo htmlspecialchars($reset_error); ?>
                    <button type="button" class="close-btn" aria-label="Dismiss">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($reset_success)): ?>
                <div class="message success" id="reset-msg-success" role="status">
                    <?php echo htmlspecialchars($reset_success); ?>
                    <button type="button" class="close-btn" aria-label="Dismiss">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Step 1: Enter Email -->
            <div id="reset-step-1" style="display:none;">
                <h3 style="margin-bottom:1rem;">Reset Password</h3>
                <p style="margin-bottom:1rem;">Enter your email address to receive a verification code.</p>
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="reset_email">Email Address</label>
                        <input type="email" id="reset_email" name="reset_email" class="form-control" required>
                    </div>
                    <button type="submit" name="request_reset" class="btn">Send Code</button>
                </form>
            </div>
            
            <!-- Step 2: Enter 6-digit Code -->
            <div id="reset-step-2" style="display:none;">
                <h3 style="margin-bottom:1rem;">Verify Code</h3>
                <p style="margin-bottom:1rem;">Enter the 6-digit code sent to your email.</p>
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="verification_code">Verification Code</label>
                        <input type="text" id="verification_code" name="verification_code" class="form-control" pattern="[0-9]{6}" maxlength="6" required>
                    </div>
                    <button type="submit" name="verify_code" class="btn">Verify Code</button>
                    <div style="margin-top:0.6rem; text-align:center;">
                        <a href="#" class="reset-restart">Request New Code</a>
                    </div>
                </form>
            </div>
            
            <!-- Step 3: Enter New Password -->
            <div id="reset-step-3" style="display:none;">
                <h3 style="margin-bottom:1rem;">New Password</h3>
                <p style="margin-bottom:1rem;">Enter your new password (minimum 10 characters).</p>
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" minlength="10" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="10" required>
                    </div>
                    <button type="submit" name="update_password" class="btn">Update Password</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Register Modal (hidden by default) -->
    <div id="register-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal">
            <button class="modal-close" aria-label="Close">&times;</button>
            <h3 style="margin-bottom:1rem;">Register</h3>
            <form method="POST" class="auth-form" id="register-form">
                <?php if (isset($reg_error)): ?>
                    <div class="message error" id="reg-msg" role="alert">
                        <?php echo htmlspecialchars($reg_error); ?>
                        <button type="button" class="close-btn" aria-label="Dismiss">&times;</button>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="reg_username">Username</label>
                    <input type="text" id="reg_username" name="reg_username" class="form-control" required value="<?php echo (isset($success) ? '' : (isset($_POST['reg_username'])?htmlspecialchars($_POST['reg_username']):'')); ?>">
                </div>

                <div class="form-group">
                    <label for="reg_email">Email</label>
                    <input type="email" id="reg_email" name="reg_email" class="form-control" required value="<?php echo (isset($success) ? '' : (isset($_POST['reg_email'])?htmlspecialchars($_POST['reg_email']):'')); ?>">
                </div>

                <div class="form-group">
                    <label for="reg_password">Password</label>
                    <input type="password" id="reg_password" name="reg_password" class="form-control" minlength="10" required value="<?php echo (isset($success) ? '' : ''); ?>">
                </div>

                <div class="form-group">
                    <label for="reg_confirm_password">Confirm Password</label>
                    <input type="password" id="reg_confirm_password" name="reg_confirm_password" class="form-control" minlength="10" required value="">
                </div>

                <button type="submit" name="register" class="btn">Create Account</button>
                <div style="margin-top:0.6rem; text-align:center;">
                    <a href="#" class="register-reset">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const msg = document.getElementById('msg');
        const msgS = document.getElementById('msg-success');
        const resetMsg = document.getElementById('reset-msg');
        const resetMsgS = document.getElementById('reset-msg-success');
        
        [msg, msgS, resetMsg, resetMsgS].forEach(m => {
            if (!m) return;
            const close = m.querySelector('.close-btn');
            if (close) close.addEventListener('click', () => m.style.display = 'none');
            setTimeout(() => { if (m) m.style.display = 'none'; }, 4500);
        });

        // Forgot password link opens reset modal
        const forgotLink = document.querySelector('.forgot-password');
        const resetModal = document.getElementById('reset-modal');
        const resetCloseBtn = resetModal?.querySelector('.modal-close');
        
        function openResetModal(step) {
            if (!resetModal) return;
            // Hide all steps
            document.getElementById('reset-step-1').style.display = 'none';
            document.getElementById('reset-step-2').style.display = 'none';
            document.getElementById('reset-step-3').style.display = 'none';
            // Show specified step
            document.getElementById('reset-step-' + step).style.display = 'block';
            resetModal.classList.add('show');
            resetModal.setAttribute('aria-hidden','false');
            document.body.classList.add('modal-open');
        }
        
        function closeResetModal() {
            if (!resetModal) return;
            resetModal.classList.remove('show');
            resetModal.setAttribute('aria-hidden','true');
            document.body.classList.remove('modal-open');
        }
        
        if (forgotLink) {
            forgotLink.addEventListener('click', function(e) {
                e.preventDefault();
                openResetModal(1);
            });
        }
        
        if (resetCloseBtn) resetCloseBtn.addEventListener('click', closeResetModal);
        if (resetModal) resetModal.addEventListener('click', function(e){ if (e.target === resetModal) closeResetModal(); });
        
        // Reset restart link
        const resetRestart = document.querySelector('.reset-restart');
        if (resetRestart) {
            resetRestart.addEventListener('click', function(e) {
                e.preventDefault();
                openResetModal(1);
            });
        }

        // Quick username availability check for register modal
        const regUsernameInput = document.getElementById('reg_username');
        if (regUsernameInput) {
            const feedback = document.createElement('div');
            feedback.id = 'reg-username-feedback';
            feedback.style.marginTop = '4px';
            feedback.style.fontSize = '0.85rem';
            regUsernameInput.parentNode.appendChild(feedback);

            let usernameCheckTimeout = null;

            function checkUsernameAvailability() {
                const value = regUsernameInput.value.trim();
                feedback.textContent = '';
                regUsernameInput.setCustomValidity('');

                if (value.length < 3) {
                    return;
                }

                const params = new URLSearchParams();
                params.append('ajax', 'check_username');
                params.append('username', value);

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
                        regUsernameInput.setCustomValidity('Username already taken');
                    } else {
                        feedback.textContent = 'Username is available.';
                        feedback.style.color = '#0a7a27';
                        regUsernameInput.setCustomValidity('');
                    }
                })
                .catch(() => {
                    // fail silently; don't block form submit
                    feedback.textContent = '';
                    regUsernameInput.setCustomValidity('');
                });
            }

            regUsernameInput.addEventListener('input', function () {
                clearTimeout(usernameCheckTimeout);
                usernameCheckTimeout = setTimeout(checkUsernameAvailability, 350);
            });
            regUsernameInput.addEventListener('blur', checkUsernameAvailability);
        }
        
        // If server-side reset process is active
        var shouldOpenReset = <?php echo ($openReset ? 'true' : 'false'); ?>;
        var resetStepNum = <?php echo $resetStep; ?>;
        if (shouldOpenReset) {
            openResetModal(resetStepNum);
        }

        // Clear login inputs when the "Reset" (forgot-password) link is clicked
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            const userInput = loginForm.querySelector('#log_username');
            const passInput = loginForm.querySelector('#log_password');
            if (userInput) userInput.value = '';
            if (passInput) passInput.value = '';
            const loginMsg = document.getElementById('msg');
            if (loginMsg) loginMsg.style.display = 'none';
            if (userInput) userInput.focus();
        }

        // Clear register modal inputs when the "Reset" link is clicked
        const regResetLink = document.querySelector('.register-reset');
        const regForm = document.getElementById('register-form');
        if (regResetLink && regForm) {
            regResetLink.addEventListener('click', function(e) {
                e.preventDefault();
                regForm.reset();
                const regMsg = document.getElementById('reg-msg');
                if (regMsg) regMsg.style.display = 'none';
                const regUser = regForm.querySelector('#reg_username');
                if (regUser) regUser.focus();
            });
        }
    });
    </script>
    <style>
    /* Modal styles: fade + scale animation */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 260ms cubic-bezier(.2,.9,.2,1);
    }
    .modal-overlay.show { opacity: 1; pointer-events: auto; }
    .modal {
        position: relative;
        width: 100%;
        max-width: 520px;
        background: rgba(255,255,255,0.99);
        padding: 1.75rem;
        border-radius: var(--border-radius);
        box-shadow: 0 18px 50px rgba(0,0,0,0.28);
        transform: translateY(10px) scale(0.98);
        opacity: 0;
        transition: transform 280ms cubic-bezier(.2,.9,.2,1), opacity 240ms ease;
    }
    .modal-overlay.show .modal { transform: translateY(0) scale(1); opacity: 1; }
    .modal-close {
        position: absolute;
        right: 18px;
        top: 14px;
        background: transparent;
        border: none;
        font-size: 1.4rem;
        cursor: pointer;
        color: #333;
    }
    @media (max-width:480px) {
        .modal { padding: 1rem; }
    }
    </style>

    <script>
    (function(){
        const openBtns = document.querySelectorAll('.open-register');
        const modal = document.getElementById('register-modal');
        const closeBtn = modal?.querySelector('.modal-close');

        function openModal() {
            if (!modal) return;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden','false');
            // add body class so background can be blurred via CSS
            document.body.classList.add('modal-open');
        }
        function closeModal() {
            if (!modal) return;
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden','true');
            // remove blur
            document.body.classList.remove('modal-open');
        }

        openBtns.forEach(b => b.addEventListener('click', function(e){ e.preventDefault(); openModal(); }));
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

        // If registration was attempted server-side, open modal so user sees messages/fields
        var shouldOpen = <?php echo ($openRegister ? 'true' : 'false'); ?>;
        var regSuccess = <?php echo (isset($success) ? 'true' : 'false'); ?>;
        if (shouldOpen) openModal();

        // If registration succeeded, ensure modal is closed, clear register fields, and focus login
        if (regSuccess) {
            // close the modal
            closeModal();

            // clear register form fields and hide any register message
            const regForm = document.getElementById('register-form');
            if (regForm) {
                regForm.reset();
                const regMsg = document.getElementById('reg-msg');
                if (regMsg) regMsg.style.display = 'none';
            }

            const loginInput = document.getElementById('log_username');
            if (loginInput) {
                // small timeout to ensure DOM focus after visual close
                setTimeout(() => loginInput.focus(), 120);
            }
        }
    })();
    </script>
</body>
</html>
