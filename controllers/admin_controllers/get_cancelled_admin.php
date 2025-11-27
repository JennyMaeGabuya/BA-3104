<?php
require_once "../../config/db_connection.php";
header("Content-Type: application/json");

try {
    $stmt = $conn->prepare("
        SELECT 
            id,
            appointment_id,
            user_id,
            name AS fullName,
            email,
            contact_no AS contactNo,
            reason,
            appointment_date AS date,
            appointment_time AS time,
            status,
            cancelled_at
        FROM cancelled_appointments
        ORDER BY cancelled_at DESC
    ");

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "msg" => $e->getMessage()
    ]);
}
