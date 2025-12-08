<?php
session_start();
// NOTE:'config.php' contains the function connect_db()
require_once __DIR__ . '/config.php';

try {
$pdo = connect_db();
} catch (Exception $e) {
error_log("Database connection failed: " . $e->getMessage());
header('Location: ../login.php?err=2'); 
exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Check for empty fields
if ($username === '' || $password === '') {
header('Location: ../login.php?err=1'); 
exit;
}

$stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
header('Location: ../login.php?err=1');
exit;
}

$_SESSION['userId'] = $user['id'];
$_SESSION['username'] = $user['username'];

header('Location: ../admin.php');
exit;
?>