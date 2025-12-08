<?php
const DB_HOST = '127.0.0.1';
const DB_NAME = 'visitorlog_db';
const DB_USER = 'root'; 
const DB_PASS = ''; // Check your XAMPP settings

function connect_db() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false, // Critical for security
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("DB Connection Error: " . $e->getMessage());
        http_response_code(500);
        // Output JSON error for the frontend fetch()
        die(json_encode(["success" => false, "message" => "Database connection failed on the server."]));
    }
}
?>