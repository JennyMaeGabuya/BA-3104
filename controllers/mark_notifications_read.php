<?php
// filepath: /opt/lampp/htdocs/booking-management/controllers/mark_notifications_read.php
session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

echo json_encode(['success' => true]);
