<?php
// Set headers to allow cross-origin requests (necessary if your HTML is not exactly on the same domain/port)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed."));
    exit();
}

// Get the data sent as JSON from the frontend
$data = json_decode(file_get_contents("php://input"));

// Basic validation: Check if required fields exist
if (empty($data->fullname) || empty($data->userType) || empty($data->contact) || empty($data->purpose)) {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Incomplete data."));
    exit();
}

// ------------------------------------------------------------------
// 1. Database Connection
// Replace these with your XAMPP credentials (default is usually root with no password)
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "visitor_log_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Prepare the SQL Statement
    $sql = "INSERT INTO entries (fullname, userType, srcode, contact, purpose, checkInTime) 
            VALUES (:fullname, :userType, :srcode, :contact, :purpose, :checkInTime)";
    
    $stmt = $conn->prepare($sql);

    // 3. Bind Parameters
    $stmt->bindParam(':fullname', $data->fullname);
    $stmt->bindParam(':userType', $data->userType);
    $stmt->bindParam(':contact', $data->contact);
    $stmt->bindParam(':purpose', $data->purpose);
    $stmt->bindParam(':checkInTime', $data->checkInTime);
    
    // Handle the optional Sr-Code
    $srcode = ($data->userType === 'student' && !empty($data->srcode)) ? $data->srcode : NULL;
    $stmt->bindParam(':srcode', $srcode);

    // 4. Execute the statement
    $stmt->execute();

    http_response_code(201); // Created
    echo json_encode(array("message" => "Check-in successful!"));

} catch(PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array("message" => "Database Error: " . $e->getMessage()));
}
// Close connection (PDO handles this implicitly when the script finishes)
// ------------------------------------------------------------------
?>