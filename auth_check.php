<?php
/**
 * Authentication Check - Shared Component
 * Include this at the top of all protected pages
 * 
 * Usage:
 * <?php require_once 'auth_check.php'; ?>
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to the site login page (use absolute path)
    header('Location: /BA-3104/login.php');
    exit; // Stop script execution
}

// Optional: Set session variable for fullname if using 'user_name'
if (!isset($_SESSION['fullname']) && isset($_SESSION['user_name'])) {
    $_SESSION['fullname'] = $_SESSION['user_name'];
}
?>

