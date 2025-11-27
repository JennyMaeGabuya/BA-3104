<?php
require_once __DIR__ . '/../config/db_connection.php';

$email = $_POST['email'];
$password = $_POST['password'];
$confirm = $_POST['confirm'];

if ($password !== $confirm) {
    echo "Passwords do not match.";
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password=?, otp=NULL, otp_expire=NULL WHERE email=?");
$stmt->bind_param("ss", $hashed, $email);
$stmt->execute();

echo "Password reset successful.";
