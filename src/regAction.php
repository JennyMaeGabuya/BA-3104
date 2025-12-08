<?php
// NOTE:'config.php' contains the function connect_db()
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed. Use POST."]);
    exit;
}

// Grab and trim inputs (names must match form)
$firstName = trim($_POST['firstname'] ?? '');
$lastName = trim($_POST['lastname'] ?? '');
$fullName = $firstName . ' ' . $lastName; // Combine first and last name
$userType = trim($_POST['userType'] ?? '');
$srcode   = trim($_POST['srcode'] ?? '');
$contact  = trim($_POST['contact'] ?? '');
$purpose  = trim($_POST['purpose'] ?? '');

// Basic server-side validation
if ($firstName === '' || $lastName === '' || $userType === '' || $contact === '' || $purpose === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Please fill all required fields: first name, last name, user type, contact, and purpose."]);
    exit;
}

// Validate contact is numeric
if (!ctype_digit($contact)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Contact number must contain only numbers."]);
    exit;
}

// Ensure srcode is only used for students; otherwise use null
$finalSrcode = null;
if (strtolower($userType) === 'student') {
    if ($srcode === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "SR-Code is required for students."]);
        exit;
    }
    // Validate SR-Code is numeric
    // Validate SR-Code format (XX-XXXXX)
    if (!preg_match('/^[0-9]{2}-[0-9]{5}$/', $srcode)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "SR-Code must be in format: XX-XXXXX (e.g., 10-10001)."]);
        exit;
    }
    $finalSrcode = $srcode;
}

try {
    $pdo = connect_db();

    $sql = "INSERT INTO visitors (full_name, user_type, srcode, contact, purpose, time_in)
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $fullName,
        $userType,
        $finalSrcode,
        $contact,
        $purpose
    ]);

    echo json_encode([
        'success' => true,
        'id' => (int)$pdo->lastInsertId(),
        'fullname' => $fullName,
        'userType' => $userType,
        'message' => 'Check-in recorded!'
    ]);
    exit;
} catch (Exception $e) {
    // Log the error to server logs for debugging, but return friendly message to client
    error_log("Insert Error (reg_action): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error saving data."]);
    exit;
}
?>