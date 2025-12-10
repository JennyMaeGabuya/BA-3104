<?php

require_once "db.php";

$stmt = $conn->prepare("SELECT * FROM reservations");
$stmt->execute();
$result = $stmt->get_result();
$reservations = [];
while($row = $result->fetch_assoc()){
  $reservations[] = $row;
}

echo json_encode([
    "status" => "success",
    "reservations" => $reservations
]);
// if($result->num_rows > 1) {
//   echo json_encode(["status" => "success" , "reservations" => $result->fetch_assoc()]);
//   exit();
// } else {
//   echo json_encode(["status" => "error" , "message" => "no reservations in database"]);
//   exit();
// }