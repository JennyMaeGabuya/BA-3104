<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "msg" => "Unauthorized"]);
    exit;
}

$appointment_id = $_POST['appointment_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$appointment_id) {
    echo json_encode(["success" => false, "msg" => "Missing appointment ID"]);
    exit;
}

// 1. Get appointment before deleting it
$get = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND user_id = ?");
$get->bind_param("ii", $appointment_id, $user_id);
$get->execute();
$appointment = $get->get_result()->fetch_assoc();

if (!$appointment) {
    echo json_encode(["success" => false, "msg" => "Appointment not found"]);
    exit;
}

// 2. Insert into cancelled_appointments table
$insert = $conn->prepare("
    INSERT INTO cancelled_appointments 
    (appointment_id, user_id, name, email, contact_no, reason,
     appointment_date, appointment_time, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cancelled')
");

$insert->bind_param(
    "iissssss",
    $appointment['appointment_id'],
    $appointment['user_id'],
    $appointment['name'],
    $appointment['email'],
    $appointment['contact_no'],
    $appointment['reason'],
    $appointment['appointment_date'],
    $appointment['appointment_time']
);

$insert->execute();

// 3. Delete from main table
$delete = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
$delete->bind_param("i", $appointment_id);
$delete->execute();

echo json_encode(["success" => true, "msg" => "Appointment cancelled"]);
