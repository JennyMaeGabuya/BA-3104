<?php
require_once __DIR__ . "/../../config/db_connection.php";
header("Content-Type: application/json");

$type = $_POST['type'] ?? null; // 'cancelled' | 'rescheduled'
if (!$type || !in_array($type, ['cancelled', 'rescheduled'], true)) {
    echo json_encode(["success" => false, "msg" => "Invalid type"]);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM admin_notifications WHERE type = ?");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "msg" => $e->getMessage()]);
}
