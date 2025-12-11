<?php
require_once __DIR__ . '/../../config/db_connection.php';
header('Content-Type: application/json');

$appointmentId = $_POST['appointment_id'] ?? null;
if (!$appointmentId) {
    echo json_encode(['success' => false, 'msg' => 'Missing appointment_id']);
    exit;
}

$conn->begin_transaction();
try {
    // Get appointment info + user_id
    $stmt = $conn->prepare("
        SELECT a.*, u.user_id
        FROM appointments a
        JOIN users u ON u.email = a.email
        WHERE a.appointment_id = ?
    ");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $appt = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$appt) throw new Exception("Appointment not found");

    // Insert into cancelled_appointments
    $ins = $conn->prepare("
        INSERT INTO cancelled_appointments
        (appointment_id, user_id, name, email, contact_no, age, gender, address, date_of_birth,
         reason, appointment_date, appointment_time, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'cancelled')
    ");
    if (!$ins) throw new Exception($conn->error);
    $ins->bind_param(
        "iississsssss",
        $appt['appointment_id'],
        $appt['user_id'],
        $appt['name'],
        $appt['email'],
        $appt['contact_no'],
        $appt['age'],
        $appt['gender'],
        $appt['address'],
        $appt['date_of_birth'],
        $appt['reason'],
        $appt['appointment_date'],
        $appt['appointment_time']
    );
    $ins->execute();
    $ins->close();

    // Delete from appointments
    $del = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    if (!$del) throw new Exception($conn->error);
    $del->bind_param("i", $appointmentId);
    $del->execute();
    $del->close();

    // Patient notification
    $noteMsg = "Your appointment on {$appt['appointment_date']} at {$appt['appointment_time']} "
        . "was cancelled because staff is not available.";
    $note = $conn->prepare("
        INSERT INTO notifications (user_id, type, message, is_read)
        VALUES (?, 'appointment_cancelled', ?, 0)
    ");
    if (!$note) throw new Exception($conn->error);
    $note->bind_param("is", $appt['user_id'], $noteMsg);
    $note->execute();
    $note->close();

    // (Optional) admin_notifications entry
    $adminNotif = $conn->prepare("
        INSERT INTO admin_notifications (appointment_id, type, message)
        VALUES (?, 'cancelled', ?)
    ");
    if ($adminNotif) {
        $adminMsg = "{$appt['name']}'s appointment was cancelled by admin.";
        $adminNotif->bind_param("is", $appt['appointment_id'], $adminMsg);
        $adminNotif->execute();
        $adminNotif->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'msg' => 'Appointment cancelled. Patient notified.']);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
