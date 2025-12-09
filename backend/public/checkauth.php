<?php
session_start();
require_once 'db.php';

if(isset($_SESSION["user"])) {
    $userId = $_SESSION["user"]["id"];
    
    $stmt = $conn->prepare("SELECT id, fullname, email, studentId FROM users WHERE id=?");
    $stmt->bind_param("i" , $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        echo json_encode(['status' => 'success' , 'user' => $user]);
    } else {
        http_response_code(401);
        echo json_encode(['status'=>'error', 'message'=>'User not found']);
        session_destroy();
    }
} else {
    http_response_code(401);
    echo json_encode(['status'=>'error', 'message'=>'Not logged in']);
}