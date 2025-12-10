<?php
// Always send CORS headers, immediately at the top
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,        // ok for HTTP localhost
    'httponly' => true,
    'samesite' => 'Lax'       // Lax works for localhost fetch
]);
session_start();


// Now continue with your normal PHP code
$method = $_SERVER["REQUEST_METHOD"];
$uri = $_SERVER["REQUEST_URI"];
require_once 'db.php';
$db = new Database();
$conn = $db->conn;

// Create table if not exists
$conn->execute_query("
    CREATE TABLE IF NOT EXISTS reservations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullName VARCHAR(255),
        studentId VARCHAR(50),
        vPlate VARCHAR(50),
        vType VARCHAR(100),
        rDate DATE,
        startTime TIME,
        endTime TIME,
        spot VARCHAR(20)
    )
");

if ($method === "POST" && str_ends_with($uri, "/save-reservation")) {
    $xmlData = file_get_contents("php://input");
    if (!$xmlData) {
        http_response_code(400);
        echo "<error>No XML data received</error>";
        exit();
    }

    $xml = simplexml_load_string($xmlData);

    if(!$xml) {
        http_response_code(401);
        echo "<error>Invalid XML</error>";
    }
 
    $fullName = $conn->real_escape_string($xml->fullName);
    $studentId = $conn->real_escape_string($xml->studentId);
    $vPlate = $conn->real_escape_string($xml->vPlate);
    $vType = $conn->real_escape_string($xml->vType);
    $rDate = $conn->real_escape_string($xml->rDate);
    $startTime = $conn->real_escape_string($xml->startTime);
    $endTime = $conn->real_escape_string($xml->endTime);
    $spot = $conn->real_escape_string($xml->spot);

    // CHECK IF STUDENT EXISTS
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE studentId = ? AND rDate = ? AND spot = ?");
    $stmt->bind_param("sss", $studentId, $rDate, $spot);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        echo "<error>Reservation already exists</error>"; // RETURN ERROR IF TRUE
        exit();
    }


    $query = "INSERT INTO reservations 
              (fullName, studentId, vPlate, vType, rDate, startTime, endTime, spot) 
              VALUES ('$fullName', '$studentId', '$vPlate', '$vType', '$rDate', '$startTime', '$endTime', '$spot')";
    if ($conn->query($query) === TRUE) {
        echo "<success>Reservation saved</success>";
    } else {
        echo "<error>Error: " . $conn->error . "</error>";
    }
} 

if ($method === "GET" && str_ends_with($uri, "/save-reservation")) {
    // SEND XML
    $result = $conn->query("SELECT * FROM reservations");
    $xml = new SimpleXMLElement("<reservations/>");
    while ($row = $result->fetch_assoc()) {
        $res = $xml->addChild("reservation");
        foreach ($row as $key => $value) {
            $res->addChild($key, htmlspecialchars($value));
        }
    }
    echo $xml->asXML();
}

if($method === "POST" && str_ends_with($uri , "/login")) {
    require "login.php";
    exit(); 
}

if($method === "POST" && str_ends_with($uri, "/signup")){
    require "signup.php";
    exit();
}

if($method === "GET" && str_ends_with($uri ,"/check-auth")) {
    require "checkauth.php";
    exit();
}

if($method === "POST" && str_ends_with($uri ,"/logout")) {
    require "logout.php";
    exit();
}

if($method === "GET" && str_ends_with($uri , "/check-user-reservation")){
    require "check_user_reservation.php";
    exit();
}

if($method === "DELETE" && str_ends_with($uri, "/delete-reservation")){
    require "delete-reservation.php";
    exit();
}

if($method === "GET" && str_ends_with($uri, "/get-all-reservations")){
    require "get_all_reservations.php";
    exit();
}


if($method === "POST" && str_ends_with($uri, "/delete-reservation-admin")){
    require "delete_reservation_admin.php";
    exit();
}


$conn->close();
