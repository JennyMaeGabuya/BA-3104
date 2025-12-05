<?php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../mail_notifications.php';
require_once __DIR__ . '/../notification_helpers.php';
require_once __DIR__ . '/../match_workflow_helpers.php';

header('Content-Type: application/json');

function respond(bool $success, string $message = '', array $extra = []): void {
  $base = $success ? ['success' => true, 'message' => $message] : ['success' => false, 'error' => $message];
  echo json_encode(array_merge($base, $extra));
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

$action = strtolower(trim((string)($_POST['action'] ?? '')));
$lostId = trim((string)($_POST['lost_report_id'] ?? ''));
$foundId = trim((string)($_POST['found_report_id'] ?? ''));
$validActions = ['claimable' => 'claimable', 'claimed' => 'claimed', 'reject' => 'rejected', "doesnt_match" => 'rejected'];

if ($lostId === '' || $foundId === '' || !isset($validActions[$action])) {
  respond(false, 'Invalid parameters supplied.');
}

$targetStatus = $validActions[$action];
$adminId = (int)($_SESSION['user_id'] ?? 0);

try {
  ensure_match_workflow_table($pdo);

  $pdo->beginTransaction();

  $lostStmt = $pdo->prepare("SELECT lr.report_id, lr.item_name, lr.contact_email, lr.status, lr.user_id,
                                      u.email AS account_email,
                                      CONCAT(u.first_name, ' ', u.last_name) AS full_name
                               FROM lost_reports lr
                               JOIN users u ON u.id = lr.user_id
                               WHERE lr.report_id = :lostId LIMIT 1");
  $lostStmt->execute([':lostId' => $lostId]);
  $lostRow = $lostStmt->fetch(PDO::FETCH_ASSOC);
  if (!$lostRow) {
    $pdo->rollBack();
    respond(false, 'Lost report not found.');
  }

  $foundStmt = $pdo->prepare("SELECT fr.report_id, fr.item_name, fr.contact_email, fr.status, fr.user_id,
                                       fr.pickup_location, fr.location_found,
                                       u.email AS account_email,
                                       CONCAT(u.first_name, ' ', u.last_name) AS full_name
                                FROM found_reports fr
                                JOIN users u ON u.id = fr.user_id
                                WHERE fr.report_id = :foundId LIMIT 1");
  $foundStmt->execute([':foundId' => $foundId]);
  $foundRow = $foundStmt->fetch(PDO::FETCH_ASSOC);
  if (!$foundRow) {
    $pdo->rollBack();
    respond(false, 'Found report not found.');
  }

  $select = $pdo->prepare("SELECT id, status FROM match_workflows WHERE lost_report_id = :lost AND found_report_id = :found LIMIT 1");
  $select->execute([':lost' => $lostId, ':found' => $foundId]);
  $matchRow = $select->fetch(PDO::FETCH_ASSOC);

  if ($matchRow) {
    $update = $pdo->prepare("UPDATE match_workflows
                              SET status = :status, admin_id = :admin, updated_at = NOW()
                              WHERE id = :id");
    $update->execute([
      ':status' => $targetStatus,
      ':admin' => $adminId ?: null,
      ':id' => $matchRow['id'],
    ]);
  } else {
    $insert = $pdo->prepare("INSERT INTO match_workflows (lost_report_id, found_report_id, status, admin_id)
                              VALUES (:lost, :found, :status, :admin)");
    $insert->execute([
      ':lost' => $lostId,
      ':found' => $foundId,
      ':status' => $targetStatus,
      ':admin' => $adminId ?: null,
    ]);
  }

  if ($targetStatus === 'claimed') {
    $pdo->prepare("UPDATE lost_reports SET status = 'Claimed', updated_at = NOW() WHERE report_id = :rid LIMIT 1")
        ->execute([':rid' => $lostId]);
    $pdo->prepare("UPDATE found_reports SET status = 'Claimed', updated_at = NOW() WHERE report_id = :rid LIMIT 1")
        ->execute([':rid' => $foundId]);
  }

  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  respond(false, 'Server error while updating match workflow.');
}

function build_recipient_list(array $row): array {
  $recipients = [];
  $contact = trim((string)($row['contact_email'] ?? ''));
  $account = trim((string)($row['account_email'] ?? ''));
  if ($contact !== '') {
    $recipients[] = ['email' => $contact, 'name' => $row['full_name'] ?? ''];
  }
  if ($account !== '' && $account !== $contact) {
    $recipients[] = ['email' => $account, 'name' => $row['full_name'] ?? ''];
  }
  return $recipients;
}

$lostRecipients = build_recipient_list($lostRow ?? []);
$foundRecipients = build_recipient_list($foundRow ?? []);

$context = [
  'reportId' => $foundId,
  'itemName' => $foundRow['item_name'] ?? ($lostRow['item_name'] ?? 'item'),
  'pickupLocation' => $foundRow['pickup_location'] ?? ($foundRow['location_found'] ?? 'the admin office'),
  'pickupWindow' => 'Mon-Fri, 8 AM - 5 PM',
  'referenceCode' => strtoupper(substr($foundId, -6)),
];

if ($targetStatus === 'claimable') {
  foreach ($lostRecipients as $recipient) {
    notify_item_claimable($recipient, $context);
  }
} elseif ($targetStatus === 'claimed') {
  $claimedCtx = [
    'reportId' => $foundId,
    'itemName' => $foundRow['item_name'] ?? ($lostRow['item_name'] ?? 'item'),
    'claimedAt' => date('Y-m-d H:i'),
  ];
  foreach ($lostRecipients as $recipient) {
    notify_item_claimed($recipient, $claimedCtx);
  }
  foreach ($foundRecipients as $recipient) {
    notify_item_claimed($recipient, $claimedCtx);
  }
} elseif ($targetStatus === 'rejected') {
  $rejectCtx = [
    'reportId' => $foundId,
    'itemName' => $foundRow['item_name'] ?? ($lostRow['item_name'] ?? 'item'),
    'reason' => 'The admin marked the suggested match as not a match.',
  ];
  foreach ($lostRecipients as $recipient) {
    notify_claim_rejected($recipient, $rejectCtx);
  }
}

if ($targetStatus === 'claimable') {
  record_match_notification($pdo, $lostRow, 'lost', 'claimable', $lostId, $foundId, $context['itemName'] ?? 'item');
  record_match_notification($pdo, $foundRow, 'found', 'claimable', $lostId, $foundId, $context['itemName'] ?? 'item');
} elseif ($targetStatus === 'claimed') {
  $claimedItem = $foundRow['item_name'] ?? ($lostRow['item_name'] ?? 'item');
  record_match_notification($pdo, $lostRow, 'lost', 'claimed', $lostId, $foundId, $claimedItem);
  record_match_notification($pdo, $foundRow, 'found', 'claimed', $lostId, $foundId, $claimedItem);
} elseif ($targetStatus === 'rejected') {
  $rejectedItem = $foundRow['item_name'] ?? ($lostRow['item_name'] ?? 'item');
  record_match_notification($pdo, $lostRow, 'lost', 'rejected', $lostId, $foundId, $rejectedItem);
  record_match_notification($pdo, $foundRow, 'found', 'rejected', $lostId, $foundId, $rejectedItem);
}

respond(true, 'Match workflow updated.', [
  'status' => $targetStatus,
]);

function record_match_notification(PDO $pdo, array $report, string $sourceLabel, string $status, string $lostId, string $foundId, string $itemName): void {
  $userId = intval($report['user_id'] ?? 0);
  if ($userId <= 0) {
    return;
  }
  $baseReportId = $report['report_id'] ?? ($status === 'claimed' ? $foundId : $lostId);
  $referenceId = $sourceLabel === 'lost' ? $foundId : $lostId;
  $message = '';
  $type = 'claim_event';
  if ($status === 'claimable') {
    $message = "Your {$sourceLabel} report ({$baseReportId}) for '{$itemName}' is ready for pickup because of match {$referenceId}.";
    $type = 'claim_ready';
  } elseif ($status === 'claimed') {
    $message = "Match {$referenceId} for '{$itemName}' was marked claimed.";
    $type = 'claim_resolved';
  } elseif ($status === 'rejected') {
    $message = "Match {$referenceId} for '{$itemName}' was rejected.";
    $type = 'claim_rejected';
  }
  insert_notification($pdo, $userId, $message, $type, $referenceId ?: $baseReportId);
}
