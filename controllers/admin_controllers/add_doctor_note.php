<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/db_connection.php';

$appointment_id = $_POST['appointment_id'] ?? null;
$note           = trim($_POST['note'] ?? '');

if (!$appointment_id || $note === '') {
    echo json_encode([
        "success" => false,
        "msg"     => "Missing note or appointment ID"
    ]);
    exit;
}

$appointment_id = (int)$appointment_id;

$stmt = $conn->prepare("UPDATE appointments SET doctor_note = ? WHERE appointment_id = ?");

$stmt->bind_param("si", $note, $appointment_id);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "msg"     => "Error saving note: " . $stmt->error
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "msg"     => "Doctor note saved successfully"
]);
exit;
