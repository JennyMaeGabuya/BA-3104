<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/db_connection.php';
// require_once 'config/functions.php';

$page = $_GET['page'] ?? 'login';
$file = __DIR__ . "/../pages/{$page}.php";

include __DIR__ . '/../includes/header.php';

if (file_exists($file) && $conn) {
    include $file;
} else{
    include __DIR__ . '/../pages/404.php';
}

include __DIR__ . '/../includes/footer.php';
?>