<?php

require_once 'db.php';

if(!isset($_SESSION["user"])) {
  http_response_code(401);
  echo json_encode(['status' => "error" , "message" => "not logged in"]);
  exit();
}

$userId = $_SESSION["user"]["studentId"];

$stmt = $conn->prepare(query: "SELECT * FROM reservations WHERE studentId = ?");
$stmt->bind_param("s", $userId);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 1) {
  echo json_encode([
    "status" => "reserved",
    "reservation" => $result->fetch_assoc(),
    "hasReservation" => true
  ]);
} else {
  echo json_encode([ "status" => "none", "hasReservation" => false ]);
}