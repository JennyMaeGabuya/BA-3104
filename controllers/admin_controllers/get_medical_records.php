<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
require_once __DIR__ . '/../../config/db_connection.php';

try {
    $stmt = $conn->prepare("
        SELECT
            record_id,
            appointment_id,
            patient_id,
            full_name,
            contact_no,
            email,
            age,
            gender,
            address,
            date_of_birth,
            date,
            time,
            reason,
            doctor_note
        FROM medical_records
        ORDER BY date DESC, time DESC
    ");

    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'data'    => $rows,
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'msg'     => $e->getMessage(),
    ]);
}
exit;
