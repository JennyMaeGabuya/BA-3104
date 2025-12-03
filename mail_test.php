<?php
require __DIR__ . '/mail_notifications.php';
$recipient = trim($argv[1] ?? '');
if ($recipient === '') {
  $recipient = getenv('FINDIT_SMTP_USER') ?: 'test@example.com';
}
$result = findit_send_mail($recipient, '[FindIt] SMTP Connectivity Test', "This is a test email generated at " . date('c'));
if ($result) {
  echo "Mail send attempt finished: success\n";
} else {
  echo "Mail send attempt finished: failed\n";
}
