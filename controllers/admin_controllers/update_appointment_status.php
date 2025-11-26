<?php
require_once "../../config/db_connection.php";
header("Content-Type: application/json");

try {
    // Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["success" => false, "msg" => "Invalid request method"]);
        exit;
    }

    if (!isset($_POST['appointment_id']) || !isset($_POST['status'])) {
        echo json_encode(["success" => false, "msg" => "Missing required fields"]);
        exit;
    }

    $appointment_id = (int)$_POST['appointment_id'];
    $status = $_POST['status'];

    // allowed statuses
    $allowed = ['pending', 'accepted', 'declined', 'completed'];
    if (!in_array($status, $allowed, true)) {
        echo json_encode(["success" => false, "msg" => "Invalid status"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
    $stmt->bind_param("si", $status, $appointment_id);

    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "msg" => "Database update failed"]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "msg"     => "Status updated",
        "status"  => $status,
        "id"      => $appointment_id
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "msg" => $e->getMessage()]);
}
