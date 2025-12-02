<?php
// Robust JSON handler for Found Item submissions

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/php_error.log');

header('Content-Type: application/json');

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_config.php';

function respond(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'error' => 'Invalid request method'], 405);
    }

    $required = [
        'itemName', 'category', 'description', 'locationFound', 'dateFound', 'pickupLocation', 'email', 'phone'
    ];

    foreach ($required as $key) {
        if (!isset($_POST[$key]) || trim((string)$_POST[$key]) === '') {
            respond(['success' => false, 'error' => "Missing required field: $key"], 400);
        }
    }

    $itemName = trim((string)$_POST['itemName']);
    $category = trim((string)$_POST['category']);
    $description = trim((string)$_POST['description']);
    $locationFound = trim((string)$_POST['locationFound']);
    $dateFound = trim((string)$_POST['dateFound']);
    $timeFound = isset($_POST['timeFound']) ? trim((string)$_POST['timeFound']) : null;
    $pickupLocation = trim((string)$_POST['pickupLocation']);
    $email = trim((string)$_POST['email']);
    $phone = trim((string)$_POST['phone']);

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        respond(['success' => false, 'error' => 'Not authenticated'], 401);
    }

    // Upload handling (mirror lost upload logic)
    $photoPath = null;
    if (isset($_FILES['photo']) && isset($_FILES['photo']['tmp_name']) && $_FILES['photo']['tmp_name'] !== '') {
        $uploadError = (int)($_FILES['photo']['error'] ?? UPLOAD_ERR_OK);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errorMap = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
            ];
            $msg = $errorMap[$uploadError] ?? 'Unknown upload error.';
            respond(['success' => false, 'error' => "Upload error: $msg"], 400);
        }

        $tmpPath = $_FILES['photo']['tmp_name'];
        $originalName = basename((string)$_FILES['photo']['name']);

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath) ?: '';
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        if (!isset($allowed[$mime])) {
            respond(['success' => false, 'error' => 'Invalid image type. Only JPG/PNG/GIF allowed.'], 400);
        }
        $ext = $allowed[$mime];

        $primaryDir = __DIR__ . '/Dashboard/Image';
        $fallbackDir = __DIR__ . '/ImageUploads';

        foreach ([$primaryDir, $fallbackDir] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (!is_writable($dir)) {
                @chmod($dir, 0777);
            }
        }

        $targetDir = is_writable($primaryDir) ? $primaryDir : $fallbackDir;
        $unique = 'found_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $unique;

        $moved = false;
        if (is_uploaded_file($tmpPath)) {
            $moved = @move_uploaded_file($tmpPath, $targetPath);
        }
        if (!$moved) {
            $moved = @rename($tmpPath, $targetPath);
        }
        if (!$moved || !file_exists($targetPath)) {
            respond(['success' => false, 'error' => 'Failed to save uploaded file'], 500);
        }

        $photoPath = str_replace(__DIR__, '', $targetPath);
        $photoPath = ltrim($photoPath, '/');
    }

    // Generate a sequential report_id: FR-001, FR-002, ...
    // Look for existing numeric-style IDs and increment the max
    $maxStmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(report_id, 4) AS UNSIGNED)) AS max_id FROM found_reports WHERE report_id REGEXP '^FR-[0-9]{3}$'");
    $maxRow = $maxStmt ? $maxStmt->fetch(PDO::FETCH_ASSOC) : null;
    $nextNum = isset($maxRow['max_id']) && $maxRow['max_id'] !== null ? ((int)$maxRow['max_id'] + 1) : 1;
    $reportId = 'FR-' . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);

    $sql = "INSERT INTO found_reports (report_id, user_id, item_name, category, description, location_found, date_found, time_found, photo_path, pickup_location, contact_email, contact_phone, status) 
            VALUES (:report_id, :user_id, :item_name, :category, :description, :location_found, :date_found, :time_found, :photo_path, :pickup_location, :contact_email, :contact_phone, 'Pending')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':report_id' => $reportId,
        ':user_id' => $userId,
        ':item_name' => $itemName,
        ':category' => $category,
        ':description' => $description,
        ':location_found' => $locationFound,
        ':date_found' => $dateFound,
        ':time_found' => $timeFound ?: null,
        ':photo_path' => $photoPath,
        ':pickup_location' => $pickupLocation,
        ':contact_email' => $email,
        ':contact_phone' => $phone,
    ]);

    respond(['success' => true, 'message' => 'Found report submitted successfully', 'type' => 'Found', 'reportId' => $reportId]);
} catch (Throwable $e) {
    error_log('submit_found_report error: ' . $e->getMessage());
    // Surface the actual error message to help diagnose in dev
    respond(['success' => false, 'error' => 'Server error during submission: ' . $e->getMessage()], 500);
}
