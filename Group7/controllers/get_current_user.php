<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["logged_in" => false]);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT user_id, name, email, phone, gender, address, date_of_birth 
                        FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "logged_in" => true,
    "user_id" => $result['user_id'],
    "name" => $result['name'],
    "email" => $result['email'],
    "phone" => $result['phone'],
    "gender" => $result['gender'],
    "address" => $result['address'],
    "date_of_birth" => $result['date_of_birth']
]);
