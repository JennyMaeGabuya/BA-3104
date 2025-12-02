<?php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../db_config.php';

header('Content-Type: application/json');

function respond(bool $ok, string $message = ''): void {
  echo json_encode($ok ? ['success' => true, 'message' => $message] : ['success' => false, 'error' => $message]);
  exit;
}

if (!isset($_SESSION['user_id']) || (($_SESSION['user_type'] ?? '') !== 'Admin')) {
  http_response_code(401);
  respond(false, 'Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  respond(false, 'Invalid method');
}

$requestCode = trim((string)($_POST['request_code'] ?? ''));
$newStatus = trim((string)($_POST['status'] ?? ''));
if ($requestCode === '' || !in_array($newStatus, ['Approved','Rejected','Resolved','Claimed'], true)) {
  respond(false, 'Invalid parameters');
}

$canonicalStatus = $newStatus === 'Claimed' ? 'Resolved' : $newStatus;

try {
  $stmt = $pdo->prepare("UPDATE claim_requests SET status = :status, updated_at = NOW() WHERE request_code = :code LIMIT 1");
  $stmt->execute([':status' => $canonicalStatus, ':code' => $requestCode]);
  if ($stmt->rowCount() !== 1) {
    respond(false, 'Request not found');
  }
  respond(true, 'Claim request updated');
} catch (Throwable $e) {
  respond(false, 'Server error');
}
