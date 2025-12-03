<?php
/**
 * Centralized email notification helper for FindIt workflow events.
 *
 * Usage:
 *   require_once __DIR__ . '/mail_notifications.php';
 *   notify_report_status_change([
 *     'email' => 'user@example.com',
 *     'name' => 'Jane Doe',
 *   ], [
 *     'reportId' => 'LR-001',
 *     'itemName' => 'Backpack',
 *     'type' => 'lost', // lost|found|claim
 *     'status' => 'approved', // approved|rejected
 *   ]);
 */

declare(strict_types=1);

// Auto-load local mail_config.php if present to populate SMTP env vars.
$mailConfigPath = __DIR__ . '/mail_config.php';
if (is_file($mailConfigPath)) {
  require_once $mailConfigPath;
}

/**
 * Determine default From header. Can be overridden via FINDIT_MAIL_FROM env.
 */
function findit_mail_from(): string {
  $from = getenv('FINDIT_MAIL_FROM');
  if ($from && trim($from) !== '') {
    return $from;
  }
  return 'FindIt Admin <no-reply@findit.local>';
}

/**
 * Determine default Reply-To header. Override via FINDIT_MAIL_REPLY env.
 */
function findit_mail_reply(): string {
  $reply = getenv('FINDIT_MAIL_REPLY');
  if ($reply && trim($reply) !== '') {
    return $reply;
  }
  return 'FindIt Helpdesk <helpdesk@findit.local>';
}

function findit_log_mail(string $message): void {
  static $logPath = null;
  if ($logPath === null) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
      @mkdir($logDir, 0775, true);
    }
    $logPath = $logDir . '/mail.log';
  }
  $timestamp = date('Y-m-d H:i:s');
  @file_put_contents($logPath, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

/**
 * Fetch SMTP configuration from environment variables when available.
 * Expected vars:
 *   FINDIT_SMTP_HOST, FINDIT_SMTP_USER, FINDIT_SMTP_PASS, FINDIT_SMTP_PORT, FINDIT_SMTP_SECURE
 */
function findit_smtp_config(): ?array {
  $host = trim((string)getenv('FINDIT_SMTP_HOST'));
  $user = trim((string)getenv('FINDIT_SMTP_USER'));
  $pass = (string)getenv('FINDIT_SMTP_PASS');
  if ($host === '' || $user === '' || $pass === '') {
    return null;
  }
  $port = (int)(getenv('FINDIT_SMTP_PORT') ?: 587);
  $secure = strtolower((string)(getenv('FINDIT_SMTP_SECURE') ?: 'tls'));
  if (!in_array($secure, ['ssl','tls','none'], true)) {
    $secure = 'tls';
  }
  return [
    'host' => $host,
    'user' => $user,
    'pass' => $pass,
    'port' => $port > 0 ? $port : 587,
    'secure' => $secure,
  ];
}

function findit_flatten_headers(array $headers): string {
  $lines = [];
  foreach ($headers as $key => $value) {
    $lines[] = $key . ': ' . $value;
  }
  return implode("\r\n", $lines);
}

function findit_build_smtp_message(string $to, string $subject, string $body, array $headers): string {
  $headerLines = [];
  $headerLines[] = 'To: ' . $to;
  $headerLines[] = 'Subject: ' . $subject;
  if (!isset($headers['Date'])) {
    $headers['Date'] = date(DATE_RFC2822);
  }
  foreach ($headers as $key => $value) {
    if (in_array(strtolower($key), ['to','subject'], true)) {
      continue;
    }
    $headerLines[] = $key . ': ' . $value;
  }
  $raw = implode("\r\n", $headerLines) . "\r\n\r\n" . $body;
  $raw = str_replace(["\r\n", "\n\r", "\r"], "\n", $raw);
  $raw = str_replace("\n", "\r\n", $raw);
  $raw = preg_replace("/(^|\r\n)\./", "$1..", $raw);
  if (!str_ends_with($raw, "\r\n")) {
    $raw .= "\r\n";
  }
  return $raw;
}

function findit_extract_email(string $address): string {
  if (preg_match('/<([^>]+)>/', $address, $matches)) {
    return trim($matches[1]);
  }
  return trim($address);
}

function findit_smtp_read($stream)
{
  $data = '';
  while (($line = fgets($stream, 515)) !== false) {
    $data .= $line;
    if (strlen($line) < 4) {
      break;
    }
    if ($line[3] === ' ') {
      break;
    }
  }
  return $data === '' ? false : $data;
}

function findit_smtp_expect($stream, string $code): bool {
  $response = findit_smtp_read($stream);
  return $response !== false && str_starts_with($response, $code);
}

function findit_send_via_smtp(array $config, string $to, string $subject, string $body, array $headers, ?string &$error = null): bool {
  $remote = $config['host'] . ':' . $config['port'];
  $secure = $config['secure'];
  if ($secure === 'ssl') {
    $remote = 'ssl://' . $config['host'] . ':' . $config['port'];
  }
  $stream = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
  if (!$stream) {
    $error = "Unable to connect to SMTP server: {$errstr} ({$errno})";
    return false;
  }
  stream_set_timeout($stream, 20);
  if (!findit_smtp_expect($stream, '220')) {
    $error = 'SMTP greeting not received';
    fclose($stream);
    return false;
  }
  $hostName = gethostname() ?: 'localhost';
  fwrite($stream, "EHLO {$hostName}\r\n");
  if (!findit_smtp_expect($stream, '250')) {
    $error = 'EHLO rejected';
    fclose($stream);
    return false;
  }
  if ($secure === 'tls') {
    fwrite($stream, "STARTTLS\r\n");
    if (!findit_smtp_expect($stream, '220')) {
      $error = 'STARTTLS rejected';
      fclose($stream);
      return false;
    }
    if (!stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
      $error = 'Failed to enable TLS encryption';
      fclose($stream);
      return false;
    }
    fwrite($stream, "EHLO {$hostName}\r\n");
    if (!findit_smtp_expect($stream, '250')) {
      $error = 'Post-STARTTLS EHLO rejected';
      fclose($stream);
      return false;
    }
  }
  fwrite($stream, "AUTH LOGIN\r\n");
  if (!findit_smtp_expect($stream, '334')) {
    $error = 'AUTH LOGIN not accepted';
    fclose($stream);
    return false;
  }
  fwrite($stream, base64_encode($config['user']) . "\r\n");
  if (!findit_smtp_expect($stream, '334')) {
    $error = 'SMTP username rejected';
    fclose($stream);
    return false;
  }
  fwrite($stream, base64_encode($config['pass']) . "\r\n");
  if (!findit_smtp_expect($stream, '235')) {
    $error = 'SMTP password rejected';
    fclose($stream);
    return false;
  }
  $fromEnvelope = findit_extract_email($headers['From'] ?? findit_mail_from());
  $toEnvelope = findit_extract_email($to);
  fwrite($stream, "MAIL FROM:<{$fromEnvelope}>\r\n");
  if (!findit_smtp_expect($stream, '250')) {
    $error = 'MAIL FROM command rejected';
    fclose($stream);
    return false;
  }
  fwrite($stream, "RCPT TO:<{$toEnvelope}>\r\n");
  if (!findit_smtp_expect($stream, '250')) {
    $error = 'RCPT TO command rejected';
    fclose($stream);
    return false;
  }
  fwrite($stream, "DATA\r\n");
  if (!findit_smtp_expect($stream, '354')) {
    $error = 'DATA command rejected';
    fclose($stream);
    return false;
  }
  $message = findit_build_smtp_message($to, $subject, $body, $headers);
  fwrite($stream, $message . ".\r\n");
  if (!findit_smtp_expect($stream, '250')) {
    $error = 'SMTP server rejected message body';
    fclose($stream);
    return false;
  }
  fwrite($stream, "QUIT\r\n");
  fclose($stream);
  return true;
}

/**
 * Low-level mail sender wrapper. Uses PHP mail() by default; swap out if SMTP is configured.
 *
 * @param string $to
 * @param string $subject
 * @param string $body
 * @param array  $headers Optional associative headers.
 */
function findit_send_mail(string $to, string $subject, string $body, array $headers = []): bool {
  $baseHeaders = [
    'From' => findit_mail_from(),
    'Reply-To' => findit_mail_reply(),
    'Content-Type' => 'text/plain; charset=UTF-8',
  ];
  $allHeaders = array_merge($baseHeaders, $headers);
  $flattened = findit_flatten_headers($allHeaders);
  $smtpConfig = findit_smtp_config();
  if ($smtpConfig) {
    $smtpError = null;
    if (findit_send_via_smtp($smtpConfig, $to, $subject, $body, $allHeaders, $smtpError)) {
      findit_log_mail("SMTP success → {$to} | {$subject}");
      return true;
    }
    findit_log_mail("SMTP failed → {$to} | {$subject} | {$smtpError}");
  }
  // In case mail() is unavailable, callers can stub this function during testing.
  $mailResult = @mail($to, $subject, $body, rtrim($flattened));
  if ($mailResult) {
    findit_log_mail("mail() fallback success → {$to} | {$subject}");
  } else {
    findit_log_mail("mail() fallback failed → {$to} | {$subject}");
  }
  return $mailResult;
}

/**
 * Helper to render friendly names (falls back to email if missing).
 */
function findit_display_name(array $recipient): string {
  $name = trim((string)($recipient['name'] ?? ''));
  if ($name !== '') {
    return $name;
  }
  $email = trim((string)($recipient['email'] ?? ''));
  return $email !== '' ? $email : 'FindIt user';
}

/**
 * Notify a user about a report approval or rejection.
 *
 * @param array $recipient ['email'=>..., 'name'=>...]
 * @param array $context  ['reportId'=>..., 'itemName'=>..., 'type'=>'lost|found', 'status'=>'approved|rejected']
 */
function notify_report_status_change(array $recipient, array $context): bool {
  if (empty($recipient['email'])) {
    return false;
  }
  $typeLabel = ($context['type'] ?? 'report') === 'found' ? 'found' : 'lost';
  $reportId = $context['reportId'] ?? 'report';
  $itemName = $context['itemName'] ?? 'item';
  $status = $context['status'] ?? '';
  $userName = findit_display_name($recipient);

  if ($status === 'approved') {
    $subject = "[FindIt] Your {$typeLabel} report ({$reportId}) is approved";
    $body = "Hi {$userName},\n\n" .
      "Good news! Your {$typeLabel} report for '{$itemName}' (ID {$reportId}) " .
      "has been approved by the admin team and is now active in the system.\n" .
      "If this was a found report, it is now listed publicly with limited details.\n" .
      "If this was a lost report, we will notify you if a matching item is located.\n\n" .
      "Thank you for using FindIt@BatStateU.\n";
  } else {
    $subject = "[FindIt] Your {$typeLabel} report ({$reportId}) was not approved";
    $body = "Hi {$userName},\n\n" .
      "We reviewed your {$typeLabel} report for '{$itemName}' (ID {$reportId}), " .
      "but it could not be approved at this time. Please review your submission " .
      "and contact the admin office if you believe this is an error.\n\n" .
      "Thank you.\n";
  }

  return findit_send_mail($recipient['email'], $subject, $body);
}

/**
 * Notify both parties (admin + reporter) about a potential match that needs verification.
 *
 * @param array $recipient ['email'=>..., 'name'=>...]
 * @param array $context ['foundId'=>..., 'lostId'=>..., 'confidence'=>float]
 */
function notify_match_detected(array $recipient, array $context): bool {
  if (empty($recipient['email'])) {
    return false;
  }
  $foundId = $context['foundId'] ?? 'FR-???';
  $lostId = $context['lostId'] ?? 'LR-???';
  $confidence = $context['confidence'] ?? null;
  $scoreText = $confidence !== null ? " (confidence {$confidence}%)" : '';
  $userName = findit_display_name($recipient);

  $subject = "[FindIt] Potential match detected: {$lostId} ↔ {$foundId}";
  $body = "Hi {$userName},\n\n" .
    "Our system detected a potential match between lost report {$lostId} and found report {$foundId}{$scoreText}.\n" .
    "The admin team will now review the details. We will notify you once the match is verified.\n\n" .
    "This is an automated message — no action is required right now.\n";

  return findit_send_mail($recipient['email'], $subject, $body);
}

/**
 * Notify claimant/owner that an item is ready for pickup (Mark as Claimable).
 *
 * @param array $recipient ['email'=>..., 'name'=>...]
 * @param array $context ['reportId'=>..., 'itemName'=>..., 'pickupLocation'=>..., 'pickupWindow'=>..., 'referenceCode'=>...]
 */
function notify_item_claimable(array $recipient, array $context): bool {
  if (empty($recipient['email'])) {
    return false;
  }
  $reportId = $context['reportId'] ?? 'report';
  $itemName = $context['itemName'] ?? 'your item';
  $location = $context['pickupLocation'] ?? 'the admin office';
  $window = $context['pickupWindow'] ?? 'the posted schedule';
  $reference = $context['referenceCode'] ?? strtoupper(substr($reportId, -6));
  $userName = findit_display_name($recipient);

  $subject = "[FindIt] Item ready for pickup ({$reportId})";
  $body = "Hi {$userName},\n\n" .
    "The admin team verified your claim for '{$itemName}' (ID {$reportId}).\n" .
    "You may now claim the item at {$location}. Pickup window: {$window}.\n" .
    "Please bring your BatStateU ID and provide this reference code: {$reference}.\n\n" .
    "If you are unable to claim within the allotted time, reply to this email or contact the campus admin office.\n";

  return findit_send_mail($recipient['email'], $subject, $body);
}

/**
 * Notify that an item has been marked as claimed.
 *
 * @param array $recipient ['email'=>..., 'name'=>...]
 * @param array $context ['reportId'=>..., 'itemName'=>..., 'claimedAt'=>...] 
 */
function notify_item_claimed(array $recipient, array $context): bool {
  if (empty($recipient['email'])) {
    return false;
  }
  $reportId = $context['reportId'] ?? 'report';
  $itemName = $context['itemName'] ?? 'your item';
  $claimedAt = $context['claimedAt'] ?? date('Y-m-d H:i');
  $userName = findit_display_name($recipient);

  $subject = "[FindIt] Item claimed confirmation ({$reportId})";
  $body = "Hi {$userName},\n\n" .
    "This is to confirm that '{$itemName}' (ID {$reportId}) was marked as claimed on {$claimedAt}.\n" .
    "Thank you for helping keep the FindIt system accurate. If you did not complete this pickup, please contact the admin office immediately.\n";

  return findit_send_mail($recipient['email'], $subject, $body);
}

/**
 * Notify claimant if a match/claim was rejected.
 */
function notify_claim_rejected(array $recipient, array $context): bool {
  if (empty($recipient['email'])) {
    return false;
  }
  $reportId = $context['reportId'] ?? 'report';
  $itemName = $context['itemName'] ?? 'the item';
  $reason = $context['reason'] ?? 'the provided proof was insufficient';
  $userName = findit_display_name($recipient);

  $subject = "[FindIt] Claim update for {$reportId}";
  $body = "Hi {$userName},\n\n" .
    "We reviewed your claim for '{$itemName}' (ID {$reportId}), but it was not approved.\n" .
    "Reason: {$reason}. You may submit more proof or contact the admin office if you believe this is an error.\n";

  return findit_send_mail($recipient['email'], $subject, $body);
}

/**
 * Utility to send arbitrary admin notifications when needed (e.g., new pending report).
 */
function notify_admins(array $emails, string $subject, string $body): void {
  foreach ($emails as $email) {
    $trimmed = trim($email);
    if ($trimmed === '') {
      continue;
    }
    findit_send_mail($trimmed, $subject, $body);
  }
}
