<?php
require_once 'db.php';

$email = $_POST['email'];
$password = $_POST['password'];


// Get user from DB
$stmt = $conn->prepare("SELECT id, fullname, email, password, verified, studentId FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if(password_verify($password, $user['password'])) {
        if($user['verified'] == 1) {
            $_SESSION["user"] = [
              "id" => $user["id"],
              "name" => $user["fullname"],
              "email" => $user["email"],
              "studentId" => $user["studentId"]
            ];
            echo json_encode(['status'=>'success']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Verify your email first']);
        }
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Wrong password']);
    }
} else {
    echo json_encode(['status'=>'error', 'message'=>'User not found']);
}
