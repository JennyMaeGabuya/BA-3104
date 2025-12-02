<?php
// Admin status update endpoint
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../db_config.php';

function fail($msg) {
  http_response_code(400);
  echo $msg;
  exit;
}

if (!isset($_SESSION['user_id']) || (($_SESSION['user_type'] ?? '') !== 'Admin')) {
  header('Location: ../login.php');
  exit;
}

if (!isset($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['admin_csrf'] ?? '')) {
  fail('Invalid CSRF token');
}

$reportId = trim((string)($_POST['report_id'] ?? ''));
$action   = trim((string)($_POST['action'] ?? ''));
if ($reportId === '' || !in_array($action, ['approve','reject'], true)) {
  fail('Invalid parameters');
}

$table = str_starts_with($reportId, 'FR-') ? 'found_reports' : 'lost_reports';
$newStatus = $action === 'approve' ? 'Verified' : 'Rejected';

try {
  $stmt = $pdo->prepare("UPDATE $table SET status = :st, updated_at = NOW() WHERE report_id = :rid LIMIT 1");
  $stmt->execute([':st'=>$newStatus, ':rid'=>$reportId]);
  if ($stmt->rowCount() !== 1) {
    fail('Report not found');
  }
  header('Location: /BA-3104/AdminDB/pendding.php?updated=1');
  exit;
} catch (Throwable $e) {
  fail('Server error');
}
