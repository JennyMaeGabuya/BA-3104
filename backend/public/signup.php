<?php
require_once 'db.php';
require_once __DIR__ . '/../PHPMailer-master/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv\Dotenv::createMutable(__DIR__. '/../');
$dotenv->load(); // get dotenvs

$mailUser = $_ENV["GMAIL_USER"];
$mailPassword = $_ENV["GMAIL_PASSWORD"];
$newEmail = $_POST["email"];
$newFullName = $_POST["fullName"];
$newPassword = password_hash($_POST["password"], PASSWORD_DEFAULT);

$token = bin2hex(random_bytes(16)); // 32 characters


//CHECK IF EMAIL EXISTS
$emailCheck = $conn->prepare("SELECT id FROM users WHERE email = ?");
$emailCheck->bind_param("s", $newEmail);
$emailCheck->execute();
$emailCheck->store_result();

if ($emailCheck->num_rows > 0) {
    $response = ["status" => "error", "message" => "Email already exists"];
    echo json_encode($response);
    exit();
}


if ($conn->query("INSERT INTO users (fullname, email, password, token, verified) VALUES ('$newFullName', '$newEmail', '$newPassword', '$token', 0)")) {
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $mailUser;
    $mail->Password = $mailPassword;
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('no-reply@bsuparkease.com', 'ParkEase');
    $mail->addAddress($newEmail, $newFullName);

    $mail->isHTML(true);
    $mail->Subject = 'Verify Your ParkEase Account';
    $verifyLink = "http://localhost/ParkEase/BA-3104/backend/public/verify.php?token=$token";

    

    $mail->Body    = "Hi $newFullName,<br><br>Thanks for signing up! Please Click the link below verify your account: <a href='$verifyLink'>Click me!</a>";
    $mail->AltBody = "Hi $newFullName,\n\n
                      Thanks for signing up! Copy and paste this link to verify your account:\n
                      $verifyLink";

    $mail->send();
    $response = ["status" => "success", "message" => "Check your email to verify your account"];
    echo json_encode($response);
    exit();
  } catch(Exception $e) {
    echo "success: user saved but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
  }
  
} else {
  $response = ["status" => "error", "message" => $conn->error];
  echo json_encode($response);
}