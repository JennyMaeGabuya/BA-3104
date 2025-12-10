<?php
require_once "db.php";


if (!isset($_SESSION["user"])) {
    echo json_encode(["status" => "error", "message" => "Not authenticated"]);
    return;
}

$studentId = $_SESSION["user"]["studentId"];

$stmt = $conn->prepare("DELETE FROM reservations WHERE studentId = ?");
$success = $stmt->execute([$studentId]);

if ($success) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error"]);
}