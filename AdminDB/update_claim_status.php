<?php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../mail_notifications.php';

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
  $claimStmt = $pdo->prepare("SELECT cr.*, u.email AS user_email, u.first_name, u.last_name,
                                     fr.item_name AS found_item_name, fr.pickup_location, fr.location_found
                              FROM claim_requests cr
                              JOIN users u ON u.id = cr.user_id
                              LEFT JOIN found_reports fr ON fr.report_id = cr.report_id
                              WHERE cr.request_code = :code
                              LIMIT 1");
  $claimStmt->execute([':code' => $requestCode]);
  $claimRow = $claimStmt->fetch(PDO::FETCH_ASSOC);
  if (!$claimRow) {
    respond(false, 'Request not found');
  }
} catch (Throwable $e) {
  respond(false, 'Unable to load claim details');
}

try {
  $stmt = $pdo->prepare("UPDATE claim_requests SET status = :status, updated_at = NOW() WHERE request_code = :code LIMIT 1");
  $stmt->execute([':status' => $canonicalStatus, ':code' => $requestCode]);
  if ($stmt->rowCount() !== 1) {
    respond(false, 'Request not found');
  }
  if (!empty($claimRow['report_id'])) {
    sync_found_report_status($pdo, $claimRow['report_id'], $canonicalStatus);
  }
  try {
    dispatch_claim_notifications($canonicalStatus, $claimRow);
  } catch (Throwable $notifyError) {
    error_log('Claim notification dispatch failed: ' . $notifyError->getMessage());
  }
  respond(true, 'Claim request updated');
} catch (Throwable $e) {
  respond(false, 'Server error');
}

function dispatch_claim_notifications(string $status, array $claim): void {
  if (!$claim) {
    return;
  }
  $recipients = build_claim_recipient_list($claim);
  if (empty($recipients)) {
    return;
  }
  $reportId = $claim['report_id'] ?? ($claim['request_code'] ?? 'report');
  $itemName = $claim['found_item_name'] ?? 'your item';
  if ($status === 'Approved') {
    $pickupLocation = $claim['pickup_location'] ?? ($claim['location_found'] ?? 'the admin office');
    $pickupWindow = trim((string)getenv('FINDIT_PICKUP_WINDOW')) ?: 'Mon-Fri, 8 AM - 5 PM';
    $reference = $claim['request_code'] ?? strtoupper(substr($reportId, -6));
    $ctx = [
      'reportId' => $reportId,
      'itemName' => $itemName,
      'pickupLocation' => $pickupLocation,
      'pickupWindow' => $pickupWindow,
      'referenceCode' => $reference,
    ];
    foreach ($recipients as $recipient) {
      notify_item_claimable($recipient, $ctx);
    }
  } elseif ($status === 'Resolved') {
    $ctx = [
      'reportId' => $reportId,
      'itemName' => $itemName,
      'claimedAt' => date('Y-m-d H:i'),
    ];
    foreach ($recipients as $recipient) {
      notify_item_claimed($recipient, $ctx);
    }
  } elseif ($status === 'Rejected') {
    $ctx = [
      'reportId' => $reportId,
      'itemName' => $itemName,
      'reason' => 'The admin marked this claim as not a match.',
    ];
    foreach ($recipients as $recipient) {
      notify_claim_rejected($recipient, $ctx);
    }
  }
}

function sync_found_report_status(PDO $pdo, string $reportId, string $status): void {
  if ($reportId === '') {
    return;
  }
  if ($status === 'Resolved') {
    $stmt = $pdo->prepare("UPDATE found_reports SET status = 'Claimed', updated_at = NOW() WHERE report_id = :rid LIMIT 1");
    $stmt->execute([':rid' => $reportId]);
  }
}

function build_claim_recipient_list(array $claim): array {
  $name = trim(((string)($claim['first_name'] ?? '')) . ' ' . ((string)($claim['last_name'] ?? '')));
  $emails = [];
  $accountEmail = trim((string)($claim['user_email'] ?? ''));
  if ($accountEmail !== '' && filter_var($accountEmail, FILTER_VALIDATE_EMAIL)) {
    $emails[$accountEmail] = ['email' => $accountEmail, 'name' => $name];
  }
  $contactInfo = trim((string)($claim['contact_info'] ?? ''));
  if ($contactInfo !== '' && filter_var($contactInfo, FILTER_VALIDATE_EMAIL) && !isset($emails[$contactInfo])) {
    $emails[$contactInfo] = ['email' => $contactInfo, 'name' => $name];
  }
  return array_values($emails);
}
