<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

header("Content-Type: application/json");

$appointment_id = $_POST['appointment_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id || !$appointment_id) {
    echo json_encode(["success" => false, "msg" => "Unauthorized action"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ? AND user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "msg" => "Appointment cancelled successfully"]);
} else {
    echo json_encode(["success" => false, "msg" => "Failed to cancel appointment"]);
}
