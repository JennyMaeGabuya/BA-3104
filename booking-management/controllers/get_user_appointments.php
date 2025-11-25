<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        appointment_id,
        user_id,
        name,   
        contact_no,
        email,
        gender,
        age,
        date_of_birth,
        address,
        appointment_date,
        reason,
        status,
        created_at
    FROM appointments 
    WHERE user_id = ? 
    ORDER BY appointment_date DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

echo json_encode($appointments);
