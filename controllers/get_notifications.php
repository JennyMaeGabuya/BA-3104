<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'count' => 0, 'notifications' => []]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, type, message, is_read, created_at
    FROM notifications
    WHERE user_id = ? AND is_read = 0
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res  = $stmt->get_result();
$data = $res->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success'       => true,
    'count'         => count($data),
    'notifications' => $data
]);
