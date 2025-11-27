<?php
header("Content-Type: application/json");

// Debug mode
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "msg" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Safe POST data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$gender = $_POST['gender'] ?? '';
$address = $_POST['address'] ?? '';
$dob = $_POST['date_of_birth'] ?? '';

$sql = "UPDATE users 
        SET name=?, email=?, phone=?, gender=?, address=?, date_of_birth=? 
        WHERE user_id=?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "msg" => $conn->error]);
    exit;
}

$stmt->bind_param("ssssssi", $name, $email, $phone, $gender, $address, $dob, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "msg" => "Profile updated successfully"]);
} else {
    echo json_encode(["success" => false, "msg" => $stmt->error]);
}
