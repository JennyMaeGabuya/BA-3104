<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../db_config.php';

header('Content-Type: application/json');

function respond(int $statusCode, array $payload): void {
  http_response_code($statusCode);
  echo json_encode($payload);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(405, ['success' => false, 'error' => 'Method not allowed.']);
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  respond(401, ['success' => false, 'error' => 'Authentication required.']);
}

$reportId = trim((string)($_POST['report_id'] ?? ''));
$reportType = trim((string)($_POST['report_type'] ?? ''));
$itemName = trim((string)($_POST['item_name'] ?? ''));
$category = trim((string)($_POST['category'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$location = trim((string)($_POST['location'] ?? ''));
$dateEvent = trim((string)($_POST['date_event'] ?? ''));
$timeEvent = trim((string)($_POST['time_event'] ?? ''));
$email = trim((string)($_POST['contact_email'] ?? ''));
$phone = trim((string)($_POST['contact_phone'] ?? ''));
$pickup = trim((string)($_POST['pickup_location'] ?? ''));

if ($reportId === '') {
  respond(422, ['success' => false, 'error' => 'Missing report ID.']);
}

$type = $reportType !== '' ? ucfirst(strtolower($reportType)) : (str_starts_with($reportId, 'FR-') ? 'Found' : 'Lost');

$requiredFields = [
  'item name' => $itemName,
  'category' => $category,
  'description' => $description,
  'location' => $location,
  'date' => $dateEvent,
  'contact email' => $email,
  'contact phone' => $phone,
];

foreach ($requiredFields as $label => $value) {
  if ($value === '') {
    respond(422, ['success' => false, 'error' => 'Please provide the ' . $label . '.']);
  }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  respond(422, ['success' => false, 'error' => 'Please enter a valid email address.']);
}

if ($timeEvent === '') {
  $timeEvent = null;
}

try {
  if ($type === 'Found') {
    $sql = "UPDATE found_reports SET item_name = :name, category = :category, description = :description, location_found = :location,
            date_found = :date_event, time_found = :time_event, contact_email = :email, contact_phone = :phone,
            pickup_location = :pickup WHERE report_id = :report_id AND user_id = :user_id";
    $params = [
      ':name' => $itemName,
      ':category' => $category,
      ':description' => $description,
      ':location' => $location,
      ':date_event' => $dateEvent,
      ':time_event' => $timeEvent,
      ':email' => $email,
      ':phone' => $phone,
      ':pickup' => ($pickup !== '' ? $pickup : null),
      ':report_id' => $reportId,
      ':user_id' => $userId,
    ];
  } else {
    $sql = "UPDATE lost_reports SET item_name = :name, category = :category, description = :description, location = :location,
            date_lost = :date_event, time_lost = :time_event, contact_email = :email, contact_phone = :phone
            WHERE report_id = :report_id AND user_id = :user_id";
    $params = [
      ':name' => $itemName,
      ':category' => $category,
      ':description' => $description,
      ':location' => $location,
      ':date_event' => $dateEvent,
      ':time_event' => $timeEvent,
      ':email' => $email,
      ':phone' => $phone,
      ':report_id' => $reportId,
      ':user_id' => $userId,
    ];
  }

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  if ($stmt->rowCount() === 0) {
    respond(404, ['success' => false, 'error' => 'Report not found or no changes detected.']);
  }

  respond(200, ['success' => true, 'message' => 'Report updated successfully.']);
} catch (Throwable $e) {
  respond(500, ['success' => false, 'error' => 'Failed to update report: ' . $e->getMessage()]);
}