


<?php

session_start();
require_once __DIR__ . '/../config/db_connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// Check login
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "msg" => "Not logged in."]);
    exit;
}

// Collect data
$name            = $_POST['name'] ?? '';
$contactNo       = $_POST['contactNo'] ?? '';
$email           = $_POST['email'] ?? '';
$age             = $_POST['age'] ?? '';
$gender          = $_POST['gender'] ?? '';
$address         = $_POST['address'] ?? '';
$dateOfBirth     = $_POST['dateOfBirth'] ?? '';
$appointmentDate = $_POST['appointmentDate'] ?? '';
$appointmentTime = $_POST['appointmentTime'] ?? '';
$reason          = $_POST['reason'] ?? '';

// Validate
if (
    !$name || !$contactNo || !$email || !$age || !$gender ||
    !$address || !$dateOfBirth || !$appointmentDate || !$appointmentTime || !$reason
) {

    echo json_encode(["success" => false, "msg" => "All fields are required"]);
    exit;
}

// Insert
$stmt = $conn->prepare("
    INSERT INTO appointments 
    (user_id, name, contact_no, email, age, gender, address, date_of_birth, appointment_date, appointment_time, reason)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(["success" => false, "msg" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param(
    "isssissssss",
    $user_id,
    $name,
    $contactNo,
    $email,
    $age,
    $gender,
    $address,
    $dateOfBirth,
    $appointmentDate,
    $appointmentTime,
    $reason
);


if ($stmt->execute()) {
    echo json_encode(["success" => true, "msg" => "Appointment booked successfully!"]);
} else {
    echo json_encode(["success" => false, "msg" => "Database Error: " . $stmt->error]);
}
