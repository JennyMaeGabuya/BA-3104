<?php
require_once 'db.php';
$db = new Database();
$conn = $db->conn;

if(isset($_GET["token"])) {
  $token = $_GET["token"];
  $stmt = $conn->prepare("SELECT * FROM users WHERE token = ? AND verified = 0");
  $stmt->bind_param("s" ,$token);
  $stmt->execute();
  $result = $stmt->get_result();
  $loginLink = "http://localhost:5173/login.html";
  if($result->num_rows === 1) {
    $update = $conn->prepare("UPDATE users SET verified = 1, token = NULL WHERE token = ?");
    $update->bind_param("s", $token);
    $update->execute();
    
    echo "Your email has been verified! You can now <a href='$loginLink'l>log in</a>.";
  } else {
    echo "Invalid or expired token";
  }
} else {
  echo "No token exists.";
}