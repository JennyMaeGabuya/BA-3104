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

// DELETE FROM cancelled_appointments TABLE
$stmt = $conn->prepare("DELETE FROM cancelled_appointments WHERE appointment_id = ? AND user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "msg" => "Cancelled appointment deleted"]);
} else {
    echo json_encode(["success" => false, "msg" => "Appointment not found or already deleted"]);
}
