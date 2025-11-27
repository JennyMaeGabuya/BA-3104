<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

header("Content-Type: application/json");

// Get form values
$name       = trim($_POST['name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$password   = trim($_POST['password'] ?? '');
$confirm    = trim($_POST['confirm'] ?? '');

// Validate empty fields
if (!$name || !$email || !$phone || !$password || !$confirm) {
    echo json_encode(["success" => false, "msg" => "All fields are required."]);
    exit();
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "msg" => "Invalid email format."]);
    exit();
}

// Confirm password
if ($password !== $confirm) {
    echo json_encode(["success" => false, "msg" => "Passwords do not match."]);
    exit();
}

// Check if email already exists
$check = $conn->prepare("SELECT * FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "msg" => "Email already registered."]);
    exit();
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "msg" => "Account created successfully!"]);
} else {
    echo json_encode(["success" => false, "msg" => "Something went wrong."]);
}
