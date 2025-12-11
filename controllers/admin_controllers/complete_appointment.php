<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../../config/db_connection.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_POST['appointment_id'])) {
    echo json_encode(["success" => false, "msg" => "No appointment ID provided."]);
    exit;
}

$appointment_id = (int)$_POST['appointment_id'];

// Get full appointment record
$stmt = $conn->prepare("
    SELECT 
        appointment_id,
        user_id,
        name,
        contact_no,
        email,
        age,
        gender,
        address,
        date_of_birth,
        appointment_date,
        appointment_time,
        reason,
        doctor_note
    FROM appointments
    WHERE appointment_id = ?
");
if (!$stmt) {
    echo json_encode(["success" => false, "msg" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

if (!$appointment) {
    echo json_encode(["success" => false, "msg" => "Appointment not found."]);
    exit;
}

// Mark appointment as completed
$update = $conn->prepare("
    UPDATE appointments
    SET status = 'completed'
    WHERE appointment_id = ?
");
if (!$update) {
    echo json_encode(["success" => false, "msg" => "Prepare (update) failed: " . $conn->error]);
    exit;
}
$update->bind_param("i", $appointment_id);
if (!$update->execute()) {
    echo json_encode(["success" => false, "msg" => "Failed to update appointment: " . $update->error]);
    exit;
}

// Insert into medical_records with new columns
$insert = $conn->prepare("
    INSERT INTO medical_records (
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
        doctor_note,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
if (!$insert) {
    echo json_encode(["success" => false, "msg" => "Prepare (insert) failed: " . $conn->error]);
    exit;
}

$insert->bind_param(
    "iisssisssssss",
    $appointment['appointment_id'],
    $appointment['user_id'],
    $appointment['name'],
    $appointment['contact_no'],
    $appointment['email'],
    $appointment['age'],
    $appointment['gender'],
    $appointment['address'],
    $appointment['date_of_birth'],
    $appointment['appointment_date'],
    $appointment['appointment_time'],
    $appointment['reason'],
    $appointment['doctor_note']
);

if (!$insert->execute()) {
    echo json_encode(["success" => false, "msg" => "Failed to save medical record: " . $insert->error]);
    exit;
}

// 4) Add notification for patient
$notif = $conn->prepare("
    INSERT INTO notifications (user_id, type, message)
    VALUES (?, 'appointment_completed', ?)
");
if ($notif) {
    // format date and time nicely
    $dateStr = date('M j, Y', strtotime($appointment['appointment_date']));
    $timeStr = date('g:i A', strtotime($appointment['appointment_time'])); // 3:15 PM

    $msg = "Your appointment on {$dateStr} at {$timeStr} has been completed.";
    $notif->bind_param("is", $appointment['user_id'], $msg);
    $notif->execute();
}

echo json_encode(["success" => true, "msg" => "Appointment successfully completed."]);
exit;
