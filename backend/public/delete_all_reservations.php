<?php

require_once "db.php";


$stmt = $conn->prepare("DELETE * FROM reservations");
$stmt->execute();
echo json_encode(["message" => "All reservations deleted"]);
