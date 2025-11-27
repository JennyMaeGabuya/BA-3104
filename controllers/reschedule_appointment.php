<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

header("Content-Type: application/json");

// --------- helper functions (same logic as in booking_controller) ----------

function get_session_from_time(string $time): array
{
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

function check_session_capacity_resched(mysqli $conn, string $date, string $time, int $appointment_id): array
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
          AND appointment_id <> ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [false, "Database error (prepare): " . $conn->error];
    }

    $stmt->bind_param("sssi", $date, $start, $end, $appointment_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $count = (int)($res['cnt'] ?? 0);

    if ($count >= $limit) {
        $label = strtoupper($session);
        return [false, "The {$label} session for this date is already full."];
    }

    return [true, ""];
}

// ------------------- MAIN -------------------

$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$new_date       = $_POST['new_date'] ?? null;
$new_time       = $_POST['new_time'] ?? null;
$user_id        = $_SESSION['user_id'] ?? null;

if (!$appointment_id || !$new_date || !$new_time || !$user_id) {
    echo json_encode(["success" => false, "msg" => "Invalid request"]);
    exit;
}

// Enforce AM/PM session rules
list($ok, $err) = check_session_capacity_resched($conn, $new_date, $new_time, $appointment_id);
if (!$ok) {
    echo json_encode(["success" => false, "msg" => $err]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE appointments 
    SET appointment_date = ?, 
        appointment_time = ?, 
        status = 'rescheduled_pending'
    WHERE appointment_id = ? AND user_id = ?
");

if (!$stmt) {
    echo json_encode(["success" => false, "msg" => "Database error (prepare): " . $conn->error]);
    exit;
}

$stmt->bind_param("ssii", $new_date, $new_time, $appointment_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "msg" => "Appointment rescheduled successfully"]);
} else {
    echo json_encode(["success" => false, "msg" => "Failed to reschedule appointment"]);
}

$stmt->close();
$conn->close();
