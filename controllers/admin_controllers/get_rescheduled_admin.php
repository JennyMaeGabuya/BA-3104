<?php
require_once __DIR__ . "/../../config/db_connection.php";
header("Content-Type: application/json");

try {
    $stmt = $conn->query("
        SELECT 
            an.id AS notif_id,
            a.id AS appointment_id,
            a.name AS fullName,
            a.email,
            a.appointment_date AS date,
            a.appointment_time AS time
        FROM admin_notifications an
        JOIN appointments a ON a.id = an.appointment_id
        WHERE an.type = 'rescheduled'
        ORDER BY an.created_at DESC
    ");

    $rows = $stmt->fetch_all(MYSQLI_ASSOC);

    echo json_encode(["success" => true, "data" => $rows]);
} catch (Throwable $e) {
    echo json_encode(["success" => false, "msg" => $e->getMessage()]);
}
