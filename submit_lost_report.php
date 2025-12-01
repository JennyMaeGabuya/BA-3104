<?php
/**
 * Submit Lost Item Report Handler
 * Processes form submission from user_report.php
 * Saves photos to Dashboard/Image/ and stores report in database
 */

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to submit a report']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Validate required fields
    $required = ['itemName', 'category', 'description', 'locationFound', 'dateFound', 'email', 'phone'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Sanitize inputs
    $item_name = trim($_POST['itemName']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $location = trim($_POST['locationFound']);
    $date_found = $_POST['dateFound'];
    $time_found = !empty($_POST['timeFound']) ? $_POST['timeFound'] : null;
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Handle photo upload
    $photo_path = null;
    $requires_approval = false;
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $requires_approval = true; // Photos require admin approval
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        $file = $_FILES['photo'];
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG and PNG allowed.');
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            throw new Exception('File too large. Maximum size is 10MB.');
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'lost_' . time() . '_' . uniqid() . '.' . $extension;
        $upload_dir = __DIR__ . '/Dashboard/Image/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $destination = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $photo_path = 'Image/' . $filename; // Relative path for database
        } else {
            throw new Exception('Failed to save uploaded file');
        }
    }
    
    // Generate report ID
    $stmt = $pdo->query("SELECT COUNT(*) FROM lost_reports");
    $count = $stmt->fetchColumn();
    $report_id = 'LR-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    
    // Determine status
    $status = $requires_approval ? 'Pending' : 'Verified';
    
    // Insert into database
    $sql = "INSERT INTO lost_reports (
        report_id, user_id, item_name, category, description, 
        location, date_lost, time_lost, photo_path, 
        contact_email, contact_phone, status, created_at
    ) VALUES (
        :report_id, :user_id, :item_name, :category, :description,
        :location, :date_lost, :time_lost, :photo_path,
        :email, :phone, :status, NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':report_id' => $report_id,
        ':user_id' => $user_id,
        ':item_name' => $item_name,
        ':category' => $category,
        ':description' => $description,
        ':location' => $location,
        ':date_lost' => $date_found,
        ':time_lost' => $time_found,
        ':photo_path' => $photo_path,
        ':email' => $email,
        ':phone' => $phone,
        ':status' => $status
    ]);
    
    if ($result) {
        // Log success for debugging
        error_log("Report saved: ID=$report_id, User=$user_id, Item=$item_name");
        
        echo json_encode([
            'success' => true,
            'message' => $requires_approval 
                ? 'Report submitted successfully! Your report with photo is pending admin approval.'
                : 'Report submitted successfully!',
            'report_id' => $report_id,
            'requires_approval' => $requires_approval
        ]);
    } else {
        throw new Exception('Failed to save report to database');
    }
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Submit error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
