<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'msg' => 'Not authenticated']);
    exit;
}

try {
    $sql = "
        SELECT appointment_date AS date, appointment_time AS time, status
        FROM appointments
        WHERE user_id = ?
        UNION ALL
        SELECT appointment_date AS date, appointment_time AS time, status
        FROM cancelled_appointments
        WHERE user_id = ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
