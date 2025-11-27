<?php
require_once __DIR__ . '/../../config/db_connection.php';
header("Content-Type: application/json");

if (!isset($_POST['appointment_id'])) {
    echo json_encode(["success" => false, "msg" => "No appointment ID"]);
    exit;
}

$id = (int)$_POST['appointment_id'];

$stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
