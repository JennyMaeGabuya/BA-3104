<?php
require_once __DIR__ . '/../config/db_connection.php';
header("Content-Type: application/json");

try {
    $date = $_POST['appointmentDate'] ?? null;
    $time = $_POST['appointmentTime'] ?? null;

    if (!$date || !$time) {
        echo json_encode([
            "success" => false,
            "msg" => "Missing date or time",
            "count" => 0
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS slot_count
        FROM appointments
        WHERE appointment_date = ?
          AND appointment_time = ?
          AND status IN ('pending', 'accepted')
    ");

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "msg" => "SQL Prepare Error: " . $conn->error,
            "count" => 0
        ]);
        exit;
    }

    $stmt->bind_param("ss", $date, $time);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        "success" => true,
        "count" => (int)($result['slot_count'] ?? 0)
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "msg" => $e->getMessage(),
        "count" => 0
    ]);
}
