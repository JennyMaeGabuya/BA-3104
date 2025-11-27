<?php
require_once __DIR__ . '/../config/db_connection.php';
header("Content-Type: application/json");

try {
    $date    = $_POST['appointmentDate'] ?? null;
    $time    = $_POST['appointmentTime'] ?? null;
    $session = $_POST['session'] ?? null;

    // New for reschedule:
    $dayOnly   = isset($_POST['day_only']) ? (int)$_POST['day_only'] : 0;
    $excludeId = isset($_POST['exclude_appointment_id']) ? (int)$_POST['exclude_appointment_id'] : 0;

    if (!$date) {
        echo json_encode([
            "success" => false,
            "msg" => "Missing date",
            "count" => 0
        ]);
        exit;
    }

    // ----------------------------------------------------
    // MODE 1: WHOLE DAY (used by reschedule modal)
    // ----------------------------------------------------
    if ($dayOnly === 1) {
        if ($excludeId > 0) {
            $sql = "
                SELECT appointment_time
                FROM appointments
                WHERE appointment_date = ?
                  AND status IN ('pending','accepted','rescheduled_pending')
                  AND appointment_id <> ?
            ";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode([
                    "success" => false,
                    "msg" => "SQL Prepare Error: " . $conn->error,
                    "count" => 0
                ]);
                exit;
            }
            $stmt->bind_param("si", $date, $excludeId);
        } else {
            $sql = "
                SELECT appointment_time
                FROM appointments
                WHERE appointment_date = ?
                  AND status IN ('pending','accepted','rescheduled_pending')
            ";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode([
                    "success" => false,
                    "msg" => "SQL Prepare Error: " . $conn->error,
                    "count" => 0
                ]);
                exit;
            }
            $stmt->bind_param("s", $date);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        $times = [];
        while ($row = $res->fetch_assoc()) {
            $times[] = $row['appointment_time'];
        }

        echo json_encode([
            "success" => true,
            "mode"    => "day",
            "count"   => count($times),
            "times"   => $times
        ]);
        exit;
    }

    // ----------------------------------------------------
    // MODE 2: SESSION (AM / PM) – used by booking
    // ----------------------------------------------------
    if ($session) {
        $session = strtolower($session);
        if ($session === 'am') {
            $start = '08:00:00';
            $end   = '11:59:59';
            $limit = 12;
        } elseif ($session === 'pm') {
            $start = '13:00:00';
            $end   = '16:59:59';
            $limit = 16;
        } else {
            echo json_encode([
                "success" => false,
                "msg" => "Invalid session",
                "count" => 0
            ]);
            exit;
        }

        $sql = "
            SELECT appointment_time 
            FROM appointments
            WHERE appointment_date = ?
              AND appointment_time BETWEEN ? AND ?
              AND status IN ('pending', 'accepted', 'rescheduled_pending')
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode([
                "success" => false,
                "msg" => "SQL Prepare Error: " . $conn->error,
                "count" => 0
            ]);
            exit;
        }

        $stmt->bind_param("sss", $date, $start, $end);
        $stmt->execute();
        $res = $stmt->get_result();

        $times = [];
        while ($row = $res->fetch_assoc()) {
            $times[] = $row['appointment_time'];
        }

        echo json_encode([
            "success" => true,
            "mode"    => "session",
            "session" => $session,
            "count"   => count($times),
            "limit"   => $limit,
            "times"   => $times
        ]);
        exit;
    }

    // ----------------------------------------------------
    // MODE 3: SINGLE TIME (legacy)
    // ----------------------------------------------------
    if (!$time) {
        echo json_encode([
            "success" => false,
            "msg" => "Missing time",
            "count" => 0
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS slot_count
        FROM appointments
        WHERE appointment_date = ?
          AND appointment_time = ?
          AND status IN ('pending', 'accepted', 'rescheduled_pending')
    ");

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "msg" => "SQL Prepare Error: " . $conn->error,
            "count" => 0
        ]);
        exit;
    }

    $stmt->bind_param("ss", $date, $time);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        "success" => true,
        "mode"    => "time",
        "count"   => (int)($result['slot_count'] ?? 0)
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "msg" => $e->getMessage(),
        "count" => 0
    ]);
}
