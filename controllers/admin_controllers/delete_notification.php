<?php
require_once __DIR__ . "/../../config/db_connection.php";
header("Content-Type: application/json");

$notifId = $_POST['id'] ?? null;
if (!$notifId) {
    echo json_encode(["success" => false, "msg" => "Invalid notification ID"]);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM admin_notifications WHERE id = ?");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $notifId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "msg" => $e->getMessage()]);
}
