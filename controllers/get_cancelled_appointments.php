<?php
session_start();
require_once "../config/db_connection.php";

header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT * FROM cancelled_appointments
    WHERE user_id = ?
    ORDER BY cancelled_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cancelled = [];
while ($row = $result->fetch_assoc()) {
    $cancelled[] = $row;
}

echo json_encode($cancelled);
