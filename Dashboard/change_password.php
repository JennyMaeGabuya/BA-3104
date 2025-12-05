<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../db_config.php';

header('Content-Type: application/json; charset=utf-8');
$userId = intval($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
  echo json_encode(['success' => false, 'error' => 'Not authenticated']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$current = trim($input['current_password'] ?? '');
$new = trim($input['new_password'] ?? '');

if ($current === '' || $new === '') {
  echo json_encode(['success' => false, 'error' => 'Missing fields']);
  exit;
}
if (strlen($new) < 8) {
  echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters']);
  exit;
}

try {
  $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $userId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
  }
  if (!password_verify($current, $row['password'])) {
    echo json_encode(['success' => false, 'error' => 'Current password incorrect']);
    exit;
  }

  $newHash = password_hash($new, PASSWORD_DEFAULT);
  $upd = $pdo->prepare('UPDATE users SET password = :pw WHERE id = :id');
  $upd->execute([':pw' => $newHash, ':id' => $userId]);
  echo json_encode(['success' => true, 'message' => 'Password updated']);
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
