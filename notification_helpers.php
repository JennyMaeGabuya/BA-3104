<?php

function ensure_notifications_table(PDO $pdo): void {
  static $initialized = false;
  if ($initialized) {
    return;
  }
  $sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id VARCHAR(64) DEFAULT NULL,
    message TEXT NOT NULL,
    notification_type VARCHAR(64) NOT NULL DEFAULT 'general',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_user_unread (user_id, is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
  try {
    $pdo->exec($sql);
  } catch (\Throwable $e) {
    log_notification_error('Unable to ensure notifications table: ' . $e->getMessage());
  }
  $initialized = true;
}

function log_notification_error(string $message): void {
  $logDir = __DIR__ . '/logs';
  if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
  }
  $target = $logDir . '/db_errors.log';
  @file_put_contents($target, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function insert_notification(PDO $pdo, int $userId, string $message, string $type = 'general', ?string $itemId = null): bool {
  if ($userId <= 0 || trim($message) === '') {
    return false;
  }
  ensure_notifications_table($pdo);
  try {
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, item_id, message, notification_type) VALUES (:user_id, :item_id, :message, :type)');
    return $stmt->execute([
      ':user_id' => $userId,
      ':item_id' => $itemId,
      ':message' => $message,
      ':type' => $type,
    ]);
  } catch (\Throwable $e) {
    log_notification_error('Unable to insert notification: ' . $e->getMessage());
    return false;
  }
}

function fetch_user_notifications(PDO $pdo, int $userId, int $limit = 25): array {
  if ($userId <= 0) {
    return [];
  }
  ensure_notifications_table($pdo);
  $limit = max(1, min(200, $limit));
  try {
    $sql = "SELECT id, item_id, message, notification_type, is_read, created_at FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT {$limit}";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (\Throwable $e) {
    log_notification_error('Unable to fetch notifications: ' . $e->getMessage());
    return [];
  }
}

function mark_notifications_read(PDO $pdo, int $userId): void {
  if ($userId <= 0) {
    return;
  }
  ensure_notifications_table($pdo);
  try {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0');
    $stmt->execute([':uid' => $userId]);
  } catch (\Throwable $e) {
    log_notification_error('Unable to mark notifications read: ' . $e->getMessage());
  }
}

function notification_icon_class(string $type): string {
  switch ($type) {
    case 'claim_ready':
    case 'claim_event':
      return 'notification-page-icon--blue';
    case 'claim_rejected':
      return 'notification-page-icon--yellow';
    case 'report_status':
    case 'claim_resolved':
      return 'notification-page-icon--green';
    default:
      return 'notification-page-icon--blue';
  }
}

function format_relative_time(?string $timestamp): string {
  if (!$timestamp) {
    return 'just now';
  }
  $ts = strtotime($timestamp);
  if ($ts === false) {
    return 'just now';
  }
  $diff = time() - $ts;
  if ($diff < 60) return 'just now';
  if ($diff < 3600) return floor($diff / 60) . ' minute' . ($diff < 120 ? '' : 's') . ' ago';
  if ($diff < 86400) return floor($diff / 3600) . ' hour' . ($diff < 7200 ? '' : 's') . ' ago';
  if ($diff < 172800) return 'yesterday';
  return date('M j, Y', $ts);
}
