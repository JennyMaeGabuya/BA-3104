<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email || !$password) {
    echo json_encode(['success' => false, 'msg' => 'All fields are required.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'msg' => 'Invalid email or password.']);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'msg' => 'Invalid email or password.']);
    exit;
}

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['name']    = $user['name'];
$_SESSION['email']   = $user['email'];
$_SESSION['phone']   = $user['phone'];
$_SESSION['role']    = $user['role'];
$_SESSION['logged_in'] = true;

// Redirect based on role
if ($user['role'] === 'admin') {
    $redirect = '../admin/admin_dashboard.php';
} else {
    $redirect = '../patient/patient.php';
}

echo json_encode([
    'success' => true,
    'msg'     => 'Login successful!',
    'redirect' => $redirect
]);
