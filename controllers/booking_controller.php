<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// ---------------- HELPER FUNCTIONS ----------------

function get_session_from_time(string $time): array
{
    // $time format: "HH:MM:SS"
    if ($time >= '08:00:00' && $time <= '11:59:59') {
        return ['am', 12, '08:00:00', '11:59:59'];
    }
    if ($time >= '13:00:00' && $time <= '16:59:59') {
        return ['pm', 16, '13:00:00', '16:59:59'];
    }
    return [null, 0, null, null];
}

function is_valid_15min_slot(string $time, string $session): bool
{
    // Expect "HH:MM:SS"
    $parts = explode(':', $time);
    if (count($parts) < 2) return false;

    $h = (int)$parts[0];
    $m = (int)$parts[1];
    $s = isset($parts[2]) ? (int)$parts[2] : 0;

    if ($s !== 0) return false;
    if ($m % 15 !== 0) return false;

    if ($session === 'am') {
        if ($h < 8 || $h > 11) return false;
        if ($h === 11 && $m > 45) return false;
    } elseif ($session === 'pm') {
        if ($h < 13 || $h > 16) return false;
        if ($h === 16 && $m > 45) return false;
    } else {
        return false;
    }

    return true;
}

function check_session_capacity(mysqli $conn, string $date, string $time): array
{
    list($session, $limit, $start, $end) = get_session_from_time($time);

    if (!$session) {
        return [false, "Selected time is outside clinic hours."];
    }

    if (!is_valid_15min_slot($time, $session)) {
        return [false, "Selected time is not a valid 15-minute slot."];
    }

    $sql = "
        SELECT COUNT(*) AS cnt
        FROM appointments
        WHERE appointment_date = ?
          AND appointment_time BETWEEN ? AND ?
          AND status IN ('pending','accepted','rescheduled_pending')
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [false, "Database error (prepare): " . $conn->error];
    }

    $stmt->bind_param("sss", $date, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $count = (int)($res['cnt'] ?? 0);

    if ($count >= $limit) {
        $label = strtoupper($session);
        return [false, "The {$label} session for this date is already full."];
    }

    return [true, ""];
}

// ---------------- MAIN LOGIC ----------------

// Check login
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "msg" => "Not logged in."]);
    exit;
}

// Collect data
$name            = $_POST['name'] ?? '';
$contactNo       = $_POST['contactNo'] ?? '';
$email           = $_POST['email'] ?? '';
$age             = $_POST['age'] ?? '';
$gender          = $_POST['gender'] ?? '';
$address         = $_POST['address'] ?? '';
$dateOfBirth     = $_POST['dateOfBirth'] ?? '';
$appointmentDate = $_POST['appointmentDate'] ?? '';
$appointmentTime = $_POST['appointmentTime'] ?? '';
$reason          = $_POST['reason'] ?? '';

// Validate
if (
    !$name || !$contactNo || !$email || !$age || !$gender ||
    !$address || !$dateOfBirth || !$appointmentDate || !$appointmentTime || !$reason
) {
    echo json_encode(["success" => false, "msg" => "All fields are required."]);
    exit;
}

// Enforce AM/PM capacity + 15 min slot
list($ok, $err) = check_session_capacity($conn, $appointmentDate, $appointmentTime);
if (!$ok) {
    echo json_encode(["success" => false, "msg" => $err]);
    exit;
}

// Insert
$stmt = $conn->prepare("
    INSERT INTO appointments 
    (user_id, name, contact_no, email, age, gender, address, date_of_birth, appointment_date, appointment_time, reason, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");

if (!$stmt) {
    echo json_encode(["success" => false, "msg" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param(
    "isssissssss",
    $user_id,
    $name,
    $contactNo,
    $email,
    $age,
    $gender,
    $address,
    $dateOfBirth,
    $appointmentDate,
    $appointmentTime,
    $reason
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "msg" => "Appointment booked successfully!"]);
} else {
    echo json_encode(["success" => false, "msg" => "Database Error: " . $stmt->error]);
}
