<?php
// Admin status update endpoint
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../mail_notifications.php';
require_once __DIR__ . '/../notification_helpers.php';

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
$reportType = $table === 'found_reports' ? 'found' : 'lost';

try {
  $stmt = $pdo->prepare("SELECT r.item_name,
                                r.report_id,
                                r.contact_email,
                                r.user_id,
                                u.email AS account_email,
                                CONCAT(u.first_name, ' ', u.last_name) AS full_name
                          FROM {$table} r
                          JOIN users u ON u.id = r.user_id
                          WHERE r.report_id = :rid LIMIT 1");
  $stmt->execute([':rid' => $reportId]);
  $reportRow = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$reportRow) {
    fail('Report not found');
  }
} catch (Throwable $e) {
  fail('Server error');
}

try {
  $stmt = $pdo->prepare("UPDATE $table SET status = :st, updated_at = NOW() WHERE report_id = :rid LIMIT 1");
  $stmt->execute([':st'=>$newStatus, ':rid'=>$reportId]);
  if ($stmt->rowCount() !== 1) {
    fail('Report not found');
  }
  // Fire-and-forget email notification; failures are non-blocking.
  $contactEmail = trim((string)($reportRow['contact_email'] ?? ''));
  $accountEmail = trim((string)($reportRow['account_email'] ?? ''));
  $emails = [];
  if ($contactEmail !== '') {
    $emails[] = $contactEmail;
  }
  if ($accountEmail !== '' && $accountEmail !== $contactEmail) {
    $emails[] = $accountEmail;
  }
  if (!$emails && $accountEmail === '' && $contactEmail === '') {
    $emails[] = ''; // ensure we still attempt (will no-op)
  }
  $context = [
    'reportId' => $reportId,
    'itemName' => $reportRow['item_name'] ?? 'your item',
    'type' => $reportType,
    'status' => $action === 'approve' ? 'approved' : 'rejected',
  ];
  foreach ($emails as $emailAddr) {
    notify_report_status_change([
      'email' => $emailAddr,
      'name' => $reportRow['full_name'] ?? '',
    ], $context);
  }
  $accountUserId = intval($reportRow['user_id'] ?? 0);
  if ($accountUserId > 0) {
    $displayName = trim((string)($reportRow['item_name'] ?? ''));
    $actionVerb = $action === 'approve' ? 'verified' : 'rejected';
    $itemSuffix = $displayName !== '' ? " for \"{$displayName}\"" : '';
    $message = "Your {$reportType} report{$itemSuffix} (#{$reportId}) has been {$actionVerb}.";
    insert_notification($pdo, $accountUserId, $message, 'report_status', $reportId);
  }
  header('Location: /BA-3104/AdminDB/pendding.php?updated=1');
  exit;
} catch (Throwable $e) {
  fail('Server error');
}
