<?php
// Always send CORS headers, immediately at the top
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    $fullName = $conn->real_escape_string($xml->fullName);
    $studentId = $conn->real_escape_string($xml->studentId);
    $vPlate = $conn->real_escape_string($xml->vPlate);
    $vType = $conn->real_escape_string($xml->vType);
    $rDate = $conn->real_escape_string($xml->rDate);
    $startTime = $conn->real_escape_string($xml->startTime);
    $endTime = $conn->real_escape_string($xml->endTime);
    $spot = $conn->real_escape_string($xml->spot);

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

$conn->close();
