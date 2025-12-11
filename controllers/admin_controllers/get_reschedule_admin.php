<?php
require_once __DIR__ . "/../../config/db_connection.php";
header("Content-Type: application/json");

try {
    $sql = "
        SELECT 
            an.id              AS notif_id,        -- admin_notifications.id
            a.appointment_id   AS ref_id,          -- related appointment
            a.appointment_date AS date,
            a.appointment_time AS time,
            a.name             AS fullName,
            a.email            AS email
        FROM admin_notifications an
        JOIN appointments a ON a.appointment_id = an.appointment_id
        WHERE an.type = 'rescheduled'
        ORDER BY an.created_at DESC
    ";

    $res = $conn->query($sql);
    if (!$res) throw new Exception($conn->error);

    echo json_encode([
        "success" => true,
        "data"    => $res->fetch_all(MYSQLI_ASSOC)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "msg" => $e->getMessage()]);
}
