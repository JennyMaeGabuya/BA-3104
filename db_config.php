<?php
// Database configuration - update these values for your environment
$DB_HOST = '127.0.0.1';
$DB_NAME = 'findit';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // In production, avoid echoing the exception message
    die('Database connection failed: ' . $e->getMessage());
}

?>
