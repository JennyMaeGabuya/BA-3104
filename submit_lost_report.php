<?php
/**
 * Submit Lost Item Report Handler
 * Processes form submission from user_report.php
 * Saves photos to Dashboard/Image/ and stores report in database
 */

// Enable error logging (log only) and prevent raw notices from corrupting JSON
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 0);

session_start();
require_once 'db_config.php';
header('Content-Type: application/json');

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
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fileErr = $_FILES['photo']['error'];
        if ($fileErr !== UPLOAD_ERR_OK) {
            $errMap = [
                UPLOAD_ERR_INI_SIZE => 'Uploaded file exceeds server limit.',
                UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds form limit.',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
            ];
            $detail = $errMap[$fileErr] ?? ('Upload error code: ' . $fileErr);
            throw new Exception('Upload failed: ' . $detail);
        }

        $requires_approval = true; // Photos require admin approval
        $file = $_FILES['photo'];

        // Validate size
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            throw new Exception('File too large. Maximum size is 10MB.');
        }

        // Robust MIME validation using finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed_mime = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!array_key_exists($mime, $allowed_mime)) {
            throw new Exception('Invalid file type. Only JPG and PNG allowed.');
        }

        // Normalize extension based on MIME
        $extension = $allowed_mime[$mime];
        $filename = 'lost_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $upload_dir = __DIR__ . '/Dashboard/Image/';
        $fallback_dir = __DIR__ . '/ImageUploads/'; // fallback if primary not writable

        // Ensure directory exists and writable
        // Ensure directory exists and writable (trying primary then fallback)
        $target_dir = $upload_dir;
        if (!is_dir($target_dir)) {
            @mkdir($target_dir, 0775, true);
        }
        if (!is_writable($target_dir)) {
            @chmod($target_dir, 0775);
        }
        // Final escalation attempt (development only) to world-writable if still failing
        if (!is_writable($target_dir)) {
            @chmod($target_dir, 0777);
        }
        if (!is_writable($target_dir)) {
            // Try fallback
            if (!is_dir($fallback_dir)) {
                @mkdir($fallback_dir, 0775, true);
            }
            if (!is_writable($fallback_dir)) {
                @chmod($fallback_dir, 0775);
            }
            if (!is_writable($fallback_dir)) {
                @chmod($fallback_dir, 0777); // escalate fallback
            }
            if (is_writable($fallback_dir)) {
                $target_dir = $fallback_dir;
            } else {
                error_log('Upload dir permission failure: primary=' . $upload_dir . ' fallback=' . $fallback_dir);
                throw new Exception('Upload directory not writable.');
            }
        }

        $destination = $target_dir . $filename;

        // Double-check tmp file
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Security check failed on uploaded file.');
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            // Attempt fallback rename (rare cases)
            if (!@rename($file['tmp_name'], $destination)) {
                error_log('Upload move failed: tmp=' . $file['tmp_name'] . ' dest=' . $destination);
                throw new Exception('Failed to save uploaded file');
            }
        }

        // Set relative path depending on which directory used
        if ($target_dir === $upload_dir) {
            $photo_path = 'Dashboard/Image/' . $filename; // correct relative to app root
        } else {
            $photo_path = 'ImageUploads/' . $filename; // fallback directory
        }
    }
    
    // Generate a sequential report ID (LR-001, LR-002, ...)
    // Use MAX across existing 3-digit IDs to avoid duplicates if rows were deleted or concurrent submissions happen.
    $maxStmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(report_id,4) AS UNSIGNED)) AS max_id FROM lost_reports WHERE report_id REGEXP '^LR-[0-9]{3}$'");
    $maxRow = $maxStmt ? $maxStmt->fetch(PDO::FETCH_ASSOC) : null;
    $nextNum = isset($maxRow['max_id']) && $maxRow['max_id'] !== null ? ((int)$maxRow['max_id'] + 1) : 1;
    $candidate = 'LR-' . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
    // Safety loop in rare race (very unlikely in XAMPP dev) - recheck existence
    $existsStmt = $pdo->prepare('SELECT 1 FROM lost_reports WHERE report_id = ? LIMIT 1');
    while (true) {
        $existsStmt->execute([$candidate]);
        if (!$existsStmt->fetch()) { break; }
        $nextNum++;
        $candidate = 'LR-' . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
    }
    $report_id = $candidate;
    
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
