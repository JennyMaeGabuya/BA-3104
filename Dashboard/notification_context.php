<?php
require_once __DIR__ . '/../notification_helpers.php';

function build_notification_context(PDO $pdo, ?int $userId, int $limit = 10): array {
  $userId = (int)($userId ?? 0);
  $notifications = $userId > 0 ? fetch_user_notifications($pdo, $userId, $limit) : [];
  $unread = count(array_filter($notifications, fn($note) => empty($note['is_read'])));
  return [
    'notifications' => $notifications,
    'dropdownNotifications' => array_slice($notifications, 0, 4),
    'totalCount' => count($notifications),
    'unreadCount' => $unread,
  ];
}
