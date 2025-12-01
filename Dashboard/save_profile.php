<?php
/**
 * Save profile handler
 * Updates users.first_name, last_name, email, phone and refreshes session values
 */
session_start();
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../db_config.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

$first = trim($_POST['firstName'] ?? '');
$last = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

$errors = [];
if (!$first) $errors[] = 'First name is required.';
if (!$last) $errors[] = 'Last name is required.';
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is invalid.';

if (!empty($errors)) {
    $_SESSION['toast_message'] = implode(' ', $errors);
    header('Location: settings.php');
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?');
    $stmt->execute([$first, $last, $email, $phone, $userId]);

    // Refresh session values
    $_SESSION['first_name'] = $first;
    $_SESSION['last_name'] = $last;
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $phone;
    $_SESSION['user_name'] = trim($first . ' ' . $last);
    $_SESSION['fullname'] = $_SESSION['user_name'];

    $_SESSION['toast_message'] = 'Profile saved.';
} catch (PDOException $e) {
    $_SESSION['toast_message'] = 'Save failed: ' . $e->getMessage();
}

header('Location: settings.php');
exit;
