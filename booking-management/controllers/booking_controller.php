<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

header("Content-Type: application/json");

// Check login
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "msg" => "Not logged in."]);
    exit;
}

// Collect POST data safely
$name            = $_POST['name'] ?? '';
$contactNo       = $_POST['contactNo'] ?? '';
$email           = $_POST['email'] ?? '';
$age             = $_POST['age'] ?? '';
$gender          = $_POST['gender'] ?? '';
$address         = $_POST['address'] ?? '';
$dateOfBirth     = $_POST['dateOfBirth'] ?? '';
$appointmentDate = $_POST['appointmentDate'] ?? '';
$reason          = $_POST['reason'] ?? '';

// Validate
if (!$name || !$contactNo || !$email || !$age || !$gender || !$address || !$dateOfBirth || !$appointmentDate || !$reason) {
    echo json_encode(["success" => false, "msg" => "All fields are required"]);
    exit;
}

// Prepare SQL
$stmt = $conn->prepare("
    INSERT INTO appointments 
    (user_id, name, contact_no, email, age, gender, address, date_of_birth, appointment_date, reason)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(["success" => false, "msg" => "SQL Prepare Error: " . $conn->error]);
    exit;
}

// Correct bind_param types (10 parameters total)
$stmt->bind_param(
    "isssisssss",
    $user_id,
    $name,
    $contactNo,
    $email,
    $age,
    $gender,
    $address,
    $dateOfBirth,
    $appointmentDate,
    $reason
);

// Execute
if ($stmt->execute()) {
    echo json_encode(["success" => true, "msg" => "Appointment booked successfully!"]);
} else {
    echo json_encode(["success" => false, "msg" => "Database error: " . $stmt->error]);
}
