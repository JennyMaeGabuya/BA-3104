<?php
// Helper shared between frontend and admin pages to keep the XML dump of user accounts up to date.
function ensure_directory(string $path): bool {
  if (is_dir($path)) {
    return true;
  }
  return @mkdir($path, 0777, true);
}

function log_user_export_error(string $message): void {
  $logDir = __DIR__ . '/logs';
  ensure_directory($logDir);
  $target = $logDir . '/db_errors.log';
  $line = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
  @file_put_contents($target, $line, FILE_APPEND | LOCK_EX);
}

function sync_user_accounts_xml(PDO $pdo): void {
  $dataDir = __DIR__ . '/data';
  if (!ensure_directory($dataDir)) {
    log_user_export_error('Unable to ensure data directory for XML export.');
    return;
  }
  try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, user_type, student_id, department, email, phone, created_at FROM users ORDER BY created_at ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Throwable $e) {
    log_user_export_error('Unable to fetch users for XML export: ' . $e->getMessage());
    return;
  }
  $file = $dataDir . '/user_accounts.xml';
  $dom = new DOMDocument('1.0', 'UTF-8');
  $dom->formatOutput = true;
  $root = $dom->appendChild($dom->createElement('users'));
  foreach ($users as $user) {
    $node = $dom->createElement('user');
    foreach ($user as $key => $value) {
      $child = $dom->createElement($key);
      $child->appendChild($dom->createTextNode((string)$value));
      $node->appendChild($child);
    }
    $root->appendChild($node);
  }
  $dom->save($file);
  @chmod($file, 0666);
}
