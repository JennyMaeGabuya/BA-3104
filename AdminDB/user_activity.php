<?php
session_name('ADMINSESSID');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../db_config.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['user_type'] ?? '') !== 'Admin')) {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

$userId = intval($_POST['user_id'] ?? 0);
if ($userId <= 0) {
  echo json_encode(['success' => false, 'error' => 'Missing user id']);
  exit;
}

try {
  $userStmt = $pdo->prepare("SELECT id, first_name, last_name, user_type, student_id, department, email, phone, created_at\n                             FROM users\n                             WHERE id = ? AND user_type IN ('Student','Faculty','Staff')\n                             LIMIT 1");
  $userStmt->execute([$userId]);
  $user = $userStmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
  }

  $lostStmt = $pdo->prepare("SELECT report_id, item_name, category, status, created_at\n                             FROM lost_reports\n                             WHERE user_id = ?\n                             ORDER BY created_at DESC\n                             LIMIT 10");
  $lostStmt->execute([$userId]);
  $lostReports = $lostStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  $foundStmt = $pdo->prepare("SELECT report_id, item_name, category, status, created_at\n                              FROM found_reports\n                              WHERE user_id = ?\n                              ORDER BY created_at DESC\n                              LIMIT 10");
  $foundStmt->execute([$userId]);
  $foundReports = $foundStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  $sessionsStmt = $pdo->prepare('SELECT session_id, login_time, last_activity, is_active FROM sessions WHERE user_id = ? ORDER BY login_time DESC LIMIT 10');
  $sessionsStmt->execute([$userId]);
  $sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  $payload = [
    'success' => true,
    'user' => $user,
    'lost_reports' => $lostReports,
    'found_reports' => $foundReports,
    'sessions' => $sessions,
  ];
  echo json_encode($payload);
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'error' => 'Server error']);
}