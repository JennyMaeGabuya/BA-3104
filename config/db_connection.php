<?php
// Database configuration (XAMPP default)
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "booking_management";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "msg" => "Database connection failed: " . $conn->connect_error
    ]));
}
