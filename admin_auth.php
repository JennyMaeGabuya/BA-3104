<?php
// Use a separate session name for admin accounts so user logins don't overwrite admin sessions
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/db_config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email) $errors[] = 'Email is required.';
if (!$password) $errors[] = 'Password is required.';

if (empty($errors)) {
    $stmt = $pdo->prepare('SELECT id, password, first_name, last_name, email, phone, user_type FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password']) && ($user['user_type'] ?? '') === 'Admin') {
        $_SESSION['user_id'] = $user['id'];
        // set explicit session fields for admin as well
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        // ensure user_type is stored so protected pages can verify role
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
        $_SESSION['fullname'] = $_SESSION['user_name'];

        // Record login in sessions table (safe): regenerate session id, avoid duplicate-key errors, and log DB exceptions
        try {
            session_regenerate_id(true);
            $session_id = session_id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $insert = $pdo->prepare('INSERT INTO adminsessions (admin_id, session_id, ip_address, admin_agent) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP, ip_address = VALUES(ip_address), admin_agent = VALUES(admin_agent), is_active = TRUE');
            $insert->execute([$user['id'], $session_id, $ip, $ua]);
        } catch (PDOException $e) {
            $msg = date('[Y-m-d H:i:s] ') . 'Session insert error (admin_auth): ' . $e->getMessage() . PHP_EOL;
            @file_put_contents(__DIR__ . '/logs/db_errors.log', $msg, FILE_APPEND);
        }

        // Redirect admin to admin area (change to actual admin dashboard)
        header('Location: AdminDB/admin.php');
        exit;
    }

    $errors[] = 'Invalid email or password or not authorized as Admin.';
}

$_SESSION['login_errors'] = $errors;
header('Location: login.php');
exit;
?>
