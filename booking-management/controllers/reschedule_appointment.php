<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

header("Content-Type: application/json");

$appointment_id = $_POST['appointment_id'] ?? null;
$new_date = $_POST['new_date'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$appointment_id || !$new_date || !$user_id) {
    echo json_encode(["success" => false, "msg" => "Invalid request"]);
    exit;
}

$stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, status = 'pending' WHERE appointment_id = ? AND user_id = ?");
$stmt->bind_param("sii", $new_date, $appointment_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "msg" => "Appointment rescheduled successfully"]);
} else {
    echo json_encode(["success" => false, "msg" => "Failed to reschedule appointment"]);
}
