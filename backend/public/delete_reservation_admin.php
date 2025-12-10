<?php

require_once "db.php";

$dataFromFrontend = json_decode(file_get_contents("php://input") , true);
$studentId = $dataFromFrontend["studentId"];

$stmt = $conn->prepare("DELETE FROM reservations WHERE studentId = ?");
$stmt->bind_param("s" , $studentId);
$stmt->execute();

echo json_encode([
    "status" => $stmt->affected_rows > 0 ? "success" : "error",
    "deletedRows" => $stmt->affected_rows
]);