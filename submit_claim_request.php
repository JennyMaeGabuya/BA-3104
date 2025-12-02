<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json');

function respond(bool $ok, string $message = '', array $extra = []): void {
  $payload = array_merge(['success' => $ok], $ok ? ['message' => $message] : ['error' => $message], $extra);
  echo json_encode($payload);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  respond(false, 'Invalid request method.');
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  http_response_code(401);
  respond(false, 'You must be logged in to submit a claim request.');
}

$reportId = trim((string)($_POST['report_id'] ?? ''));
$details  = trim((string)($_POST['details'] ?? ''));
$contact  = trim((string)($_POST['contact'] ?? ''));

if ($reportId === '' || $details === '' || $contact === '') {
  respond(false, 'All fields are required.');
}

if (!isset($_FILES['school_id']) || !is_uploaded_file($_FILES['school_id']['tmp_name'])) {
  respond(false, 'Please upload a photo of your school ID.');
}

$idFile = $_FILES['school_id'];
$allowedMime = ['image/jpeg','image/png','image/webp'];
$allowedExt  = ['jpg','jpeg','png','webp'];
$mime = mime_content_type($idFile['tmp_name']);
$ext = strtolower(pathinfo($idFile['name'], PATHINFO_EXTENSION));

if (!in_array($mime, $allowedMime, true) || !in_array($ext, $allowedExt, true)) {
  respond(false, 'Only JPG, PNG, or WEBP images are allowed.');
}

if ($idFile['size'] > 10 * 1024 * 1024) { // 10 MB
  respond(false, 'ID photo is too large. Maximum size is 10MB.');
}

try {
  $stmt = $pdo->prepare("SELECT report_id, status FROM found_reports WHERE report_id = :rid LIMIT 1");
  $stmt->execute([':rid' => $reportId]);
  $report = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$report) {
    respond(false, 'Report not found or unavailable.');
  }
} catch (Throwable $e) {
  respond(false, 'Failed to validate report.');
}

$uploadDir = __DIR__ . '/ImageUploads/claims';
if (!is_dir($uploadDir)) {
  if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    respond(false, 'Unable to create upload directory.');
  }
}

$cleanReport = preg_replace('/[^A-Za-z0-9]/', '', $reportId);
$filename = 'CLAIM_' . $cleanReport . '_' . time() . '.' . $ext;
$destPath = $uploadDir . '/' . $filename;
if (!move_uploaded_file($idFile['tmp_name'], $destPath)) {
  respond(false, 'Failed to save ID photo.');
}
$relativePath = 'ImageUploads/claims/' . $filename;

try {
  $maxStmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(request_code, 4) AS UNSIGNED)) AS max_code FROM claim_requests WHERE request_code REGEXP '^CR-[0-9]{3,}$'");
  $maxRow = $maxStmt->fetch(PDO::FETCH_ASSOC);
  $next = (int)($maxRow['max_code'] ?? 0) + 1;
  $requestCode = sprintf('CR-%03d', $next);

  $insert = $pdo->prepare("INSERT INTO claim_requests (request_code, report_id, item_type, user_id, details, contact_info, id_photo_path)
    VALUES (:code, :report, 'Found', :uid, :details, :contact, :photo)");
  $insert->execute([
    ':code' => $requestCode,
    ':report' => $reportId,
    ':uid' => $userId,
    ':details' => $details,
    ':contact' => $contact,
    ':photo' => $relativePath,
  ]);

  respond(true, 'Claim request submitted.', ['requestCode' => $requestCode]);
} catch (Throwable $e) {
  error_log('Claim request error: ' . $e->getMessage());
  respond(false, 'Server error while saving claim request.');
}
