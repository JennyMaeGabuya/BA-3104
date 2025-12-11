<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';
header("Content-Type: application/json");

/* -----------------------------------------------------
   VALIDATION
----------------------------------------------------- */
$user_id = $_SESSION['user_id'] ?? null;
$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$new_date = $_POST['new_date'] ?? null;
$new_time = $_POST['new_time'] ?? null;

if (!$user_id || !$appointment_id || !$new_date || !$new_time) {
    echo json_encode(["success" => false, "msg" => "Invalid request"]);
    exit;
}

/* -----------------------------------------------------
   SLOT VALIDATION (AM / PM + 15-min slots)
----------------------------------------------------- */
function get_session_from_time(string $time): array
{
    if ($time >= '08:00:00' && $time <= '11:45:00')
        return ['am', 12, '08:00:00', '11:45:00'];
    if ($time >= '13:00:00' && $time <= '16:45:00')
        return ['pm', 16, '13:00:00', '16:45:00'];
    return [null, 0, null, null];
}

function is_valid_15min_slot(string $time, string $session): bool
{
    [$h, $m, $s] = array_pad(explode(':', $time), 3, 0);
    return ($s == 0 && $m % 15 == 0);
}

function check_session_capacity(mysqli $conn, string $date, string $time)
{
    list($session, $limit, $start, $end) = get_session_from_time($time);
    if (!$session) return [false, "Selected time is outside clinic hours"];
    if (!is_valid_15min_slot($time, $session)) return [false, "Invalid 15-minute slot"];

    $sql = "
        SELECT COUNT(*) AS cnt
        FROM appointments
        WHERE appointment_date = ?
          AND appointment_time BETWEEN ? AND ?
          AND status IN ('pending','accepted','rescheduled_pending')
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $date, $start, $end);
    $stmt->execute();

    $cnt = ($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    if ($cnt >= $limit) return [false, strtoupper($session) . " session is full"];

    return [true, ""];
}

list($ok, $msg) = check_session_capacity($conn, $new_date, $new_time);
if (!$ok) {
    echo json_encode(["success" => false, "msg" => $msg]);
    exit;
}

/* -----------------------------------------------------
   DETECT SOURCE:
   1) Pending → UPDATE
   2) Cancelled → RESTORE + DELETE
----------------------------------------------------- */

// Check if appointment exists in ACTIVE table
$check_active = $conn->prepare("
    SELECT * FROM appointments
    WHERE appointment_id = ? AND user_id = ?
");
$check_active->bind_param("ii", $appointment_id, $user_id);
$check_active->execute();
$active = $check_active->get_result()->fetch_assoc();

// Check if appointment exists in CANCELLED table
$check_cancelled = $conn->prepare("
    SELECT * FROM cancelled_appointments
    WHERE appointment_id = ? AND user_id = ?
");
$check_cancelled->bind_param("ii", $appointment_id, $user_id);
$check_cancelled->execute();
$cancelled = $check_cancelled->get_result()->fetch_assoc();

/* -----------------------------------------------------
   CASE 1: ACTIVE APPOINTMENT (pending/resched) → UPDATE
----------------------------------------------------- */
if ($active) {
    $stmt = $conn->prepare("
        UPDATE appointments
        SET appointment_date = ?, 
            appointment_time = ?, 
            status = 'rescheduled_pending'
        WHERE appointment_id = ? 
          AND user_id = ?
    ");

    $stmt->bind_param("ssii", $new_date, $new_time, $appointment_id, $user_id);

    if ($stmt->execute()) {

        // >>> NEW: admin notification for reschedule (active) <<<
        $adminNotif = $conn->prepare("
            INSERT INTO admin_notifications (appointment_id, type, message)
            VALUES (?, 'rescheduled', ?)
        ");
        if ($adminNotif) {
            $msg = "Appointment for {$active['name']} was rescheduled to {$new_date} at {$new_time}.";
            $adminNotif->bind_param("is", $appointment_id, $msg);
            $adminNotif->execute();
            $adminNotif->close();
        }

        echo json_encode(["success" => true, "msg" => "Appointment rescheduled successfully"]);
        exit;
    } else {
        echo json_encode(["success" => false, "msg" => "Failed to update appointment"]);
        exit;
    }
}

/* -----------------------------------------------------
   CASE 2: CANCELLED APPOINTMENT → RESTORE INTO ACTIVE TABLE
----------------------------------------------------- */
if ($cancelled) {

    $insert = $conn->prepare("
        INSERT INTO appointments
        (user_id, name, email, contact_no, age, gender, address, date_of_birth,
         appointment_date, appointment_time, reason, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'rescheduled_pending')
    ");

    $insert->bind_param(
        "isssissssss",
        $cancelled["user_id"],
        $cancelled["name"],
        $cancelled["email"],
        $cancelled["contact_no"],
        $cancelled["age"],
        $cancelled["gender"],
        $cancelled["address"],
        $cancelled["date_of_birth"],
        $new_date,
        $new_time,
        $cancelled["reason"]
    );

    if (!$insert->execute()) {
        echo json_encode(["success" => false, "msg" => "Failed to restore cancelled appointment"]);
        exit;
    }

    $newAppointmentId = $insert->insert_id;

    // >>> NEW: admin notification for reschedule (restored) <<<
    $adminNotif = $conn->prepare("
        INSERT INTO admin_notifications (appointment_id, type, message)
        VALUES (?, 'rescheduled', ?)
    ");
    if ($adminNotif) {
        $msg = "Appointment for {$cancelled['name']} was rescheduled to {$new_date} at {$new_time}.";
        $adminNotif->bind_param("is", $newAppointmentId, $msg);
        $adminNotif->execute();
        $adminNotif->close();
    }

    // Remove from cancelled table
    $del = $conn->prepare("DELETE FROM cancelled_appointments WHERE appointment_id = ?");
    $del->bind_param("i", $appointment_id);
    $del->execute();

    echo json_encode(["success" => true, "msg" => "Appointment restored & rescheduled"]);
    exit;
}

/* -----------------------------------------------------
   NOT FOUND ANYWHERE
----------------------------------------------------- */
echo json_encode(["success" => false, "msg" => "Appointment not found"]);
exit;
