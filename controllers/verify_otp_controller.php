<?php
require_once __DIR__ . '/../config/db_connection.php';

$email = $_POST['email'];
$otp = $_POST['otp'];

$stmt = $conn->prepare("SELECT otp, otp_expire FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res['otp'] != $otp) {
    echo "Invalid OTP.";
    exit;
}

if (strtotime($res['otp_expire']) < time()) {
    echo "OTP expired.";
    exit;
}

echo "OK";
