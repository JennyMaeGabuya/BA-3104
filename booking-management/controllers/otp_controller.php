<?php
require_once __DIR__ . '/../config/db_connection.php';
require '../vendor/autoload.php'; // PHPMailer

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if (!$email) {
    echo json_encode(["success" => false, "msg" => "Email is required."]);
    exit;
}

// check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "msg" => "No account found with this email."]);
    exit;
}

// generate a 6-digit OTP
$otp = rand(100000, 999999);

// save OTP in database (temporary)
$update = $conn->prepare("UPDATE users SET otp=?, otp_expire=DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE email=?");
$update->bind_param("is", $otp, $email);
$update->execute();

// send via PHPMailer
$mail = new PHPMailer\PHPMailer\PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'kenpitarde09685912133@gmail.com';
$mail->Password = 'nlcw fuyo vnlh xmmh';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('kenpitarde09685912133@gmail.com', 'BSU Clinic System');
$mail->addAddress($email);
$mail->Subject = "Your OTP Code";
$mail->Body = "Your OTP to reset your password is: $otp\nValid for 5 minutes.";

if ($mail->send()) {
    echo json_encode(["success" => true, "msg" => "OTP sent to your email."]);
} else {
    echo json_encode(["success" => false, "msg" => "Mailer error: " . $mail->ErrorInfo]);
}
