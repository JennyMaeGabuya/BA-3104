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
        appointment_date,
        appointment_time,
        reason,
        name,
        contact_no,
        email,
        age,
        gender,
        date_of_birth,
        address,
        status
    FROM appointments
    WHERE user_id = ?
    ORDER BY appointment_date DESC
");

if (!$stmt) {
    echo json_encode(["success" => false, "msg" => "Query error: " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];

while ($row = $result->fetch_assoc()) {

    // normalize status: if null → pending
    if (!$row['status']) {
        $row['status'] = "pending";
    }

    // make sure it is always one of allowed statuses
    $allowedStatuses = [
        "pending",
        "accepted",
        "declined",
        "completed",
        "rescheduled_pending"
    ];

    if (!in_array($row['status'], $allowedStatuses, true)) {
        $row['status'] = "pending";
    }

    $appointments[] = $row;
}

echo json_encode($appointments);
