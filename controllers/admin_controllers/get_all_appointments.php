<?php
require_once "../../config/db_connection.php";
header("Content-Type: application/json");

try {
    $stmt = $conn->prepare("
        SELECT 
            appointment_id AS id,
            appointment_date AS date,
            appointment_time AS time,
            reason,
            name AS fullName,
            contact_no AS contactNo,
            email,
            age,
            gender,
            status
        FROM appointments
        ORDER BY appointment_date ASC, appointment_time ASC
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
        "error" => $e->getMessage()
    ]);
}
